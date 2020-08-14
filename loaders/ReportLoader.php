<?php
include_once "../settings.php";

class ReportLoader
{
    public $db;

    public static function get_shops_maps($db){
        $shops_name_map=array(
            "Ярослава Гашека 8_22" => "942043",
            "СПб Парголовская 7" => "89428",
            "Ударников 42" => "2743330",
            "СПб Ленсовета 37" => "92137",
            "Славянка Колпинское ш. 34-1" => "869763",
            "Пушкин Московская 29" => "1248009",
            "Пражская 22" => "962485",
            "Оранжерейная" => "2781521",
            "Колпино Пролетарская 58" => "476386",
            "Боровая 8" => "1671210",
            "Колпино Ижорского Батальона 8" => "276454",
            "Пушкин Железнодорожная 56А" => "80198"
        );

        $sql = "select id, name, address from retailpoints rp where rp.closed is NULL;";
        $data = $db->query($sql);
        $count = 0;
        if (null != $data ){
            while(null!=($row = $data->fetch_assoc())) {
                $shops_name_map[$row['address']] = $row['id'];
                $namea = str_replace(";",'', $row['address'], $count);
                if( $count > 0 ) {
                    $shops_name_map[$namea] = $row['id'];
                }
                $shops_name_map[$row['name']] = $row['id'];
            }
        }
        return $shops_name_map;
    }

    public static function get_store_maps($db){
        $shops_name_map=array(
            "Ярослава Гашека 8_22" => "942043",
            "СПб Парголовская 7" => "89428",
            "Ударников 42" => "2743330",
            "СПб Ленсовета 37" => "92137",
            "Славянка Колпинское ш. 34-1" => "869763",
            "Пушкин Московская 29" => "1248009",
            "Пражская 22" => "962485",
            "Оранжерейная" => "2781521",
            "Колпино Пролетарская 58" => "476386",
            "Боровая 8" => "1671210",
            "Колпино Ижорского Батальона 8" => "276454",
            "Пушкин Железнодорожная 56А" => "80198"
        );

        $sql = "select id, store_id, name, address from retailpoints rp where rp.closed is NULL;";
        $data = $db->query($sql);
        $count = 0;
        if (null != $data ){
            while(null!=($row = $data->fetch_assoc())) {
                $shops_name_map[$row['address']] = $row['id'];
                $namea = str_replace(";",'', $row['address'], $count);
                if( $count > 0 ) {
                    $shops_name_map[$namea] = $row['store_id'];
                }
                $shops_name_map[$row['name']] = $row['store_id'];

                foreach($shops_name_map as $name => $id){
                    if ($id==$row['id']) {
                        $shops_name_map[$name] = $row['store_id'];
                    }
                }
            }
        }

        return $shops_name_map;
    }
    public static function get_report_types_maps(){

        $operations = array(
            '1' => 'Приход',
            '2' => 'Уход',
            '3' => 'Списание',
            '4' => 'Заказ на мороженное',
            '5' => 'Заказ на хозтовары',
            '6' => 'Инвентаризация',
            '7' => 'Заказ ценников',
            '8' => 'Заказ Желтых ценников',
            '9' => 'Заявка к ЗАКАЗУ'
        );

        $operations_map = array();
        foreach ($operations as $id => $name){
            $operations_map[$name] = $id;
        }
        return $operations_map;
    }
}