$(function(){
    var server_url = 'http://xo.loc/';

    $('.new_game').click(function(){
        $("input.cell")
            .val('')
            .removeAttr('disabled');
        $.getJSON( server_url + '?method=create', function( data ) {
            $('.game_id').val(data['id']);
            fillCells(data);
        });
    });

    $('.load_game').click(function(){
        $("input.cell")
            .val('')
            .removeAttr('disabled');
        $.getJSON( server_url + '?method=get_table&id=' + $('.game_id').val(), function( data ) {
            fillCells(data);
        });
    });

    $("input.cell").keyup(function(){
        $(this).val($('.expected_symbol').val());
        var y = parseInt(($(this).attr('name') - 1) / 3);
        var x = parseInt($(this).attr('name') - y*3)-1;
        $.getJSON( server_url + '?method=make_move&id=' + $('.game_id').val() + '&x=' + x + '&y=' + y, function( data ) {
            fillCells(data);
        });
    });
});

/**
 * Заполнить ячейки данными игры
 * @param data
 */
function fillCells(data){
    $.each(data['state'], function(key, val) {
        var input = $("input.position" + key);
        input.val(val);
        input.attr('disabled', 'disabled');
    });
    $('.expected_symbol').val(data['expected_symbol']);
    if(data['complete']){
        $("input.cell").attr('disabled', 'disabled');
        if(data['winner'] == null){
            alert('Игра окончена. Победила дружба');
        }else{
            alert('Игра окончена. Победили "' + data['winner'] + '"');
        }
    }
}