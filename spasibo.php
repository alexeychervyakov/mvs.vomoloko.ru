<?php

include 'db.conf.php';

function save_feedback($ip, $value)
{
    $db = new MysqlWrapper();


    $sql = "select id,name from retailpoints where closed is null and ipv4='".$ip."'";
    $rest_rslt = $db->query($sql);

    if( null!=($info=$rest_rslt->fetch_assoc()) && array_key_exists('id',$info)) {
        $db->query("insert into retailpointfeedbacks(retailpoint_id, p

oints) values(".$info['id']."," . $value . ")");
        file_put_contents("/tmp/spasibo.log", "SAVE " . $value . " points for '".$info['name']."'\r\n",FILE_APPEND);
        return 0;
    }
    file_put_contents("/tmp/spasibo.log", "NOT FOUND SOURCE OF REQUEST from " . $_SERVER['REMOTE_ADDR'] .' btn:'.$_REQUEST['btn']."\r\n",FILE_APPEND);
    return 1;
}

http_response_code(200 + 301 * save_feedback($_SERVER['REMOTE_ADDR'],$_REQUEST['btn'] ));