<?php

const FRESH_SALE_PERCENT = 85;
const DISC_DAYS = 2;

const DB_SERVER = 'localhost';
const DB_NAME = 'vomoloko';
const DB_USER = 'vomoloko';
const DB_PASSWORD = 'v0m0!0k0PSWD';
const DB_PORT = 3306;

include 'db.conf.php';

function get_orders($delivery_date)
{
    $db = new MysqlWrapper(/*DB_SERVER, DB_NAME, DB_USER, DB_PASSWORD, DB_PORT*/);

    //Load all orders
    $rest_rslt = $db->query('select o.id as int_order_id,o.num as order_id, o.createdon as created, cost as cost, delivery_cost as delivery_cost, weight AS weight, delivery as delivery, o.comment as comment, o.properties as properties, a.user_id as user_id, receiver as receiver, a.phone as phone, a.city as city, metro as location, a.street as street, a.building as building, a.room as room, a.comment as delivery_comment, a.properties as delivery_properties, username as user_name, ua.email as email, ua.fullname as full_user_name 
            from vomoloko.modx_ms2_orders o left join vomoloko.modx_ms2_order_addresses a on a.id=o.id 
            left join vomoloko.modx_users u on u.id=a.user_id left join vomoloko.modx_user_attributes ua on ua.id=u.id order by o.id desc limit 1000;');
    $orders_array = array();

    while (($info = $rest_rslt->fetch_assoc())) {
        $order = array();
        $order_delivery_date = 0;
        if ( array_key_exists('delivery_properties', $info) &&
            null != ($delivery_props = json_decode($info['delivery_properties'], true)) &&
            array_key_exists('shk_delivery_date',
                $delivery_props)) {
            $order_delivery_date = $delivery_props['shk_delivery_date'];
        }
        if ($order_delivery_date == $delivery_date) {
            //lets load the order
            $order['int_order_id'] = $info['int_order_id'];
            //check id the order is not empty one

            $sql = 'select products_sets.product_id,
                   products_sets.part_of_basket,
                   p.article                                    as `article`,
                   sum(op.count * products_sets.product_amount) as `amount`,
                   if(set_article=p.article, 0,set_article) as set_article,
                   op.count as set_count,
                   op.id as order_line_id
            from vomoloko.modx_ms2_order_products op
                     left outer join (
                select p.id                                    as set_id,
                       p.article                               as set_article,
                       IFNULL(sp.product_id, p.id)                product_id,
                       IFNULL(sp.product_amount, 1)            as product_amount,
                       IF(sp.product_amount is  NULL, 0, 1) as part_of_basket
                from vomoloko.modx_ms2_products p
                         left outer join vomoloko.set_products sp on sp.set_id = p.id
                        ) products_sets on products_sets.set_id = op.product_id
                                 left join vomoloko.modx_ms2_products p on p.id = products_sets.product_id
                where order_id = ' . $order['int_order_id'] . '
                group by products_sets.product_id, products_sets.part_of_basket, p.article;';
            $order_rslt = $db->query($sql);

            $line_count = 0;
            $order['lines'] = array();
            $order['sets'] = array();
            while (($order_line = $order_rslt->fetch_assoc())) {
                $next_line = array();
                $article = $order_line['article'];
                if (array_key_exists($article, $order['lines'])) {
                    $next_line = $order['lines'][$article];
                } else {
                    $next_line['amount'] = 0;
                    $next_line['part_of_basket'] = 0;
                    $order['lines'][$article] = $next_line;
                }
                if( 1==$order_line['part_of_basket'] ) {
                    $next_line['part_of_basket'] = $next_line['part_of_basket'] + $order_line['amount'];
                }
                $next_line['amount'] = $next_line['amount'] + $order_line['amount'];
                $order['lines'][$article] = $next_line;
                $line_count++;

                if ($order_line['set_article'] != 0 ){

                    $order_line_id = $order_line['order_line_id'];
                    if (!array_key_exists($order_line_id, $order['sets'])) {
                        $order_set = array();
                        $order_set['set_count'] = $order_line['set_count'];
                        $order_set['set_article'] = $order_line['set_article'];
                        $order['sets'][$order_line_id] = $order_set;
                    }
                }
            }
            if ($line_count > 0) {
                //номер заказа
                //номер клиента
                //тип доставки
                //адрес доставки
                //стоимость дост.
                //итого
                //комент к заказу
                //телефон в заказе
                $order['receiver'] = $info['receiver'] . ':' . $info['email'];
                $order['order_no'] = $info['order_id'];
                $order['user_id'] = $info['user_id'];
                if (1 == $info['delivery']) {
                    $order['delivery_type'] = 'SELF_PICKUP';
                    $order['delivery_address'] = $info['location'];
                } else {
                    $order['delivery_type'] = 'LONG_RANGE';
                    $order['delivery_address'] = $info['location'] . ', ' . $info['street'] . ', ' . $info['building'] . ', ' . $info['room'];
                }
                if (array_key_exists('delivery_cost', $info))
                    $order['delivery_cost'] = $info['delivery_cost'];
                else
                    $order['delivery_cost'] = 0;
                $order['comment'] = '[1]' . $info['delivery_comment'] . '[2]' . $info['comment'];
                $order['phone'] = $info['phone'];
                $order['cost'] = $info['cost'];
                $orders_array[$order['order_no']] = $order;
            }
        }
    }
    return json_encode($orders_array);
}

echo get_orders($_GET['delivery_date']);