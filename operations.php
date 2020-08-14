<?php

function load_actions($dbc, $shop_id, $dates, $actions)
{
    $res = array();
    $csv = array();

    $res['pea'] = 'не известно';
    //load $previousEndAmount
    $data = $dbc->query("SELECT `amount` FROM retailpointactions rpa WHERE rpa.retailpoint_id = " . $shop_id . " AND rpa.date<'" . $dates . "000000' AND `action`='".$actions['end']."' order by `date` DESC limit 1");
    if (null != $data ){
        if(null!=($row = $data->fetch_assoc())) {
            $res['pea'] = $row['amount'];
        }
    }
    $data = $dbc->query("SELECT `date`, `created`,`action`, `amount`,`name`, `comment`, rpa.id as id FROM retailpointactions rpa LEFT JOIN marketers m on m.id = rpa.marketer_id WHERE rpa.retailpoint_id = " . $shop_id . " AND rpa.date between '" . $dates . "000000' AND  '" . $dates . "235959' order by `created`");

    $res['sa'] = '-';
    $res['ea'] = '-';
    if (null != $data ){
        while(null!=($row = $data->fetch_assoc())) {
            if (!action_visible($row['action']))
                $amount = '';
            else
                $amount = $row['amount'];

            if ($row['action'] == $actions['start']) {
                $res['sa'] = $amount;
            } else if ($row['action'] == $actions['end']) {
                $res['ea'] = $amount;
            }
            if ($row['action'] == $actions['fine']) {
                $res['fine'][] = array($row['date'], $row['created'], $row['action'], $amount, $row['name'], $row['comment'], $row['id']);

            } else {
                $csv[] = array($row['date'], $row['created'], $row['action'], $amount, $row['name'], $row['comment'], $row['id']);
            }
        }
    }

    $res['operations'] = $csv;
    return $res;
}

function load_shifts($dbc, $shop_id, $dates)
{

    $res = array();

    if(isset($shop_id)) {
        $data = $dbc->query("SELECT s.date AS `date`, s.id AS `time`, m.name AS `name`, round(s.hours/12,1) AS shifts FROM shifts s LEFT OUTER JOIN marketers m ON s.marketer_id=m.id WHERE s.retailpoint_id=" . $shop_id . " AND s.date BETWEEN '" . $dates . "000000' AND '" . $dates . "235959' ORDER BY s.id DESC LIMIT 5;");
        if (null != $data) {
            while (null != ($row = $data->fetch_assoc())) {
                $res[] = array($row['date'], $row['time'], $row['name'], $row['shifts'], '');
            }
        }
    }
    return $res;
}

function get_avg_check($shop_name,$date,$db){
    $date_day_ago = date_add(clone $date,date_interval_create_from_date_string("1 days ago"));

    $res=0;
    $sql="select sum(rg.sum)/count(DISTINCT rc.id) as sm
            from retailchecks rc
              LEFT JOIN retailpoints rp on rp.id=rc.retail_point_id
              LEFT JOIN  retailcheckgoods rg on rc.id=rg.retail_check_id
              LEFT JOIN goods g on rg.good_id=g.id
            where rp.closed is NULL and 
              rc.date BETWEEN '".date_format($date_day_ago,'Ymd235959')."' AND '".date_format($date,'Ymd235959')."' and
              rp.name='".$shop_name."' AND
              g.group_id <> 95207;";
    $data = $db->query($sql);
    if (null != $data ){
        if(null!=($row = $data->fetch_assoc()) && $row['sm']!=null) {
            $res = $row['sm'];
        }
    }
    return $res;
}
/** на выходе массив
 *
    best_shop, max_avg_check, avg_check, place, bonus, worse_shop, worse_avg_check
 *
 **/

