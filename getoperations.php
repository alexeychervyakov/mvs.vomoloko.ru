<?php
require_once('auth.php');
include_once ('operations.php');

$response=array();



if(isset($_SESSION['shop_id'])){
    $shop_id=$_SESSION['shop_id'];
    $shopname=$_SESSION['shopname'];

    $dates = date('Ymd');
    if(isset($_POST['date'])){
        list($d, $m, $y) = explode('.', $_POST['date']);
        $dates = $y.$m.$d;
    }

    $response['result'] = 'ok';

    if(isset($shopname)){
        $db = new MysqlWrapper();

        $response = get_current_state($db, $shop_id, $shopname, $dates, $actions);
        $plan_results = get_plan_results($shopname,$dates,$db);
        if( $plan_results != '' ) {
            $response['plan_results'] = $plan_results;
        }
        $db->disconnect();
    }

	echo json_encode($response);
} else {
	echo json_encode(array('result' => 'error'));
}