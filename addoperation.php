<?php
require_once('auth.php');
include_once ('operations.php');

if (isset($_POST['action']) && $_POST['action'] != '' && ((isset($_POST['cashier']) && $_POST['cashier'] != '') || $_POST['action'] == 'withdraw2') && isset($_POST['amount']) && $_POST['amount'] != '') {

    $shop_id=$_SESSION['shop_id'];
    $shopname=$_SESSION['shopname'];

    $response = array();
    $csv=array();

    if ($shop_id === false) {
        echo json_encode(array('result' => 'error'));
        die();
    }
    if ($_POST['action'] == 'withdraw') {
        if (!isset($_POST['password']) || md5($_POST['password'].'VOMOLOKO') != $collector_password) {
            echo json_encode(array('result' => 'wrong_password'));
            die();
        }
    }
    if ($_POST['action'] == 'withdraw2') {
        $_POST['action'] = 'withdraw';
    }

    $dates = date('Ymd', time());
    if (isset($_POST['date']) && $_POST['date'] != '') {
        list($d, $m, $y) = explode('.', $_POST['date']);
        $dates = $y.$m.$d;
    }

    $dbc = new MysqlWrapper();

    $times = date("YmdHis");
    $action = $actions[$_POST['action']];
    $size = str_replace(",",".",$_POST['amount']);
    $marketer = $_POST['cashier'];
    $comment = str_replace("\n", "",str_replace("\r", "", $_POST['comment']));

    $sql = "INSERT INTO retailpointactions(retailpoint_id,marketer_id,`amount`,`action`,`date`,`created`,`comment`) SELECT " . $shop_id . ", id, " . $size . ", '".$action."','" . $dates . "','".$times."','".$comment."' FROM marketers WHERE name='" . $marketer . "' ON DUPLICATE KEY update amount=".$size;
    $dbc->query($sql);

    $response=  get_current_state( $dbc, $shop_id, $shopname, $dates, $actions);

    $dbc->disconnect();

    echo json_encode($response);
} else {
    echo json_encode(array('result' => 'error'));
}