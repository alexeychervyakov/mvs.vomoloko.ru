<?php
require_once('auth.php');
include_once ('operations.php');
include_once ('ReportManager.php');

$db=false;

$dates = date('Ymd');
if(isset($_POST['date'])){
    list($d, $m, $y) = explode('.', $_POST['date']);
    $dates = $y.$m.$d.'000000';
}
if($shopname){

    $db = new MysqlWrapper();
    $date_dt=date_create($dates);

    $min_to_sell_amount = get_minimum_amount_to_sale($shopname,'АТАГ ВЕСОВЫЕ',$date_dt,$db);
    $sold_amount = get_amount_sold($shopname,'АТАГ ВЕСОВЫЕ',$date_dt,$db);

    /*  Average check bonus routines
       $date_week_ago = date_add($date_dt,date_interval_create_from_date_string("7 days ago"));

        $newAvgCheck= get_avg_check($shopname,$date_dt,$db);
        $oldAvgCheck= get_avg_check($shopname,$date_week_ago,$db);
        $bonus = $newAvgCheck > $oldAvgCheck ? $newAvgCheck : 0;
    */
}
$js_pre_operation = 'false';
if($shop_id) {
    if($db===false) {
        $db = new MysqlWrapper();
    }

    $startAmount='';
    $endAmount='';
    $previousEndAmount='';

    $last_whoworked = load_shifts($db, $shop_id, $dates);

    $state_operation = '';
    if (isset($_GET['o']) && in_array($_GET['o'], array_keys($operations))) {
        $state_operation = ' disabled';
        $js_pre_operation = 'true';
    }
}
if(!($db===false)){
    $db->disconnect();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<title>Во!Молоко (менеджер)</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="css/jquery-ui.min.css" rel="stylesheet">
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/bootstrap-datetimepicker.css" rel="stylesheet">
	<link href="css/autocomplete.css" rel="stylesheet">
	<link href="css/style.css" rel="stylesheet">
	<script type="text/javascript" src="js/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
	<script type="text/javascript" src="js/moment.js"></script>
	<script type="text/javascript" src="js/locale/ru.js"></script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/bootstrap-datetimepicker.js"></script>
</head>
<body>
	<div class="logo"><img src="images/logo.png"></div>
	<div class="container-fluid main-wrapper">
		<?php if (!isset($_SESSION['manager_authorized']) || $_SESSION['manager_authorized'] !== true) { ?>
		<div class="well" style="margin-top:20px;">
			<div class="row">
				<div class="col-sm-12">
					<form action="" method="post" class="form-horizontal">
					<div class="form-group">
						<label for="manager_password" class="col-sm-2 control-label">Пароль</label>
						<div class="col-sm-4">
							<input type="password" class="form-control" name="manager_password" id="manager_password">
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-2 col-sm-4">
							<button type="submit" class="btn btn-primary">Авторизоваться</button>
						</div>
					</div>
					</form>
				</div>
			</div>
		</div>
		<?php } else { ?>
		<div id="tabs">
			<div class="row">
				<div class="col-sm-4" style="min-width: 300px;">
					<div class="tabs-wrapper" id="tabs-wrapper"-->
						<ul>
							<li><a href="#tab-1" >Касса</a></li>
							<li><a href="#tab-2">Перемещения</a></li>
							<li><a href="#tab-3">Отчеты</a></li>
						</ul>
					</div>
				</div>
				<div class="col-sm-2" style="padding-top: 0.2em;min-width: 170px;">
					<div class="form-group">
						<div class='input-group date' id='date'>
							<input type='text' name="date" class="form-control" />
							<span class="input-group-addon">
								<span class="glyphicon glyphicon-calendar"></span>
							</span>
		                </div>
					</div>
				</div>
				<div class="col-sm-4 hide-on-tab3" style="padding-top: 0.2em;">
					<form action="" method="post" id="change_shop_manager">
					<select class="form-control" name="shop_manager">
						<option value="">Выберите магазин</option>
						<?php
							foreach ($shops as $shop) {
								echo '<option value="'.$shop['title'].'"'.(isset($_SESSION['shop_manager']) && $_SESSION['shop_manager'] == $shop['title'] ? ' selected' : '').'>'.$shop['title'].'</option>';
							}
						?>
					</select>
					</form>
				</div>
				<div class="col-sm-2" style="padding-top: 0.2em;">
					<form action="" method="post">
						<button type="submit" class="btn btn-primary" name="manager_exit">Выход</button>
					</form>
				</div>

			</div>

            <div id="tab-1">
                    <!--div class="row">

                        <div class="col-sm-5">
                            <form action="" method="post" class="form-horizontal" id="form-operations">
                                <div class="form-group">
                                    <label for="action" class="col-sm-4 control-label">Действие</label>
                                    <div class="col-sm-8">
                                        <select class="form-control" id="action">
                                            <option value="">Выберите</option>
                                            <--?php
                                            foreach ($actions as $key => $title) {
                                                echo '<option value="'.$key.'">'.$title.'</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="cashier" class="col-sm-4 control-label">Продавец</label>
                                    <div class="col-sm-8">
                                        <select class="form-control" id="cashier">
                                            <option value="">Выберите</option>
                                            <--?php
                                            foreach ($cashiers as $cashier) {
                                                echo '<option value="'.$cashier.'">'.$cashier.'</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group" id="group-amount">
                                    <label for="amount" class="col-sm-4 control-label">Сумма</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="amount">
                                    </div>
                                </div>
                                <div class="form-group" id="group-howmuch" style="display:none;">
                                    <label for="howmuch" class="col-sm-4 control-label">Сколько</label>
                                    <div class="col-sm-8">
                                    <div class="col-sm-8">
                                        <select class="form-control" id="howmuch">
                                            <option value="">Выберите</option>
                                            <option value="1">1</option>
                                            <option value="0.5">0.5</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="comment2" class="col-sm-4 control-label">Комментарий</label>
                                    <div class="col-sm-8">
                                        <textarea class="form-control" id="comment2"></textarea>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-sm-offset-4 col-sm-8">
                                        <button type="submit" class="btn btn-primary" id="tab1-submit">Внести</button>
                                    </div>
                                </div>
                            </form>
                            <br />
                            <h4>Кто работал в смену</h4>
                            <table class="table table-bordered table-condensed" id="table-whoworked">
                                <thead>
                                <tr>
                                    <th>Продавец</th>
                                    <th>Сколько</th>
                                    <th>Комментарий</th>
                                </tr>
                                </thead>
                                <tbody>
                                <--?php
                                foreach ($last_whoworked as $whoworked) {
                                    echo '<tr><td>'.$whoworked[2].'</td><td>'.$whoworked[3].'</td><td>'.$whoworked[4].'</td></tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-sm-7">
                            <h4>Закрытие прошлой смены: <strong><span id="previous-end-amount"></span></strong></h4>
                            <h4>Сумма в начале смены: <strong><span id="start-amount"></span></strong></h4>
                            <h4>Бонус: <strong><span id="bonus"></span></strong></h4>
                            <h4>Лучше всех работает: <strong><span id="best_shop"></span></strong></h4>

                            <table class="table table-bordered table-condensed" id="table-fines">
                                <thead></thead>
                                <tbody></tbody>
                            </table>
                            <br />
                            <h4>Операции за смену</h4>
                            <table class="table table-bordered table-condensed" id="table-operations">
                                <thead>
                                <tr>
                                    <th>Действие</th>
                                    <th>Сумма</th>
                                    <th>Продавец</th>
                                    <th>Комментарий</th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>

                            <h4>Сумма в конце смены: <strong><span id="end-amount"></span></strong></h4>
                        </div>
                    </div-->
                <div class="row">

                    <div class="col-sm-5">
                        <form action="" method="post" class="form-horizontal" id="form-operations">
                            <div class="form-group">
                                <label for="action" class="col-sm-4 control-label">Действие</label>
                                <div class="col-sm-8">
                                    <select class="form-control" id="action">
                                        <option value="">Выберите</option>
                                        <?php
                                        foreach ($actions as $key => $title) {
                                            echo '<option value="' . $key . '">' . $title . '</option>';

                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="cashier" class="col-sm-4 control-label">Продавец</label>
                                <div class="col-sm-8">
                                    <select class="form-control" id="cashier">
                                        <option value="">Выберите</option>
                                        <?php
                                        foreach ($cashiers as $cashier) {
                                            echo '<option value="'.$cashier.'">'.$cashier.'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" id="group-amount">
                                <label for="amount" class="col-sm-4 control-label">Сумма</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="amount">
                                </div>
                            </div>
                            <div class="form-group" id="group-howmuch" style="display:none;">
                                <label for="howmuch" class="col-sm-4 control-label">Сколько</label>
                                <div class="col-sm-8">
                                    <select class="form-control" id="howmuch">
                                        <option value="">Выберите</option>
                                        <option value="1">1</option>
                                        <option value="0.5">0.5</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="comment2" class="col-sm-4 control-label">Комментарий</label>
                                <div class="col-sm-8">
                                    <textarea class="form-control" id="comment2"></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-sm-offset-4 col-sm-8">
                                    <button type="submit" class="btn btn-primary" id="tab1-submit">Внести</button>
                                </div>
                            </div>
                        </form>
                        <br />
                        <h4>Кто работал в смену</h4>
                        <table class="table table-bordered table-condensed" id="table-whoworked">
                            <thead>
                            <tr>
                                <th>Продавец</th>
                                <th>Сколько</th>
                                <th>Комментарий</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach ($last_whoworked as $whoworked) {
                                echo '<tr><td>'.$whoworked[2].'</td><td>'.$whoworked[3].'</td><td>'.$whoworked[4].'</td></tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-sm-7">
                        <h4>Закрытие прошлой смены: <strong><span id="previous-end-amount"></span></strong></h4>
                        <h4>Сумма в начале смены: <strong><span id="start-amount"></span></strong></h4>
                        <h4>Премия: <strong><span id="bonus"></span></strong></h4>
                        <h4>Лучший средний чек: <strong><span id="best_shop"></span></strong></h4>

                        <!--
                    <h4>Средний чек на прошлой неделе: <strong><span id="old-avg-check">< ?php echo $oldAvgCheck; ?></span></strong></h4>
                    <h4>Средний чек сегодня: <strong><span id="new-avg-check">< ?php echo $newAvgCheck."(".(($oldAvgCheck>$newAvgCheck)?("".($newAvgCheck-$oldAvgCheck)):("+".($newAvgCheck-$oldAvgCheck))).")"; ?></span></strong></h4>
                    <h3>Премия сегодня: <strong><span id="bonus">< ?php echo $bonus; ?></span></strong></h3>-->

                        <table class="table table-bordered table-condensed" id="table-fines">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                        <br />
                        <h4>Операции за смену</h4>
                        <table class="table table-bordered table-condensed" id="table-operations">
                            <thead>
                            <tr>
                                <th>Действие</th>
                                <th>Сумма</th>
                                <th>Продавец</th>
                                <th>Комментарий</th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>

                        <h4>Сумма в конце смены: <strong><span id="end-amount"></span></strong></h4>
                    </div>

                </div>

        </div>

        <div id="tab-2" class="tab">
            <div class="row">

                <div class="col-sm-5">
                    <form action="" method="post">
                        <div class="well">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="operation">Операция</label>
                                        <select class="form-control" name="operation" id="operation"<?php echo $state_operation; ?>>
                                            <option value="">Выберите</option>
                                            <?php
                                            foreach ($operations as $id => $title) {
                                                echo '<option value="'.$id.'"'.(isset($_GET['o']) && $_GET['o'] == $id ? ' selected' : '').'>'.$title.'</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12" id="subj_shop_div" hidden="true">
                                    <div class="form-group">
                                        <label for="operation">Магазин</label>
                                        <select class="form-control" id="subj_shop">
                                            <option value="">Выберете магазин</option>
                                            <?php
                                            foreach ($shops as $shop) {
                                                if($shop != $shopname) {
                                                    echo '<option value="'.$shop['store_id'].'">'.$shop['title'].'</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="well">
                            <div class="row">
                                <div class="col-sm-9">
                                    <div class="form-group">
                                        <label for="item">Товар</label>
                                        <input type="text" name="item" class="form-control" id="item">
                                    </div>
                                </div>

                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label for="quantity">Количество</label>
                                        <input type="text" name="quantity" class="form-control" id="quantity">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-12">
                                <table class="table table-bordered table-condensed" id="table">
                                    <thead>
                                    <tr>
                                        <th>Код товара</th>
                                        <th>Штрих-код товара</th>
                                        <th>Название товара</th>
                                        <th>Количество</th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="well">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="comment">Комментарий</label>
                                        <textarea name="comment" class="form-control" id="comment"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="text-right">
                                        <div class="loading"></div>
                                        <button type="submit" class="btn btn-primary" id="submit">Отправить</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="tab-3" class="tab">
            <div class="row">
                <div class="col-sm-5">
                    <h4>Показатели за смену</h4>
                    <table class="table table-striped table-bordered table-sm" id="current-results">
                        <thead>
                        <tr>
                            <th class="th-sm">Магазин</th>
                            <th class="th-sm">Чеков</th>
                            <th class="th-sm">Средний чек</th>
                            <th class="th-sm">Сумма</th>
                            <th class="th-sm">Оценка </th>
                            <th class="th-sm">Отзывы</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        var selected = null;
        var editMode = false;

        $( "#tabs" ).tabs();

        function load_report (op_type){
            $.ajax({
                url: 'update_report.php',
                type: 'POST',
                data: {
                    action: "load",
                    operation: op_type
                },
                dataType: 'json',
                success: function(report) {
                    $('.loading').hide();
                    $('#submit').prop('disabled', false);
                    load_report_table(report);
                },
                error: function(xhr, status, errorThrown) {
                    $('.loading').hide();
                    $('#submit').prop('disabled', false);
                    alert(xhr.status + "\r\n" + xhr.responseText + "\r\n" + status + "\r\n" + errorThrown);
                }
            });
        }

        function load_report_table( report ){
            if( report != null && report.id != -1) {
                $('#operation').val(report.type);
                $('#subj_shop').val(report.subj_store_id);
                $('#submit').val(report.id);
                $('#comment').val(report.comment);

                //clear the table
                $('#table > tbody > tr').fadeOut(300, function () {
                    $(this).remove();
                });
                report.goods_list.forEach(function(good,index,goods_list){
                    $('<tr><td>' + good.article + '</td><td>' + good.barcode + '</td><td>' + good.name + '</td><td>' + good.amount + '</td>' +
                        '<td class="text-center"><button class="btn btn-primary btn-xs button-delete-item" value="'+ encodeURI(JSON.stringify({report_id:report.id, good_id:good.id})) + '"><span class="glyphicon glyphicon-remove"></span></button></td>' +
                        //'<td class="text-center"><button class="btn btn-primary btn-xs button-edit-item"><span class="glyphicon glyphicon-pencil"></span></button></td>' +
                        '</tr>').prependTo('#table > tbody').hide().fadeIn();
                });
                $('#comment').val(report.comment);
            } else {
                $('#table > tbody > tr').fadeOut(300, function () {
                    $(this).remove();
                });
            }
        }

        var pre_operation = <?php echo $js_pre_operation; ?>;
        jQuery(document).ready(function($) {
            $('#tabs').tabs({
                active: 2,
                beforeActivate: function (event, ui) {
                    if( ui.newPanel.attr('id') == 'tab-1') {
                        reload_operations($('#date').data("date"));
                    } else if( ui.newPanel.attr('id') == 'tab-3') {
                        reload_results($('#date').data("date"));
                    }
                    return
                }
            });

            $('#date').datetimepicker({
                locale: 'ru',
                defaultDate: new Date(),
                format: 'DD.MM.YYYY'
            });

            reload_results($('#date').data("date"));

            $('#date').on("dp.change", function(e) {
                    reload_operations($('#date').data("date"));
                    reload_results($('#date').data("date"));
            });

            $('#item').autocomplete({
                serviceUrl: 'suggestions.php',
                triggerSelectOnValidInput: false,
                onSelect: function (suggestion) {
                    selected = suggestion;
                    checkItem($(this).val());
                }
            });

            $('#operation').change(function (){
                var op_type;

                op_type = $('#operation').val();
                load_report(op_type);

                if( op_type == "2" || op_type == "1") {
                    $('#subj_shop_div').show();
                    $('#subj_shop').focus();
                } else {
                    $('#subj_shop_div').hide();
                    $('#item').focus();
                }
                return false;
            });

            $('#subj_shop').change(function (){
                $('#item').focus();
            });

            $('#item').keypress(function (e) {
                if (e.which == 13 && $(this).val() != '') {
                    e.preventDefault();
                    checkItem($(this).val());
                    $('#quantity').focus();
                    return false;
                }
            });
            $('#item').select(function () {
                $('#quantity').focus();
            });

            $('#check-modal .button-yes').click(function(){
                $('#check-modal').modal('hide');
                $('#quantity').focus();
            });

            $('#check-modal .button-no').click(function(){
                $('#check-modal').modal('hide');
                $('#item').focus();
            });

            $('#quantity').keydown(function(event) {
                if ((event.which >= 48 && event.which <= 57) || event.which == 190 || event.which == 188) {
                    if ($(this).val().indexOf('.') != -1) {
                        var parts = $(this).val().split('.');
                        if (typeof parts[1] == 'string' && parts[1].length >= 3) {
                            event.preventDefault();
                        }
                    }
                    if ($(this).val().indexOf(',') != -1) {
                        var parts = $(this).val().split(',');
                        if (typeof parts[1] == 'string' && parts[1].length >= 3) {
                            event.preventDefault();
                        }
                    }
                }
            });

            $('#quantity').keypress(function(event) {
                var regex = /[^0-9\.\, ]/g;
                if ($(this).val() != '' && regex.test($(this).val())) {
                    $(this).parents('.form-group').addClass('has-error');
                } else {
                    var floatVal = $(this).val().replace(',', '.');
                    if (isNaN(floatVal) || floatVal < 0) {
                        $(this).parents('.form-group').addClass('has-error');
                    } else {
                        $(this).parents('.form-group').removeClass('has-error');
                        if ($(this).val() != '' && event.which == 13) {

                            if ($('#operation').val() == '') {
                                alert('Укажите операцию.');
                                event.preventDefault();
                                return false;
                            } else {
                                $('.loading').css('display', 'inline-block');
                                $.ajax({
                                    url: 'update_report.php',
                                    type: 'POST',
                                    data: {
                                        operation: $('#operation').val(),
                                        date: $('#date').data("date"),
                                        subj: $('#subj_shop').val(),
                                        comment: $('#comment').val().replace('\n', '<br>'),
                                        action: "update",

                                        item_article: selected.data[0],
                                        item_barcode: selected.data[1],
                                        item_id: selected.data[2],
                                        item: $('#item').val(),
                                        amount: parseFloat($('#quantity').val().replace(',', '.').replace(' ', '')),
                                        update: editMode
                                    },
                                    dataType: 'json',
                                    success: function (report) {
                                        $('.loading').hide();
                                        $('#submit').prop('disabled', false);
                                        load_report_table(report);
                                    },
                                    error: function (xhr, status, errorThrown) {
                                        $('.loading').hide();
                                        $('#submit').prop('disabled', false);
                                        alert(xhr.status + "\r\n" + xhr.responseText + "\r\n" + status + "\r\n" + errorThrown);
                                    }
                                });
                            }

                            $('#quantity').val('');
                            $('#item').val('').focus();
                            selected = null;
                            event.preventDefault();
                        }
                    }
                }
                if (event.which == 13) event.preventDefault();
            });

            /*$(document).on('click', '.button-edit-item', function(e) {
                var item = $(this).parents('tr').find('td:nth-child(3)').html();

                $('#item').val(item);
                $('#quantity').val('').focus();
                editMode = true;
                e.preventDefault();
            });*/

            $(document).on('click', '.button-delete-item', function(e) {

                $(this).parents('tr').fadeOut(300, function(){ $(this).remove(); });
                e.preventDefault();
                var report_params = JSON.parse(decodeURI($(this).val()));
                $.ajax({
                    url: 'update_report.php',
                    type: 'POST',
                    data: {
                        action: "delete",
                        report_id: report_params['report_id'],
                        good_id: report_params['good_id'],
                    },
                    dataType: 'json',
                    success: function (report) {
                        $('.loading').hide();
                        $('#submit').prop('disabled', false);
                    },
                    error: function (xhr, status, errorThrown) {
                        $('.loading').hide();
                        $('#submit').prop('disabled', false);
                        alert(xhr.status + "\r\n" + xhr.responseText + "\r\n" + status + "\r\n" + errorThrown);
                    }
                });
            });

            $('#submit').click(function(e) {
                if ($('#operation').val() == '') {
                    alert('Укажите операцию.');
                    e.preventDefault();
                    return false;
                }
                if ($('#date').data("date") == '') {
                    alert('Укажите дату.');
                    e.preventDefault();
                    return false;
                }
                var items = [];
                $('#table > tbody > tr').each(function() {
                    items.push({
                        'code': $('td:nth-child(1)', this).html(),
                        'barcode': $('td:nth-child(2)', this).html(),
                        'title': $('td:nth-child(3)', this).html(),
                        'quantity': $('td:nth-child(4)', this).html(),
                    });
                });
                if (items.length == 0) {
                    alert('Добавьте товары.');
                    e.preventDefault();
                    return false;
                }
                $(this).prop('disabled', true);
                $('.loading').css('display', 'inline-block');
                $.ajax({
                    url: 'order.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        comment: '"' + $('#comment').val().replace('\n', '<br>') + '"',
                        report_id: $('#submit').val(),
                        operation: $('#operation').val(),
                        date: $('#date').data("date"),
                        subj: $('#subj_shop').val(),
                        items: items,
                    },
                    success: function(data) {
                        $('.loading').hide();
                        $('#submit').prop('disabled', false);
                        if (!pre_operation) $("#operation")[0].selectedIndex = 0;
                        $('#table > tbody > tr').fadeOut(300, function(){ $(this).remove(); });
                        $('#comment').val('');
                        $('body,html').animate({ scrollTop: 0 }, 300);
                    },
                    error: function(xhr, status, errorThrown) {
                        $('.loading').hide();
                        $('#submit').prop('disabled', false);
                        alert(xhr.status + "\r\n" + xhr.responseText + "\r\n" + status + "\r\n" + errorThrown);
                    }
                });
                e.preventDefault();
            });

            $('#action').change(function() {
                $(this).parents('.form-group').removeClass('has-error');
                if ($(this).val() == 'whowork') {
                    $('#group-amount').hide();
                    $('#group-howmuch').show();
                    $('#cashier').focus();
                } else {
                    $('#group-howmuch').hide();
                    $('#group-amount').show();
                    if ($('#cashier').val() == '') {
                        $('#cashier').focus();
                    } else {
                        $('#amount').focus();
                    }
                }
            });

            $('#cashier').change(function() {
                $(this).parents('.form-group').removeClass('has-error');
                if ($(this).val() != '') {
                    if ($('#action').val() == 'whowork') {
                        $('#howmuch').focus();
                    } else {
                        $('#amount').focus();
                    }
                }
            });

            $('#howmuch').change(function() {
                $(this).parents('.form-group').removeClass('has-error');
                $('#form-operations').submit();
            });

            $('#amount, #comment2').change(function() {
                $(this).parents('.form-group').removeClass('has-error');
            });

            $('#amount').keypress(function(e) {
                if (e.which == 13 && $(this).val() != '') {
                    if ($('#action').val() == 'payment') {
                        $('#comment2').focus();
                        return false;
                    }
                }
            });

            $('#password-modal').on('shown.bs.modal', function () {
                $('#collector_password').focus();
            })

            $('#form-operations').submit(function(e) {
                e.preventDefault();
                if ($('#action').val() == '') {
                    $('#action').parents('.form-group').addClass('has-error');
                    alert('Укажите действие.');
                    return false;
                }
                if ($('#cashier').val() == '') {
                    $('#cashier').parents('.form-group').addClass('has-error');
                    alert('Укажите продавца.');
                    return false;
                }
                if ($('#action').val() != 'whowork') {
                    var regex = /[^0-9\.]/g;
                    var floatVal = $('#amount').val().replace(',', '.').replace(' ', '');
                    if (floatVal == '' || regex.test(floatVal) || isNaN(floatVal) || floatVal < 0) {
                        $('#amount').parents('.form-group').addClass('has-error');
                        alert('Некорректная сумма.');
                        return false;
                    }
                } else {
                    if ($('#howmuch').val() == '') {
                        $('#howmuch').parents('.form-group').addClass('has-error');
                        alert('Укажите сколько.');
                        return false;
                    }
                }
                if ($('#action').val() == 'payment') {
                    if ($('#comment2').val() == '') {
                        $('#comment2').parents('.form-group').addClass('has-error');
                        alert('Укажите комментарий.');
                        return false;
                    }
                }
                if ($('#action').val() == 'withdraw') {
                    $('#password-modal').modal('show');
                } else {
                    if ($('#action').val() == 'whowork') {
                        addWhoWork();
                    } else {
                        addOperation('');
                    }
                }
            });

            $('#password-modal .button-cancel').click(function(){
                $('#password-modal').modal('hide');
            });

            $('#password-modal .button-ok').click(function(){
                $('#collector-password-form').submit();
            });

            $(document).on('submit', '#collector-password-form', function(e){
                e.preventDefault();
                var collectorPassword = $('#password-modal #collector_password').val();
                $('#password-modal').modal('hide');
                $('#password-modal #collector_password').val('');
                addOperation(collectorPassword);
            });

            $(document).on('submit', '#reports-auth', function(e){
                e.preventDefault();
                $.ajax({
                    url: 'reports.php',
                    type: 'POST',
                    data: 'auth_pass=' + $('#reports_password').val(),
                    success: function(data) {
                        $('#ui-id-4').html(data);
                    },
                    error: function(xhr, status, errorThrown) {
                        alert(xhr.status + "\r\n" + xhr.responseText + "\r\n" + status + "\r\n" + errorThrown);
                    }
                });
            });
            $(document).on('click', '#fine-del-btn', function(e){
                e.preventDefault();
                $.ajax({
                    url: 'remove_action.php',
                    type: 'POST',
                    data: {
                        action_id: $('#fine-del-btn').val(),
                        date: $('#date').data("date")
                    },
                    dataType: 'json',
                    success: function(response) {
                        load_opertaions_response(response);
                    },
                    error: function(xhr, status, errorThrown) {
                        alert(xhr.status + "\r\n" + xhr.responseText + "\r\n" + status + "\r\n" + errorThrown);
                    }
                });
            });

            var previousHour = 0;
            setInterval(function(){
                var realDate = new Date();
                var currentHour = realDate.getHours();
                if (currentHour == 0 && previousHour == 23) {
                    $("#date").data('DateTimePicker').date(new Date());
                }
                previousHour = currentHour;
            }, 5000);

            $('select[name="shop_manager"]').change(function(){
                $('#change_shop_manager').submit();
            });

            $('#password-modal .button-cancel').click(function(){
                $('#password-modal').modal('hide');
            });

            $('#password-modal .button-ok').click(function(){
                $('#collector-password-form').submit();
            });

            $(document).on('submit', '#collector-password-form', function(e){
                e.preventDefault();
                var collectorPassword = $('#password-modal #collector_password').val();
                $('#password-modal').modal('hide');
                $('#password-modal #collector_password').val('');
                addOperation(collectorPassword);
            });

        });

        function reload_operations(date) {
            $.ajax({
                url: 'getoperations.php',
                type: 'POST',
                data: 'date=' + date,
                dataType: 'json',
                success: function(response) {
                    load_opertaions_response(response);
                },
                error: function(xhr, status, errorThrown) {
                    alert(xhr.status + "\r\n" + xhr.responseText + "\r\n" + status + "\r\n" + errorThrown);
                }
            });
        }

        function reload_results(date) {
            $.ajax({
                url: 'getresults.php',
                type: 'POST',
                data: 'date=' + date,
                dataType: 'json',
                success: function(response) {
                    load_results_response(response);
                },
                error: function(xhr, status, errorThrown) {
                    alert(xhr.status + "\r\n" + xhr.responseText + "\r\n" + status + "\r\n" + errorThrown);
                }
            });
        }


        function load_opertaions_response(response) {
            $('#previous-end-amount').html(response.pea);
            $('#start-amount').html(response.sa);
            $('#end-amount').html(response.ea);
            $('#table-operations > tbody > tr').remove();
            $('#table-whoworked > tbody > tr').remove();
            $('#table-fines > tbody > tr').remove();
            $('#table-fines > thead > tr').remove();

            if (response.operations.length > 0) {
                for (i in response.operations) {
                    $('<tr><td>' + response.operations[i][2] + '</td><td>' + response.operations[i][3] + '</td><td>' + response.operations[i][4] + '</td><td>' + response.operations[i][5] + '</td></tr>').prependTo('#table-operations > tbody');
                }
            }
            if (response.whoworked.length > 0) {
                for (i in response.whoworked) {
                    $('<tr><td>' + response.whoworked[i][2] + '</td><td>' + response.whoworked[i][3] + '</td><td>' + response.whoworked[i][4] + '</td></tr>').prependTo('#table-whoworked > tbody');
                }
            }
            var plan_scale = 1;
            var plan_bonus = 0;
            var plan_message = "";
            if (!(typeof response.plan_results === 'undefined')) {
                plan_scale = 1/response.plan_results.penalty;
                plan_bonus = response.plan_results.bonus;
                plan_message = '<br/>' + response.plan_results.message;
            }
            if (!(typeof response.stat.avg_check === 'undefined') && !(response.stat.error > 0) ){

                if ( response.total_fine > 0) {
                    $('#bonus').html( Math.round(response.stat.bonus * plan_scale + plan_bonus) + ' = '+ response.stat.percent + '% от ' + response.stat.sold +
                        //' + бонус ' + response.stat.bonus_percent +
                        ' - штраф ' + response.total_fine + ' руб.' +
                        //'<br/> Ваш ср. чек: ' + response.stat.avg_check + ' ++ ' + response.stat.place + ' место++'+ plan_message
                        '') ;

                } else {
                    $('#bonus').html(Math.round(response.stat.bonus * plan_scale + plan_bonus) + ' = '+ response.stat.percent + '% от ' + response.stat.sold +
                        //' + бонус ' + response.stat.bonus_percent +
                        //'<br/> Ваш ср. чек: ' + response.stat.avg_check + ' ++ ' + response.stat.place + ' место++'+ plan_message
                        '') ;
                }
                //$('#best_shop').html( response.stat.best_shop + ' Ср. чек: ** ' + response.stat.max_avg_check ) + ' **';

            } else {
                $('#bonus').html(plan_message);
                $('#best_shop').html('-');
            }
            if ( !(typeof response.fine === 'undefined') && response.fine.length > 0){
                $('<tr><th>Время</th><th>Причина</th><th>Продавец</th><th>Штраф</th></tr>').prependTo('#table-fines > thead');
                for (i in response.fine) {
                    $('<tr><td>' + response.fine[i][1] + '</td><td>' + response.fine[i][5] + '</td><td>' + response.fine[i][4] + '</td><td>' + response.fine[i][3] + '</td>' +
                        '<td><button value="' + response.fine[i][6] + '" class="btn " id="fine-del-btn">x</button></td></tr>').prependTo('#table-fines > tbody');
                }
            }
        }

        function load_results_response(response) {
            $('#current-results > tbody > tr').remove();

            sorted_stat = new Map();
            for (i in response.shop_stat){
                sorted_stat.set(response.shop_stat[i].place, response.shop_stat[i])
            }
            sorted_stat = new Map([...sorted_stat.entries()].sort(function(a,b){
                return -a[0]+b[0];}));

            sorted_stat.forEach(function(value, key){
                var av_points
                av_points = value.av_points;
                if (!isNaN(parseFloat(av_points))){
                    av_points=Math.round(av_points*100)/100;
                }

                $('<tr><td> ' + value.place +' ' + value.name +  //shop name
                    '</td><td>' + value.checks + //checks
                    '</td><td>' + value.avg_checks + //average check
                    '</td><td>' + value.income + //income
                    '</td><td>' + av_points +  //feedback value
                    '</td><td>' + value.points_line +  //feedback value
                    '</td></tr>').prependTo('#current-results > tbody');
            })
        }

        function checkItem(item) {
            $.ajax({
                url: 'check.php',
                type: 'POST',
                data: 'query=' + item,
                dataType: 'json',
                success: function(data) {
                    if (data.status == '1') {
                        $('#quantity').focus();
                    }
                    if (data.status == '0') {
                        $('#check-modal').modal('show');
                    }
                    if (data.status == '2') {
                        $('#item').autocomplete('disable');
                        $('#item').val(data.item.value);
                        $('#item').autocomplete('enable');
                        selected = data.item;
                        $('#quantity').focus();
                    }
                },
                error: function(xhr, status, errorThrown) {
                    alert(xhr.status + "\r\n" + xhr.responseText + "\r\n" + status + "\r\n" + errorThrown);
                }
            });
        }

        function addOperation(pass) {
            $('#tab1-submit').prop('disabled', true);
            $.ajax({
                url: 'addoperation.php',
                type: 'POST',
                data: {
                    date: $('#date').data("date"),
                    action: $('#action').val(),
                    cashier: $('#cashier').val(),
                    amount: $('#amount').val().replace('.', ',').replace(' ', ''),
                    comment: $('#comment2').val().replace('\n', '<br>'),
                    password: pass,
                },
                dataType: 'json',
                success: function(response) {

                    $('#tab1-submit').prop('disabled', false);
                    if (response.result == 'ok') {
                        load_opertaions_response(response);
                        $('#amount').val('');
                        $('#comment2').val('');
                        $('#action').val('');
                        $('#action').focus();
                    }
                    if (response.result == 'wrong_password') {
                        alert('Неверный пароль.');
                        return false;
                    }
                },
                error: function(xhr, status, errorThrown) {
                    $('#tab1-submit').prop('disabled', false);
                    alert(xhr.status + "\r\n" + xhr.responseText + "\r\n" + status + "\r\n" + errorThrown);
                }
            });
        }

        function addWhoWork() {
            $('#tab1-submit').prop('disabled', true);
            $.ajax({
                url: 'addwhowork.php',
                type: 'POST',
                data: {
                    date: $('#date').data("date"),
                    cashier: $('#cashier').val(),
                    howmuch: $('#howmuch').val().replace('.', ',').replace(' ', ''),
                    comment: $('#comment2').val().replace('\n', '<br>')
                },
                dataType: 'json',
                success: function(response) {
                    $('#tab1-submit').prop('disabled', false);
                    if (response.result == 'ok') {
                        var tr = $('<tr><td>' + $('#cashier').val() + '</td><td>' + $('#howmuch').val().replace(',', '.').replace(' ', '') + '</td><td>' + $('#comment2').val().replace('\n', '<br>') + '</td></tr>').prependTo('#table-whoworked > tbody').hide().fadeIn();
                        $({alpha:1}).animate({alpha:0}, {
                            duration: 3000,
                            step: function(){
                                tr.css('background-color','rgba(125,175,220,'+this.alpha+')');
                            }
                        });
                        $('#table-whoworked > tbody > tr:gt(4)').fadeOut(300, function(){ $(this).remove(); });
                        $('#howmuch').val('');
                        $('#comment2').val('');
                        $('#action').focus();
                    }
                },
                error: function(xhr, status, errorThrown) {
                    $('#tab1-submit').prop('disabled', false);
                    alert(xhr.status + "\r\n" + xhr.responseText + "\r\n" + status + "\r\n" + errorThrown);
                }
            });
        }

    </script>

    <div id="check-modal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <br/><p class="text-center">Такой товар отсутствует в списке.<br/>Подтверждаете ввод?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default button-no">Нет</button>
                    <button type="button" class="btn btn-primary button-yes">Да</button>
                </div>
            </div>
        </div>
    </div>

    <div id="password-modal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body">
                    <br/><p class="text-center">Введите пароль: <form method="post" action="" id="collector-password-form"><input type="password" id="collector_password" class="form-control" /></form></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default button-cancel">Отмена</button>
                    <button type="button" class="btn btn-primary button-ok">Ok</button>
                </div>
            </div>
        </div>
	  <?php } ?>
	</div>
</body>