function get_sale_bonus($db, $shop_id, $date){
    $res = array('error'=>'1');
    $end_of_workday='205959';

    if($date > '20191000') {

        $avg_calc = "sum(rcg.sum)/count(distinct rc.id) ";
        if($date < '20191003') {
            $avg_calc = 'sum(rcg.sum)/count(distinct rc.id) ';
        }

        $sql = "select rc.retail_point_id as rp_id, rp.name as name,
           sum(rcg.sum) + ifnull(orders_sum.sold,0) as sold,
           count(distinct rc.id) as checks,
           " . $avg_calc . " as avg_check, rp.bonuses from retailchecks rc
                                                          left outer join retailcheckgoods rcg on rcg.retail_check_id=rc.id
                                                          right join goods g on g.id=rcg.good_id
                                                          right join retailpoints rp on rp.id=rc.retail_point_id
                                                          left outer join (select rc.retail_point_id as rp_id,
                                                                                  sum(rcg.sum) as sold
                                                                           from retailchecks rc
                                                                               left outer join retailcheckgoods rcg on rcg.retail_check_id=rc.id
                                                                               right join goods g on g.id=rcg.good_id
                                                                           where
                                                                               rc.date BETWEEN '" . $date . '000001' . "' AND '" . $date . $end_of_workday . "' and
                                                                                  g.group_id=95207
                                                                           group by rc.retail_point_id
                ) as orders_sum on orders_sum.rp_id = rc.retail_point_id
                where
                    rc.date BETWEEN '" . $date . '000001' . "' AND '" . $date . $end_of_workday . "' and
                        g.group_id<>3141198 AND g.group_id<>95207
                group by rc.retail_point_id
                order by avg_check desc;";

        $data = $db->query($sql);
        $res = array('error' => '1');
        $total_sold = 0;
        if (null != $data) {
            $place = 0;
            $res = array();
            while (null != ($row = $data->fetch_assoc())) {
                $place++;
                if (1 == $place) { //the first one
                    $res['best_shop'] = $row['name'];
                    $res['max_avg_check'] = round($row['avg_check'], 2);
                }
                $total_sold += $row['sold'];
                if ($shop_id == $row['rp_id']) {
                    $res['avg_check'] = round($row['avg_check'], 2);
                    $res['place'] = $place;
                    $res['sold'] = round($row['sold'], 2);
                    $res['bonus_flags'] = $row['bonuses'];

                    if ($res['avg_check'] == $res['max_avg_check']) {
                        $res['place'] = 1;
                        $res['best_shop'] = $row['name'];
                    }
                }
                $res['worse_shop'] = $row['name'];
                $res['worse_avg_check'] = round($row['avg_check'], 2);

            }

            if ($place == 0) {
                $res = array('error' => '2');
            } elseif (!array_key_exists('place', $res)) {
                $res['bonus_percent'] = 0;
                $res['bonus'] = 0;
            } else {
                $percent = 1;
                if ($shop_id==999999) {
                    $percent = 5;
                }
                //премиальный чек всем магазинам первой половины
                if ($res['place'] * 2 <= $place && $res['bonus_flags'] <> 0 ) {
                    $bonus_part = $total_sold / 2 / ($place / 2 + 1) / $place / 25;
                    $bonus_percent = round($bonus_part * ($place / 2 + 1 - $res['place']), 2);
                    $res['bonus_percent'] = round($bonus_percent, 2);
                    $res['bonus'] = round($res['sold'] / 100 * $percent + $bonus_percent, 2);
                } else {
                    $res['bonus_percent'] = '0';
                    $res['bonus'] = round($res['sold'] / 100 * $percent, 2);
                }
            }

        }
    }
    return $res;
}

function get_plan_results($shopname, $dates, $db){

    $date_dt=date_create($dates);
    $product_group_name = 'АТАГ ВЕСОВЫЕ';
    $min_to_sell_amount = get_minimum_amount_to_sale_map($shopname, $date_dt );

    if( $min_to_sell_amount > 0 ) {
        $sold_amount = get_amount_sold($shopname, $product_group_name, $date_dt, $db);

        $plan_results = array();
        $plan_results['penalty'] = 1;
        $plan_results['bonus'] = 0;
        $max_bonus = 100;

        if ($sold_amount >= $min_to_sell_amount * 2) {
            $plan_results['bonus'] = (
                min($max_bonus,
                    floor($sold_amount / $min_to_sell_amount - 1) * 100) + max(0, floor($sold_amount / $min_to_sell_amount - 5) * 50));
            $plan_results['message'] = "План по продаже " . $product_group_name . " перевыполнен на " . round($sold_amount / $min_to_sell_amount * 100 - 100) .
                "%! <br/>Бонус: " . $plan_results['bonus'] . " Рублей!";
        } elseif (round($sold_amount, 3) < round($min_to_sell_amount, 3)) {
            $plan_results['message'] = "Осталось продать: " . round(1000 * ($min_to_sell_amount - $sold_amount), 0) . " грамм " . $product_group_name .
                " <br/>Депримирование 50%";
            $plan_results['penalty'] = 2;
        } elseif (round($sold_amount, 3) == round($min_to_sell_amount, 3)) {
            $plan_results['message'] = "План по продаже " . $product_group_name . " выполнен" .
                "! <br/>Для получения бонуса осталось продать: " . round(1000 * (2 * $min_to_sell_amount - $sold_amount), 0) . " грамм";
        } else {
            $plan_results['message'] = "План по продаже " . $product_group_name . " перевыполнен на " . round($sold_amount / $min_to_sell_amount * 100 - 100) .
                "%! <br/>Для получения бонуса осталось продать: " . round(1000 * (2 * $min_to_sell_amount - $sold_amount), 0) . " грамм";
        }
        return $plan_results;
    }
    return '';
}

