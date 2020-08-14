<?php

include 'loaders/ReportLoader.php';
include_once 'db.conf.php';

$db = new MysqlWrapper();

$shop_names_map = ReportLoader::get_store_maps($db);
$report_types_map = ReportLoader::get_report_types_maps();

$path="./files/invent";
//$path="C:/tmp/mvs_data/reports";

$file_list = scandir($path);

foreach ($file_list as $file_name){
    $file_info = pathinfo($file_name);
    echo $file_name."\r\n";
    //20190204 Списание СПб Парголовская 7.csv
    if( FALSE === ($res = preg_match('/([0-9]+) (Заказ ценников|Заказ на хозтовары|Заказ на мороженное|Заказ Желтых ценников|[^ ]+) ([^()]+)( \\([0-9]+\\))?\.csv/',$file_name, $matches))){
        echo "ERROR! regexp ".$res."\r\n";

    } else if (!($res === 0)) {
        //echo json_encode($matches,JSON_PRETTY_PRINT)."\r\n";
        $report = $matches[1];
        if( array_key_exists($matches[3],$shop_names_map) ){
            $date=date_parse($matches[1]);
            $report_type = $matches[2];
            if( array_key_exists($report_type,$report_types_map) ){
                $report_type_id = $report_types_map[$report_type];
                $shop_id = $shop_names_map[$matches[3]];
                if (($in = fopen($path."/".$file_name, "r")) !== FALSE) {
                    //create a report
                    $db->query("INSERT INTO `storereports`(author_store_id, subj_store_id, created, confirmed, type, comment, date) 
                          VALUES (" . $shop_id . ",NULL," . $matches[1] . "230000,1,'" . $report_type_id . "',''," . $matches[1] . "230000)");
                    $report_id = $db->get_last_id();
                    if (-1 != $report_id) {
                        $line_count = 0;
                        while (($data = fgetcsv($in, 1000, ";")) !== FALSE) {
                            //Артикул;Код на складе;Название продукта;Количество
                            if ($line_count > 0) {
                                if (count($data) > 3) {
                                    $good_name = iconv("Windows-1251", "UTF-8", $data[2]);
                                    $article = $data[1];

                                    $amount = str_replace(',', ".", $data[3]);
                                    if (is_float($amount) || is_numeric($amount)) {
                                        $sql = "INSERT INTO storereportgoods(`storereport_id`,`good_id`,`amount`) SELECT " . $report_id . ", id, " . $amount . " FROM `goods` WHERE `part`='" . $article . "' ON DUPLICATE KEY UPDATE `amount`=`amount` + " . $amount;
                                        $db->query($sql);
                                        if (-1 == $db->get_last_id()) {
                                            echo "ERROR FAILED TO insert line to a report: SQL='" . $sql . "'\r\n";
                                        }
                                    } else {
                                        echo "ERROR FAILED TO insert line to a report: AMOUNT IS TEXT: '" . $amount . "'\r\n";
                                    }

                                }
                            }
                            $line_count++;
                        }
                        echo "REPORT CREATED: id:".$report_id." store:".$matches[3]."(".$shop_id.") type:".$report_type."(".$report_type_id.") lines:".$line_count."\r\n";
                    } else {
                        echo "ERROR FAILED TO CREATE REPORT!\r\n";
                    }
                } else {
                    echo "Failed to open file '".$path."/".$file_name."'\r\n";
                }
            } else {
                echo "UNKNOWN REPORT TYPE '".$report_type."'\r\n";
            }
            fclose($in);

        } else {
            echo "ERROR unknown shop '".$matches[3]."''\r\n";
        }
    } else {
        echo "REGEXP not matches to '".$file_name."'\r\n";
    }

}

$db->disconnect();
