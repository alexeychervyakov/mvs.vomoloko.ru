<?php
session_start();
require_once('db.conf.php');
require_once('settings.php');

$db = false;
$shop_id = false;
$shopname = false;
$store_id = false;

if (isset($_POST['shop_manager'])) {
    $_SESSION['shop_manager'] = $_POST['shop_manager'];
}

if (isset($_POST['manager_password']) && md5($_POST['manager_password'].'VOMOLOKO') == $manager_password) {
    $_SESSION['manager_authorized'] = true;
}

if (isset($_POST['manager_exit'])) {
    $_SESSION['manager_authorized'] = false;
    $_SESSION['shop_id'] = false;
}

/**
 * @param $db
 * @return array
 */
function load_shops($db)
{
    $shops = array();
    $sql = "SELECT id, name, ipv4 as ip, store_id, address from retailpoints WHERE closed is NULL order by name;";
    $data = $db->query($sql);
    if (null != $data) {
        while (null != ($row = $data->fetch_assoc())) {
            $shops[] = array(
                "ip" => $row['ip'],
                "title" => $row['name'],
                "store_id" => $row['store_id'],
                "address" => $row['address'],
                "id" => $row['id']);
        }
    }
    return $shops;
}

while(!isset($_SESSION['shops']) || sizeof($_SESSION['shops'])<1 || sizeof($_SESSION['shops'][0])<4) { //shops are loaded to session
    if($db===false) {
        $db = new MysqlWrapper();
    }
    $shops = load_shops($db);
    $_SESSION['shops'] = $shops;
}
$shops = $_SESSION['shops'];


if (isset($_SESSION['shop_manager'])){
    if(!isset($_SESSION['shopname']) || $_SESSION['shopname'] !=$_SESSION['shop_manager']) {

        foreach ($shops as $shop) {
            if ($shop['title'] == $_SESSION['shop_manager']) {
                $shopname = $shop['title'];
                $shop_id = $shop['id'];
                $store_id = $shop['store_id'];
                $_SESSION['shop_id'] = $shop_id;
                $_SESSION['shopname'] = $shopname;
                $_SESSION['store_id'] = $store_id;
                break;
            }
        }
    } else {
        $shop_id = $_SESSION['shop_id'];
        $shopname = $_SESSION['shopname'];
        $store_id = $_SESSION['store_id'];
    }

} else if(!isset($_SESSION['shop_id']) || !isset($_SESSION['shopname']) || !isset($_SESSION['$store_id'] ) ) { //session authorized
    $ip = $_SERVER['REMOTE_ADDR'];

    foreach ($shops as $shop) {
        if ($shop['ip'] == $ip) {
            $shopname = $shop['title'];
            $shop_id = $shop['id'];
            $store_id = $shop['store_id'];
            $_SESSION['shop_id'] = $shop_id;
            $_SESSION['shopname'] = $shopname;
            $_SESSION['store_id'] = $store_id;
            break;
        }
    }
}

if(!isset($_SESSION['cashiers'])) { //cashier authorized
    if($db===false) {
        $db = new MysqlWrapper();
    }
    $cashiers = array();
    $sql = "SELECT id, name from marketers where retired is NULL order by name;";
    $data = $db->query($sql);
    if (null != $data ){
        while(null!=($row = $data->fetch_assoc())) {
            $cashiers[] = $row['name'];
        }
    }
    $_SESSION['cashiers'] = $cashiers;
} else {
    $cashiers = $_SESSION['cashiers'];
}

if( !($db===false)) {
    $db->disconnect();
}