<?php
session_start();
require_once('settings.php');

if (isset($_POST['withdraw_password']) && md5($_POST['withdraw_password'].'VOMOLOKO') == $withdraw_password) {
	$_SESSION['withdraw_authorized'] = true;
}

if (isset($_POST['withdraw_exit'])) {
	$_SESSION['withdraw_authorized'] = false;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<title>Во!Молоко (снятие)</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="css/jquery-ui.min.css" rel="stylesheet">
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/bootstrap-datetimepicker.css" rel="stylesheet">
	<link href="css/style.css" rel="stylesheet">
	<script type="text/javascript" src="js/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
</head>
<body>
	<div class="container-fluid">
		<?php if (!isset($_SESSION['withdraw_authorized']) || $_SESSION['withdraw_authorized'] !== true) { ?>
		<div class="well" style="margin-top:20px;">
			<div class="row">
				<div class="col-sm-12">
					<form action="" method="post" class="form-horizontal">
					<div class="form-group">
						<label for="withdraw_password" class="col-sm-2 control-label">Пароль</label>
						<div class="col-sm-4">
							<input type="password" class="form-control" id="withdraw_password" name="withdraw_password">
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
		<div class="row">
			<div class="col-sm-12 text-right" style="min-width: 300px;margin:20px 0;">
				<form action="" method="post">
					<button type="submit" class="btn btn-primary" name="withdraw_exit">Выход</button>
				</form>
			</div>
		</div>
		<div class="well">
			<div class="row">
				<div class="col-sm-12">
					<form action="" method="post" class="form-horizontal" id="form-withdraw">
						<div class="form-group">
							<label for="shop" class="col-sm-2 control-label">Магазин</label>
							<div class="col-sm-10">
								<select class="form-control" id="shop">
									<option value="">Выберите</option>
									<?php
										foreach ($shops as $shop) {
											echo '<option value="'.$shop['title'].'">'.$shop['title'].'</option>';
										}
									?>
								</select>
							</div>
						</div>
						
						<div class="form-group">
							<label for="amount" class="col-sm-2 control-label">Сумма</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="amount">
							</div>
						</div>
						
						<div class="form-group">
							<div class="col-sm-offset-2 col-sm-10">
								<button type="submit" class="btn btn-primary">Внести</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#shop').change(function() {
				if ($(this).val() != '') {
					$('#amount').focus();
				}
			});
		});
		
		$('#form-withdraw').submit(function(e) {
			e.preventDefault();
			$.ajax({
				url: 'addoperation.php',
				type: 'POST',
				data: {
					action: 'withdraw2',
					cashier: '',
					amount: $('#amount').val().replace('.', ',').replace(' ', ''),
					shop: $('#shop').val()
				},
				success: function(data) {
					var response = $.parseJSON(data);
					if (response.result == 'ok') {
						$('#amount').val('').focus();
						alert('Операция добавлена');
					} else {
						alert('Операция не добавлена');
					}
				}
			});
		});
	</script>
		<?php } ?>
	</div>
</body>