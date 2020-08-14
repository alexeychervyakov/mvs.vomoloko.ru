<?php

const FRESH_SALE_PERCENT=85;
const DISC_DAYS=2;

include 'db.conf.php';

function get_waste($_fresh_sent_percent)
{
    $db = new MysqlWrapper();
    $fresh_sent_percent = FRESH_SALE_PERCENT;
    if (is_numeric($_fresh_sent_percent)) {
        $fresh_sent_percent = intval($_fresh_sent_percent);
    }

    $sql2 = "select s.name, store_id, DATEDIFF(NOW(),date(sg.`updated`)) as ago,sg.`updated` as last_update, good_id, g.name,  amount from storegoods sg inner join (
        select max(sg.id) as mid, DATE(sg.`updated`) as day_of_rest
        from storegoods sg
        right join goods g on g.id=sg.good_id
        where g.expiration_days > 0 and sg.`updated` > ADDDATE(DATE(NOW()),-g.expiration_days*2)
        group by store_id, good_id, DATE(sg.`updated`)
        order by day_of_rest
    ) as midt on midt.mid=id
    left join stores s on s.id=store_id
    left join goods g on g.id=sg.good_id
        where g.expiration_days > 0
    order by store_id, good_id, last_update;";
    $rest_rslt = $db->query($sql2);

    $rest_array = array();
    $stores = array();

    while( ($info=$rest_rslt->fetch_assoc())){
        $good_id = $info['good_id'];
        $ago = $info['ago'];
        $store_id = $info['store_id'];
        $amount = $info['amount'];

        if(!array_key_exists($store_id, $stores)) {
            $stores[$store_id] = 0;
        };

        if(!array_key_exists($store_id, $rest_array )){
            $rest_array[$store_id] = array();
        }
        if(!array_key_exists($good_id,$rest_array[$store_id])){
            $rest_array[$store_id][$good_id] = array();
        }
        $rest_array[$store_id][$good_id][$ago] = $amount;
    }

    $goods_exp = array();

    $sql1 = "select g.expiration_days as exp,  date(rc.date), DATEDIFF(NOW(),date(rc.date)) as ago, rp.store_id as store_id, g.name, g.id as good_id, sum(IF(rcg.discount_value>20,rcg.amount, 0)) as disc_sale, sum(rcg.amount) as sale from retailchecks rc
        left outer join retailcheckgoods rcg on rcg.retail_check_id=rc.id
        left outer join goods g on g.id=rcg.good_id
        left outer join retailpoints rp on rp.id=rc.retail_point_id
        where rc.date > ADDDATE(DATE(NOW()), -g.expiration_days*2)
        and g.expiration_days > 0
    group by rc.retail_point_id, g.id, rc.year, rc.doy ;";
    $rest_rslt = $db->query($sql1);

    $sale_array = array();
    while( ($info=$rest_rslt->fetch_assoc())){
        $good_id = $info['good_id'];
        $ago = $info['ago'];
        $sale = $info['sale'];
        $disc_sale = $info['disc_sale'];
        $store_id = $info['store_id'];
        $goods_exp[$good_id] = $info['exp'];

        if(!array_key_exists($store_id, $stores)) {
            $stores[$store_id] = 0;
        }

        if(!array_key_exists($store_id, $sale_array )){
            $sale_array[$store_id] = array();
        }
        if(!array_key_exists($good_id,$sale_array[$store_id])){
            $sale_array[$store_id][$good_id] = array();
        }
        $sale_array[$store_id][$good_id][$ago] = array( 'sold' => $sale, 'disc_sale' => $disc_sale, 'fresh_sale' => $sale - $disc_sale );
    }

    $sql = "select DATEDIFF(NOW(),date(o.delivered)) as ago, o.store_id as store_id, g.id as good_id, g.name, sum(og.amount) as amount from orders o
        left outer join ordergoods og on og.order_id = o.id
        left outer join goods g on g.id=og.good_id
        where o.delivered > ADDDATE(DATE(NOW()), -g.expiration_days*2)
          and g.expiration_days > 0
    group by o.store_id, DATE(o.delivered),good_id;";
    $rest_rslt = $db->query($sql);

    $order_array = array();
    while( ($info=$rest_rslt->fetch_assoc())){
        $good_id = $info['good_id'];
        $ago = $info['ago'];
        $amount = $info['amount'];
        $store_id = $info['store_id'];

        if(!array_key_exists($store_id, $stores)) {
            $stores[$store_id] = 0;
        };

        if(!array_key_exists($store_id, $order_array )){
            $order_array[$store_id] = array();
        }
        if(!array_key_exists($good_id,$order_array[$store_id])){
            $order_array[$store_id][$good_id] = array();
        }
        $order_array[$store_id][$good_id][$ago] = $amount;
    }

    $rest_with_exp = array();
    $waist_by_day = array();

    foreach( $stores as $store_id => $zero ){
        $rest_with_exp[$store_id] = array();
        $waist_by_day[$store_id] = array();

        if (array_key_exists($store_id,$rest_array) && !is_null($rest_array[$store_id])) {
            foreach ($rest_array[$store_id] as $good_id => $rest_by_days) {
                if (array_key_exists($good_id, $goods_exp)) {
                    $rest_with_exp[$store_id][$good_id] = array();
                    $waist_by_day[$store_id][$good_id] = array();

                    $day_rests = array(); //array of arrays with amount of goods with date after arrive

                    $exp = $goods_exp[$good_id];
                    $the_last_day = true;

                    for ($day_ago = $exp * 2; $day_ago >= 0; $day_ago--) {
                        if ($the_last_day) {
                            $day_rests = array_fill(0, $exp, 0);
                            $the_last_day = false;
                        } else {
                            $wasted = array_shift($day_rests);
                            if ($wasted > 0) {
                                $waist_by_day[$store_id][$good_id]['wasted'][$day_ago] = $wasted;
                            }

                            //set fresh goods from next order
                            if (array_key_exists($store_id, $order_array) && array_key_exists($good_id, $order_array[$store_id]) && array_key_exists($day_ago, $order_array[$store_id][$good_id])) {
                                $day_rests[] = $order_array[$store_id][$good_id][$day_ago];
                            } else {
                                $day_rests[] = 0;
                            }

                            //take sales into account
                            $cheated_exists = false;
                            if (array_key_exists($store_id, $sale_array) && array_key_exists($good_id, $sale_array[$store_id]) && array_key_exists($day_ago, $sale_array[$store_id][$good_id])) {
                                $fresh_sold = $sale_array[$store_id][$good_id][$day_ago]['fresh_sale'];

                                list($day_rests, $fresh_sold) = process_sale($day_rests, $exp, $fresh_sold, $fresh_sent_percent, true);
                                if ($fresh_sold > 0) {
                                    list($day_rests, $fresh_sold) = process_sale($day_rests, $exp, $fresh_sold, $fresh_sent_percent, false);
                                }
                                //check is sold are implemented
                                /*if ($fresh_sold > 0 and $day_ago < $exp) {
                                    echo "EXCEED SOLD OF PRODUCTS " . $good_id . " DAY AGO " . $day_ago . " STORE: " . $store_id . " BY " . $fresh_sold."\r\n";
                                }*/

                                if ($day_ago < $exp + DISC_DAYS) { //учитываем продажу на скидке только 2 дня
                                    $disc_sold = $sale_array[$store_id][$good_id][$day_ago]['disc_sale'];
                                    list($day_rests, $disc_sold) = process_sale($day_rests, DISC_DAYS, $disc_sold, $fresh_sent_percent, false);
                                    if ($disc_sold > 0) {
                                        $waist_by_day[$store_id][$good_id]['cheated'][$day_ago] = $disc_sold;
                                        //echo "EXCEED SOLD OF WASTE!!!" . $good_id . " DAY AGO " . $day_ago . " STORE: " . $store_id . " BY " . $disc_sold."\r\n";
                                        //@@UNREZONABLE DISCONT
                                        list($day_rests, $disc_sold) = process_sale($day_rests, $exp, $disc_sold, $fresh_sent_percent, false);
                                        if ($disc_sold > 0) {
                                            //echo "TOTAL EXCEED SOLD OF WASTE!!!" . $good_id . " DAY AGO " . $day_ago . " STORE: " . $store_id . " BY " . $disc_sold . "\r\n";
                                        }
                                        $cheated_exists = true;
                                    }
                                }
                            }
                            if ($cheated_exists) {
                                $waist_by_day[$store_id][$good_id]['cheated'][753] = 0;
                            }
                            if ($day_ago < $exp) {
                                $cur_rest = 0;
                                foreach ($day_rests as $rest) {
                                    $cur_rest += $rest;
                                }
                                $rest_in_db = 0;
                                /*if(array_key_exists($day_ago, $rest_by_days)){
                                    $rest_in_db=$rest_by_days[$day_ago];
                                }
                                $delta = $cur_rest - $rest_in_db;*/
                                //echo "DELTA rest=".$delta."\r\n";
                            }
                        }
                    }
                    $waist_by_day[$store_id][$good_id]['freshness'] = array_reverse($day_rests);
                } else {
                    unset($rest_array[$store_id][$good_id]);
                }
            }
        }
    }
    $waist_by_day = load_wasted_by_reports($waist_by_day);

    return json_encode($waist_by_day);
}