/**
 * @param MysqlWrapper $db
 * @param $shop_id
 * @param $shopname
 * @param $dates
 * @param array $actions
 * @return array
 */
function get_current_state( $db, $shop_id, $shopname, $dates, array $actions)
{
    $response = load_actions($db, $shop_id, $dates, $actions);
    $response['whoworked'] = load_shifts($db, $shop_id, $dates);

    $date_dt = date_create($dates);
    $date_week_ago = date_add(clone $date_dt, date_interval_create_from_date_string("7 days ago"));

    $response['nac'] = round(get_avg_check($shopname, $date_dt, $db));
    $response['oac'] = round(get_avg_check($shopname, $date_week_ago, $db));
    //$response['bonus'] = $newAvgCheck > $oldAvgCheck ? $newAvgCheck : 0;
    $response['stat'] = get_sale_bonus($db, $shop_id, $dates);
    $total_fine = 0;
    if (array_key_exists('fine', $response)) {
        foreach ($response['fine'] as $fine) {
            $total_fine += floatval($fine['3']);
        }
        if (array_key_exists('bonus', $response['stat'])) {
            $response['stat']['bonus'] = max(0, $response['stat']['bonus'] - $total_fine);
        }
    }
    $response['total_fine'] = $total_fine;

    $response['bonus_message'] = get_plan_results($shopname, $dates, $db);
    if (false && isset($_SESSION['shop_manager'])) {
        $response['plan_results'] = get_plan_report($dates, $db);
    }
    $response['result'] = 'ok';
    return $response;
}
//$items = update_item($('#item').val(), parseFloat($('#quantity').val().replace(',', '.').replace(' ', '')), editMode);

function get_minimum_amount_to_sale($shop_name,$product_group_name, $date, $db){

    if($date > date_create_from_format('Y-m-d H:i:s','2018-12-10 23:59:59')) {
        error_log ("get_minimum_amount_to_sale: date ".date_format($date,'Ymd235959')." > ".date_format(date_create_from_format('Y-m-d H:i:s','2018-12-10 23:59:59'),'Ymd235959')." \r\n", 3, '/tmp/getopt.log');
        return get_minimum_amount_to_sale_map($shop_name,$date);
    }

    $min_amount = 2;
    $sql="select avg_day_amount, if(avg_day_amount<2,2,avg_day_amount) as min_amount,
              avg_day_sale, if(avg_day_amount<2,2/avg_day_amount*avg_day_sale,avg_day_sale) as min_sale
              from (
              (select rp.name as name,
                round(sum(rcg.sum)/count(distinct rc.dow),2) avg_day_sale,
                round(sum(rcg.amount)/count(distinct rc.dow),2) avg_day_amount
              from retailchecks rc 
                left outer join retailcheckgoods rcg on rc.id=rcg.retail_check_id 
                left join goods g on g.id=rcg.good_id 
                left join retailpoints rp on rc.retail_point_id=rp.id
              where rp.name='".$shop_name."' AND g.group_id=(select id from goodgroups gg where gg.name='".$product_group_name."') and 
                rc.year=YEAR('".date_format($date,'Ymd235959')."')-1 and rc.woy=WEEKOFYEAR('".date_format($date,'Ymd235959')."')) aa);
            ";
    $data = $db->query($sql);
    if (null != $data ){
        if(null!=($row = $data->fetch_assoc()) && $row['min_amount']!=null) {
            $min_amount = $row['min_amount'];
        }
    }
    return $min_amount;
}

