<?php

require_once('config.php');
require_once('Xo.php');

$method = $_GET['method'];

if(!empty($method)){
    switch($method) {
        case 'create':
            create();
            break;
        case 'get_table':
            get_table();
            break;
        case 'make_move':
            make_move();
            break;
    }
}

function create(){
    $xo = Xo::create();
    $state = $xo->getState();
    $result['id'] = $xo->getId();
    $result['state'] = $state;
    $result['expected_symbol'] = $xo->getExpectedSymbol();
    echo json_encode($result);
    return;
}

function get_table(){
    if(empty($_GET['id'])){
        return 'Error: empty table id';
    }
    $id = (int) $_GET['id'];
    $xo = Xo::factory($id);
    $state = $xo->getState();
    $result['state'] = $state;
    $result['complete'] = $xo->isComplete();
    $result['winner'] = $xo->getWinner();
    $result['expected_symbol'] = $xo->getExpectedSymbol();
    echo json_encode($result);
    return;
}

function make_move(){
    if(empty($_GET['id'])){
        return 'Error: empty table id';
    }
    $id = (int) $_GET['id'];
    $x = !isset( $_GET['x']) ? null : (int) $_GET['x'];
    $y = !isset( $_GET['y']) ? null : (int) $_GET['y'];
    if(!is_null($x) && !is_null($y)){
        if($x > 2 || $y > 2){
            echo 'Error: invalid coordinates';
            return;
        }
        $xo = Xo::factory($id);
        //преобразовываем координаты
        $position = $x+1+$y*3;
        $state = $xo->makeMoveHuman($position);
        $result['state'] = $state;
        $result['complete'] = $xo->isComplete();
        $result['winner'] = $xo->getWinner();
        $result['expected_symbol'] = $xo->getExpectedSymbol();
        echo json_encode($result);
        return;
    }else{
        echo 'Error: choose coordinates';
    }
}