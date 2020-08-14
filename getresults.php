<?php
require_once('auth.php');

function load_results($dbc, $dates)
{
    $res = array();
    $data = $dbc->query("select rp.id, rp.name, round(sum(rc.pay_cashless+rc.pay_cash),0) as income, count(distinct rc.id) as checks from retailpoints rp
                            left outer join retailchecks rc on rc.retail_point_id=rp.id
                            where DATE(rc.date)='".$dates."' group by rp.id order by round(sum(rc.pay_cashless+rc.pay_cash),0)/count(distinct rc.id) desc");
    $shop_stat = array();

    if (null != $data ){
        $place = 1;
        while(null!=($row = $data->fetch_assoc())) {
            $id = $row['id'];
            $shop_stat[$id] = array();
            $shop_stat[$id]['name'] = $row['name'];
            $shop_stat[$id]['income'] = $row['income'];
            $shop_stat[$id]['checks'] = $row['checks'];
            if($row['checks']==0) {
                $shop_stat[$id]['avg_checks'] = 0;
            } else {
                $shop_stat[$id]['avg_checks'] = round($row['income']/$row['checks'],0);
            }
            $shop_stat[$id]['av_points'] = '-';
            $shop_stat[$id]['points_cnt'] = 0;
            $shop_stat[$id]['points_line'] = '';
            $shop_stat[$id]['place'] = $place;
            $place = $place + 1;
        }
    }
    $data = $dbc->query("select retailpoint_id as id, rp.name, points, count(*) as cnt, group_concat( DISTINCT TIME(ts) SEPARATOR ' ') as tlist from retailpointfeedbacks fb
                            left outer join retailpoints rp on rp.id=fb.retailpoint_id
                            where `date`='".$dates."' group by retailpoint_id, points ");
    if (null != $data ){
        while(null!=($row = $data->fetch_assoc())) {
            $id = $row['id'];
            if( !array_key_exists($id, $shop_stat)) {
                $shop_stat[$id]=array();
                $shop_stat[$id]['name']=$row['name'];
                $shop_stat[$id]['income'] = '-';
                $shop_stat[$id]['checks'] = '-';
                $shop_stat[$id]['avg_checks'] = '-';
            }
            if( !array_key_exists('av_points', $shop_stat[$id]) || $shop_stat[$id]['av_points'] == '-'){
                $shop_stat[$id]['av_points'] = $row['points'];
                $shop_stat[$id]['points_cnt'] = $row['cnt'];
                $shop_stat[$id]['points_line'] = $row['points']."(".$row['cnt'].")";
                if ($row['points']>2) {
                    $shop_stat[$id]['points_line'] = $row['points']."(".$row['cnt'].")";
                } else {
                    $shop_stat[$id]['points_line'] = $row['points']."(".$row['cnt']. ": " . $row['tlist'] . ")";
                }
            } else {
                $shop_stat[$id]['av_points'] = ($shop_stat[$id]['av_points'] * $shop_stat[$id]['points_cnt'] +
                    $row['points'] * $row['cnt']) / ($shop_stat[$id]['points_cnt']+$row['cnt']);
                $shop_stat[$id]['points_cnt'] = $shop_stat[$id]['points_cnt'] + $row['cnt'];
                if ($row['points']>2) {
                    $shop_stat[$id]['points_line'] = $shop_stat[$id]['points_line'] . ", " . $row['points'] . "(" . $row['cnt'] . ")";
                } else {
                    $shop_stat[$id]['points_line'] = $shop_stat[$id]['points_line'] . ", " . $row['points'] . "(" . $row['cnt'] . ": " . $row['tlist'] . ")";
                }
            }
        }
    }
    $res['shop_stat'] = $shop_stat;
    return $res;
}

$response=array();

$dates = date('Ymd');
if(isset($_POST['date'])){
    list($d, $m, $y) = explode('.', $_POST['date']);
    $dates = $y.$m.$d;
}

$db = new MysqlWrapper();
$response = load_results($db, $dates);
$db->disconnect();
echo json_encode($response);