function load_wasted_by_reports($waist_by_day){

    $db = new MysqlWrapper();
    $sql = "select sr.author_store_id as store_id,
                   srg.good_id as good_id,
                   DATEDIFF(NOW(),date(sr.date)) as ago,
                   srg.amount as amount
            from storereportgoods srg
                     right join storereports sr on srg.storereport_id = sr.id
                     right join retailpoints rp on rp.store_id = sr.author_store_id
                     right join goods g on g.id = srg.good_id
            where srg.storereport_id in
                  (select id from storereports sr where DATE(sr.date) > ADDDATE(NOW(),-g.expiration_days)  and sr.type = 3)
            GROUP by rp.id, DATE(sr.date),srg.good_id order by store_id, good_id, ago desc;";

    $waist_rslt = $db->query($sql);

    while( ($info=$waist_rslt->fetch_assoc())){
        $good_id = $info['good_id'];
        $ago = $info['ago'];
        $amount = $info['amount'];
        $store_id = $info['store_id'];

        if(!array_key_exists($store_id, $waist_by_day )){
            $waist_by_day[$store_id] = array();
        }
        if(!array_key_exists($good_id,$waist_by_day[$store_id])){
            $waist_by_day[$store_id][$good_id] = array();
        }
        if(!array_key_exists('reported',$waist_by_day[$store_id][$good_id])){
            $waist_by_day[$store_id][$good_id]['reported'] = array();
        }
        $waist_by_day[$store_id][$good_id]['reported'][$ago] = $amount;
    }
    return $waist_by_day;
}

function process_sale( $day_rests, $exp, $sold, $fresh_sent_percent, $care_of_freshness)
{
    $fresh_day = 2;
    for ($day = $exp-1; $day >= 0 && $sold>0; $day--) {
        if ($care_of_freshness && $fresh_day > 0) {
            $real_fresh_sold = $sold * ($fresh_sent_percent / 100);
            $fresh_day--;
        } else { //freshness does not matter
            $real_fresh_sold = $sold;
        }

        if ($day_rests[$day] < $real_fresh_sold) {
            $sold -= $day_rests[$day];
            $day_rests[$day] = 0;
        } else {
            $day_rests[$day] -= $real_fresh_sold;
            $sold -= $real_fresh_sold;
        }
    }
    return array( $day_rests, $sold );
};


echo get_waste($_GET['fresh_sale_percent']);