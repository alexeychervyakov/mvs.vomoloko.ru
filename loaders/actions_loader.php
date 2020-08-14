<?php

include 'ReportLoader.php';
include_once '../db.conf.php';

$db = new MysqlWrapper();


$shop_names_map = ReportLoader::get_shops_maps($db);

//$path="/home/vsftpd/psykarma/www/mvs/files/operations";
$path="C:/tmp/mvs_data/operations/";

$file_list = scandir($path);

foreach ($file_list as $file_name){
    $file_info = pathinfo($file_name);
    echo $file_name;
    //Колпино Пролетарская 58_2017.12.csv
    if( FALSE === ($res = preg_match('/(.+)_([0-9.]+)\.csv/',$file_name, $matches))){
        echo "ERROR! regexp ".$res."\r\n";

    } else if (!($res === 0)) {
        if( array_key_exists($matches[1],$shop_names_map) ){
            $month=date_parse($matches[2]."27");

            $shop_id = $shop_names_map[$matches[1]];
            if (($in = fopen($path."/".$file_name, "r")) !== FALSE) {
                $line_count = 0;
                while (($data = fgetcsv($in, 1000, ";")) !== FALSE) {
                    //20171206;"06.12.2017 06:37:17";"Начало смены";92613,2;"Оксана Сташкова";
                    if($line_count>0) {
                        if (count($data) > 4) {
                            $date = iconv("Windows-1251", "UTF-8", $data[0]);
                            $ts = DateTime::createFromFormat("d.m.Y H:i:s", iconv("Windows-1251", "UTF-8", $data[1]));
                            $times = $ts->format("Y-m-d H:i:s");
                            $action = iconv("Windows-1251", "UTF-8", $data[2]);
                            $size = str_replace(",",".",iconv("Windows-1251", "UTF-8", $data[3]));
                            $marketer = iconv("Windows-1251", "UTF-8", $data[4]);
                            $comment = iconv("Windows-1251", "UTF-8", $data[5]);
                            $db->query("INSERT INTO retailpointactions(retailpoint_id, marketer_id,`amount`,`action`,`date`,`created`,`comment`) SELECT " . $shop_id . ", id, " . $size . ", '".$action."','" . $date . "000000','".$times."','".$comment."' FROM marketers WHERE name='" . $marketer . "' ON DUPLICATE KEY update amount=".$size);
                        }
                    }
                    $line_count++;
                    echo ".";
                }
            }
            fclose($in);
            echo "\r\n   ".$line_count." lines loaded from file: ".$file_name."\r\n";

        } else {
            echo "ERROR unknown shop ".$matches[1]."\r\n";
        }
    } else {
        echo "REGEXP not matches to '".$file_name."'\r\n";
    }

}

$db->disconnect();