function get_minimum_amount_to_sale_map($shop_name, $date)
{
    $dow = date_format($date,'N') - 1;
    $day_scales = [ 1, 2, 1, 2, 1, 2, 1];
    $sales = [
        'Пушкин' => 0,
        'Парголовская' => 0.5,
        'Ленсовета' => 0,
        'КОЛПИНО1' => 0,
        'Колпино2' => 0,
        'Славянка' => 0,
        'Гашека' => 0,
        'Пражская' => 0,
        'Пушкин2' => 0,
        'Дыбенко' => 0,
        'Ударников' => 0,
        'ОРАНЖЕРЕЙНАЯ' => 0,
        'Ленинский' => 0,
        'Ленина 40' => 0,
        'Народная' => 0,
        'Звездная' => 0,
        'Ленсовета 89' => 0,
        'Наличная 31' => 0];

    if(isset($shop_name) && isset($sales[$shop_name])) {
        return $sales[$shop_name] * $day_scales[$dow];
    }
    return -1;
}
//how many sold today
function get_amount_sold($shop_name,$product_group_name, $date,$db){
    $sold=0;
    $sql="select sum(rcg.amount) as sold
              from retailchecks rc
                left outer join retailcheckgoods rcg on rc.id=rcg.retail_check_id
                left join goods g on g.id=rcg.good_id
                left join retailpoints rp on rc.retail_point_id=rp.id                
            where
              rp.name='".$shop_name."' AND g.group_id=(select id from goodgroups gg where gg.name='".$product_group_name."') AND
              rc.year=YEAR('".date_format($date,'Ymd235959')."') AND rc.doy=DAYOFYEAR('".date_format($date,'Ymd235959')."')-1;
            ";
    $data = $db->query($sql);
    if (null != $data ){
        if(null!=($row = $data->fetch_assoc()) && $row['sold']!=null) {
            $sold = $row['sold'];
        }
    }
    return $sold;
}

function get_plan_report($date_str,$db){

    list($d, $m, $y) = explode('.', $date_str);
    $dates = $y.$m.$d;
    $date=date_create($dates);
    $product_group_name = 'АТАГ ВЕСОВЫЕ';

    $sql="select a1.name, min_amount, if(sold is null, 0, sold) as sold, floor(if(sold is null, 0, sold)/min_amount-1) * 200 as bonus, round(if(sold is null, 0, sold)/min_amount*100) as plan from ((
            select avg_day_amount, if(avg_day_amount<2,2,avg_day_amount) as min_amount, name,
              if(avg_day_amount<2,2/avg_day_amount*avg_day_sale,avg_day_sale) as min_sale
            from (
              (select rp.name as name,
                      round(sum(rcg.sum)/count(distinct rc.dow),2) avg_day_sale,
                      round(sum(rcg.amount)/count(distinct rc.dow),2) avg_day_amount
               from retailchecks rc
                 left outer join retailcheckgoods rcg on rc.id=rcg.retail_check_id
                 left join goods g on g.id=rcg.good_id
                 left join retailpoints rp on rc.retail_point_id=rp.id
               where rp.closed is NULL and g.group_id=(select id from goodgroups gg where gg.name='".$product_group_name."') and
                     rc.year=YEAR('".date_format($date,'Ymd235959')."')-1 and rc.woy=WEEKOFYEAR('".date_format($date,'Ymd235959')."') group by rp.name) aa) group by name) as a1)
            left join ((
            
            select sum(rcg.amount)  as sold, if(rp.name='Европейский','Славянка',rp.name) as name
            from retailchecks rc
              left outer join retailcheckgoods rcg on rc.id=rcg.retail_check_id
              left join goods g on g.id=rcg.good_id
              left join retailpoints rp on rc.retail_point_id=rp.id
            where rp.closed is NULL and 
              g.group_id=(select id from goodgroups gg where gg.name='".$product_group_name."') AND
              rc.year=YEAR('".date_format($date,'Ymd235959')."') and rc.doy=DAYOFYEAR('".date_format($date,'Ymd235959')."')-1 group by rp.name) a2)
              on a1.name = a2.name order by plan;";
    $plana=array();
    $data = $db->query($sql);
    if (null != $data ){
        while(null!=($row = $data->fetch_assoc())) {
            $plana[]=$row;
        }
    }
    return $plana;
}
?>