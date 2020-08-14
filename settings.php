<?php

static $operations = array(
	'1' => 'Приход',
	'2' => 'Уход',
	'3' => 'Списание',
	'4' => 'Заказ на мороженное',
	'5' => 'Заказ на хозтовары',
	'6' => 'Инвентаризация',
	'7' => 'Заказ ценников',
	'8' => 'Заказ Желтых ценников',
    '9' => 'Заявка к ЗАКАЗУ'
);

$mailfrom = 'marina@vomoloko.ru';

$datadir = dirname(__FILE__).'/files/';

$actions = array(
	'start' => 'Начало смены',
	'end' => 'Конец смены',
	'end_cashless' => 'Сумма по безналу',
	'end_change' => 'Разменых денег',
	'accept' => 'Внесение',
	'withdraw' => 'Снятие кассы',
	'payment' => 'Оплата из кассы',
	'whowork' => 'Кто работает',
	'avans' => 'Аванс',
	'zarplata' => 'Зарплата',
	'premiya' => 'Премия',
    'fine' => 'Штраф',
);

$manager_only_actions = array('avans', 'zarplata');

$reports_password = '6702a8aa0dfabde68da39c8c1cbe0f79';
$manager_password = '6702a8aa0dfabde68da39c8c1cbe0f79';
$withdraw_password = '6702a8aa0dfabde68da39c8c1cbe0f79';

function action_visible($atitle) {
	global $actions, $manager_only_actions;
	if (!isset($_SESSION['manager_authorized']) || $_SESSION['manager_authorized'] !== true) {
		foreach ($actions as $key => $title) {
			if ($title == $atitle && in_array($key, $manager_only_actions)) return false;
		}
	}
	return true;
}
