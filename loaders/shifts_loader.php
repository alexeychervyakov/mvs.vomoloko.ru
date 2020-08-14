<?php

include 'ReportLoader.php';
include_once '../db.conf.php';

$db = new MysqlWrapper();

$shop_names_map = ReportLoader::get_shops_maps($db);

$path="/home/vsftpd/psykarma/www/mvs/files/whoworked";

$file_list = scandir($path);

foreach ($file_list as $file_name){
    $file_info = pathinfo($file_name);
    echo $file_name."\r\n";
    //Колпино Пролетарская 58_2017.12.csv
    if( FALSE === ($res = preg_match('/(.+)_([0-9.]+)\.csv/',$file_name, $matches))){
        echo "ERROR! regexp ".$res."\r\n";

    } else if (!($res === 0)) {
        echo json_encode($matches,JSON_PRETTY_PRINT)."\r\n";
        if( array_key_exists($matches[1],$shop_names_map) ){
            $month=date_parse($matches[2]."27");

            $shop_id = $shop_names_map[$matches[1]];
            if (($in = fopen($path."/".$file_name, "r")) !== FALSE) {
                $line_count = 0;
                while (($data = fgetcsv($in, 1000, ";")) !== FALSE) {
                    //20160722;"22.07.2016 01:54:01";"Ира Сахно";1;
                    if($line_count>0) {
                        if (count($data) > 3) {
                            $marketer = iconv("Windows-1251", "UTF-8", $data[2]);
                            $size = str_replace(",",".",iconv("Windows-1251", "UTF-8", $data[3])) * 12;
                            $date = iconv("Windows-1251", "UTF-8", $data[0]);
                            $db->query("INSERT INTO shifts(retailpoint_id,marketer_id,`hours`,`date`) SELECT " . $shop_id . ", id, " . $size . ", '" . $date . "000000' FROM marketers WHERE name='" . $marketer . "' ON DUPLICATE KEY update hours=".$size);
                        }
                    }
                    $line_count++;
                }
            }
            fclose($in);

        } else {
            echo "ERROR unknown shop ".$matches[1]."\r\n";
        }
    } else {
        echo "REGEXP not matches to '".$file_name."'\r\n";
    }

}

$db->disconnect();
