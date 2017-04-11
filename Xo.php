<?php

class Xo
{
    /** @var array Выигрышные комбинации */
    protected $success = array(
        array(1,2,3),
        array(4,5,6),
        array(7,8,9),
        array(1,4,7),
        array(2,5,8),
        array(3,6,9),
        array(1,5,9),
        array(3,5,7)
    );

    /** таблица состояний игр */
    const TABLE = 'tables';

    /**
     * @var mysqli|null
     */
    protected $db = null;
    /**
     * @var int|null
     */
    protected $id = null;
    /**
     * @var int количество ячеек поля
     */
    protected $cells = 9;
    /**
     * @var null символ, которым играет комп
     */
    protected $symbol = null;
    /**
     * @var null символ противника
     */
    protected $anti_symbol = null;
    /**
     * @var array|mixed состояние поля
     */
    protected $state = array();
    /**
     * @var bool статус завершения
     */
    protected $complete = false;
    /**
     * @var null кто победил
     */
    protected $winner = null;
    /**
     * @var null время начала
     */
    protected $time = null;

    /**
     * Xo constructor.
     * @param $id
     * @param $symbol
     * @param $anti_symbol
     * @param null $state
     * @param $time
     * @param $status
     * @param $winner
     */
    protected function __construct($id, $symbol, $anti_symbol, $state = null, $time, $status, $winner){
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB);
        $this->id = (int) $id;
        //TODO: проверка на валидность символов
        $this->symbol = $symbol;
        $this->anti_symbol = $anti_symbol;
        $this->state = json_decode($state, true);
        $this->time = $time;
        $this->complete = $status ? false : true;
        $this->winner = $winner;
    }

    /**
     * Создать новую игру
     * @return Xo
     * @throws Exception
     */
    static public function create(){
        //TODO: выбор символа (X/O)
        $symbol = 'X';
        $anti_symbol = 'O';
        $db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB);
        $sql = 'INSERT INTO `'.Xo::TABLE.'` (`symbol` , `anti_symbol`) VALUES ("'.$symbol.'", "'.$anti_symbol.'")';
        $db->query($sql);
        $id = $db->insert_id;
        $xo = Xo::factory($id);
        $rand = rand(1, 10);
        if($rand >=5){
            $xo->makeMove();
        }
        return $xo;
    }

    /**
     * @param $id
     * @return static
     * @throws Exception
     */
    static public function factory($id){
        $db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB);
        $sql = 'SELECT `state`, `symbol`, `anti_symbol`, `started`, `status`, `winner` FROM `'.Xo::TABLE.'` WHERE `id` = '.(int) $id;
        $result = $db->query($sql);
        $data = $result->fetch_assoc();
        if(empty($data)){
            throw new Exception('Xo instance not found');
        }
        return new static($id, $data['symbol'], $data['anti_symbol'], $data['state'], $data['started'], $data['status'], $data['winner']);
    }

    /**
     * @return int|null
     */
    public function getId(){
        return $this->id;
    }

    /**
     * Получить состояние поля
     * @return array
     */
    public function getState(){
        return (array) $this->state;
    }

    /**
     * Кто победил?
     * @return null
     */
    public function getWinner(){
        return $this->winner;
    }

    /**
     * Закончена ли игра?
     * @return bool
     */
    public function isComplete(){
        return $this->complete ? true : false;
    }

    /**
     * Получить символ ожидаемого хода
     * @return null
     */
    public function getExpectedSymbol(){
        return $this->anti_symbol;
    }

    /**
     * Можно ли делать ход (заполнено ли поле)?
     * @return bool
     */
    protected function canMakeMovie(){
        if(count($this->state) < $this->cells){
            return true;
        }
        return false;
    }

    /**
     * Сделать ход компьютера
     * @return array|mixed
     * @throws Exception
     */
    public function makeMove(){
        $this->isTimeOut();
        //если это первый ход, просто ходим с середины
        if(empty($this->state)){
            $this->updateState(array(5 => $this->symbol));
        }else{
            $possibleMoves = $this->getMoveRatings();
            $maxRatingA = 0;
            $minRatingB = 3;
            foreach ($possibleMoves as $movie) {
                if ($movie['ratingA'] > $maxRatingA) {
                    $maxRatingA = $movie['ratingA'];
                }
            }
            foreach ($possibleMoves as $movie) {
                if ($movie['ratingA'] == $maxRatingA){
                    if ($movie['ratingB'] < $minRatingB) {
                        $minRatingB = $movie['ratingB'];
                    }
                }
            }
            foreach ($possibleMoves as $movie) {
                if ($movie['ratingA'] == $maxRatingA && $movie['ratingB'] == $minRatingB) {
                    $this->updateState($movie['try']);
                    break;
                }
            }
            //проверить, не выиграл ли комп
            $this->completeGame($this->symbol);
        }
        return $this->state;
    }

    /**
     * Сделать ход противника
     * @param $position
     * @return array|mixed
     * @throws Exception
     */
    public function makeMoveHuman($position){
        $this->isTimeOut();
        if($this->complete){
            throw new Exception('Table is complete');
        }
        if(empty($this->state[$position])){
            $state = $this->state;
            $state[$position] = $this->anti_symbol;
            $this->updateState($state);
            //проверить, не выиграл ли человек. если нет - комп делает ход.
            if(!$this->completeGame($this->anti_symbol)){
                $this->makeMove();
            }
        }else{
            throw new Exception('This position is busy');
        }
        return $this->state;
    }

    /**
     * Обновить состояние поля
     * @param $newState
     * @return array|mixed
     */
    protected function updateState($newState){
        $this->state = $newState;
        $sql = 'UPDATE `'.Xo::TABLE.'` SET `state` = "'.$this->db->real_escape_string(json_encode($this->state)).'" WHERE `id` = '.(int) $this->id;
        $this->db->query($sql);
        return $this->state;
    }

    /**
     * Проверка, не выиграла ли одна из сторон
     * @param $symbol - символ стороны, для которой делается проверка
     * @return bool
     * @throws Exception
     */
    protected function completeGame($symbol){
        if(!in_array($symbol, array($this->symbol, $this->anti_symbol))){
            throw new Exception('invalid symbol');
        }
        foreach($this->success as $combination){
            $rating = $this->getTryRating($this->state, $combination, $symbol);
            if($rating == 3){
                $this->complete = true;
                $this->winner = $symbol;
                $sql = 'UPDATE `'.Xo::TABLE.'` SET `status` = 0, `winner` = "'.$this->winner.'" WHERE `id` = '.(int) $this->id;
                $this->db->query($sql);
                return true;
            }
        }
        //если больше нет ходов - закончить игру
        if(!$this->canMakeMovie()){
            $this->complete = true;
            $sql = 'UPDATE `'.Xo::TABLE.'` SET `status` = 0 WHERE `id` = '.(int) $this->id;
            $this->db->query($sql);
            return true;
        }
        return false;
    }

    /**
     * Получить рейтинги для возможных ходов
     * @return array
     */
    protected function getMoveRatings(){
        //массив возможных ходов
        $possibleMoves = array();
        for($i = 1; $i <= $this->cells; $i++){
            if(empty($this->state[$i])){
                $try = $this->state;
                $try[$i] = $this->symbol;
                $params = array();
                $params['try'] = $try;
                foreach($this->success as $combination){
                    $params['ratingA'] = $this->getTryRating($try, $combination, $this->symbol);
                    $params['ratingB'] = $this->getTryRating($try, $combination, $this->anti_symbol);
                }
                $possibleMoves[] = $params;
            }
        }
        return $possibleMoves;
    }

    /**
     * Получить рейтинг конкретной попытки хода
     * @param $try
     * @param $combination
     * @param $symbol
     * @return int от 1 до 3
     */
    protected function getTryRating($try, $combination, $symbol){
        $hits = 0;
        if(!empty($try[$combination[0]]) && $try[$combination[0]] == $symbol){
            $hits++;
        }
        if(!empty($try[$combination[1]]) && $try[$combination[1]] == $symbol){
            $hits++;
        }
        if(!empty($try[$combination[2]]) && $try[$combination[2]] == $symbol){
            $hits++;
        }
        return $hits;
    }

    /**
     * TODO: завершение игры по таймауту
     */
    protected function isTimeOut(){

    }
}