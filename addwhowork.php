<?php
require_once('auth.php');

if (isset($shop_id) && isset($_POST['cashier']) && $_POST['cashier'] != '' && isset($_POST['howmuch']) && $_POST['howmuch'] != '') {

    $shop_id = $_SESSION['shop_id'];
    $shopname = $_SESSION['shopname'];

    $db = new MysqlWrapper();
    if(isset($shop_id)){
        list($d, $m, $y) = explode('.', $_POST['date']);
        $date = $y.$m.$d;

        $hours = str_replace(",",".",$_POST['howmuch']) * 12;
        $marketer = $_POST['cashier'];

        $db->query("INSERT INTO shifts(retailpoint_id,marketer_id,`hours`,`date`) SELECT " . $shop_id . ", id, " . $hours . ", '" . $date . "000000' FROM marketers WHERE name='" . $marketer . "' ON DUPLICATE KEY update hours=".$hours);

        $db->disconnect();

        echo json_encode(array('result' => 'ok'));
    } else {
        echo json_encode(array('result' => 'error'));
    }
}