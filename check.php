<?php
header('Content-Type: application/json;charset=utf-8');
include 'db.conf.php';

function _strtolower($string) {
    $small = array('à','á','â','ã','ä','å','¸','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','÷','ö','ø','ù','ý','þ','ÿ','û','ú','ü','ý', 'þ', 'ÿ');
    $large = array('À','Á','Â','Ã','Ä','Å','¨','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','×','Ö','Ø','Ù','Ý','Þ','ß','Û','Ú','Ü','Ý', 'Þ', 'ß');
    return strtolower(str_replace($large, $small, $string));
}

$result = array('status' => '0');
if (isset($_POST['query']) && trim($_POST['query']) != '') {

    $query = $_POST['query'];

    $sql = "SELECT name, part, g.id as gid, b.value as barcode from goods g LEFT OUTER JOIN barcodes b on g.id = b.good_id where  g.group_id not in (1426751,95207,3141198) AND (LOWER(g.name) like '%".$query."%' OR g.part like '".$query."%' OR value='".$query."') group by name;";

    $db = new MysqlWrapper();
    $data = $db->query($sql);
    if (null != $data ){
        if( null!=($row = $data->fetch_assoc())){
            $result['status'] = '2';
            $result['item'] = array(
                'value' => $row['name'],
                'data' => array(
                    $row['part'],
                    $row['barcode'] == "null" ? "" : $row['barcode'],
                    $row['gid']
                )
            );
        }
    }
    $db->disconnect();
}

echo json_encode($result);
