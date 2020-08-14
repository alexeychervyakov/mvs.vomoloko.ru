<?php
require_once('settings.php');
require_once('auth.php');
require_once('db.conf.php');
require_once('ReportManager.php');

$report = null;
$dates = date('Ymd', time());
if (isset($_POST['date']) && $_POST['date'] != '') {
    list($d, $m, $y) = explode('.', $_POST['date']);
    $dates = $y.$m.$d;
}
if (isset($_POST['action']) && $_POST['action'] == 'update') {

    $db = new MysqlWrapper();
    $report = ReportManager::update_item($db,
            $_SESSION['shop_id'],
            $_SESSION['store_id'],
            $_POST['operation'],
            $_POST['subj'],
            $_POST['comment'],
            $_POST['item_article'],
            $_POST['item_barcode'],
            $_POST['item_id'],
            $_POST['item'],
            $_POST['amount'],
            $_POST['update'],
        $dates
    );

    $db->disconnect();

} else if (isset($_POST['action']) && $_POST['action'] == 'load') {

    $op_type = $_POST['operation'];
    $db = new MysqlWrapper();
    $report = ReportManager::get_last_report($_SESSION['store_id'],$op_type,$db, $dates);
    $db->disconnect();

} else if (isset($_POST['action']) && $_POST['action'] == 'delete') {

    $good_id = $_POST['good_id'];
    $report_id = $_POST['report_id'];
    $db = new MysqlWrapper();
    $report = ReportManager::delete_item($good_id,$report_id,$db);
    $db->disconnect();
}

if($report==null){
    echo json_encode(new StoreReport(-1,-1));
} else {
    echo json_encode($report);
}