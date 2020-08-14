<?php
require_once('auth.php');
include_once ('operations.php');

$response=array();

if (isset($_POST['action_id']) && $_POST['action_id'] != '' ) {

    $shop_id=$_SESSION['shop_id'];
    $shopname=$_SESSION['shopname'];
    $response = array();
    $csv=array();
    $action_id=$_POST['action_id'];

    if ($shop_id === false) {
        echo json_encode(array('result' => 'error'));
        die();
    }

    $dates = date('Ymd', time());
    if (isset($_POST['date']) && $_POST['date'] != '') {
        list($d, $m, $y) = explode('.', $_POST['date']);
        $dates = $y.$m.$d;
    }

    $dbc = new MysqlWrapper();

    $sql = "DELETE FROM retailpointactions WHERE id= " . $action_id;
    $dbc->query($sql);

    $response = get_current_state( $dbc, $shop_id, $shopname, $dates, $actions);

    echo json_encode($response);
    $dbc->disconnect();

} else {
    echo json_encode(array('result' => 'error'));
}