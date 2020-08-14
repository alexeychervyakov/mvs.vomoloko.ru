<?php
/**
 * Created by PhpStorm.
 * User: ale10
 * Date: 16.01.2019
 * Time: 17:04
 */

class ReportGood {
    public $id;
    public $article;
    public $barcode;
    public $name;
    public $amount;
};

class StoreReport {
    public $id;
    public $store_id;
    public $shop_id;
    public $date;
    public $subj_store_id;
    public $subj_store_name;
    public $goods_list;
    public $type;
    public $comment;


    public function __construct($shop_id,$id){
        $this->shop_id=$shop_id;
        $this->id = $id;
        $this->goods_list=array();
    }
};

class ReportManager
{

    public static function delete_item($good_id,$report_id,$db){
        $sql="DELETE FROM storereportgoods where storereport_id=".$report_id." AND id=".$good_id.";";
        $db->query($sql,false);
    }

    public static function get_last_report($store_id, $operation, $db, $dates){


        $sql="SELECT sr.id as report_id, sr.author_store_id as author_id, sr.created as created, rp.id as rid, sr.type as `type`, sr.comment as `comment`, rp.name as subj_store_name 
            from storereports sr
            right JOIN retailpoints rp on rp.store_id=sr.author_store_id
           WHERE sr.id in (SELECT id FROM storereports WHERE rp.closed is NULL and author_store_id=".$store_id." AND sr.type =".$operation." AND `date`='".$dates."000000') AND sr.confirmed=FALSE;";
        $data = $db->query($sql,false);

        if (null != $data && null!=($row=$data->fetch_assoc())){

            $report_id=$row['report_id'];
            $res = new StoreReport($row['rid'], $report_id);
            $res->date=$row['created'];
            $res->subj_store_id=$store_id;
            $res->subj_store_name=$row['subj_store_name'];
            $res->store_id=$row['author_id'];
            $res->type=$row['type'];
            $res->comment=$row['comment'];
            $res->goods_list = array();

            $sql2="SELECT srg.good_id as gid, g.name as good_name, g.part as article, srg.amount as amount, if( b.value is null, '-', b.value) as barcode, srg.id as rgid 
            from storereportgoods srg 
            right join goods as g on srg.good_id=g.id
            left outer join barcodes as b on b.good_id=g.id
           WHERE srg.storereport_id=".$report_id." 
           ORDER by srg.id;";

            $data = $db->query($sql2,false);
            $good = null;
            while(null!=($row=$data->fetch_assoc())) {
                $next_gid = $row['rgid'];
                if($good != null && $good->id == $next_gid){
                    $good->barcode = $good->barcode."<br/>".$row['barcode'];
                } else {
                    $good = new ReportGood();
                    $good->amount = $row['amount'];
                    $good->article = $row['article'];
                    $good->name = $row['good_name'];
                    $good->barcode = $row['barcode'];
                    $good->id = $row['rgid'];
                    $res->goods_list[] = $good;
                }
            }
            return $res;
        } else {
            return null;
        }
    }

    public static function create_store_report($db, $shop_id, $store_id, $subj_store_id, $type, $comment, $dates){
        $res = null;
        if ($subj_store_id==null) {
            $subj_store_idv = 'NULL';
        } else {
            $subj_store_idv = $subj_store_id;
        }

        $sql = "INSERT INTO storereports(`author_store_id`,`subj_store_id`,`type`,`comment`,`date`) VALUES ( 
      ".$store_id.",
      ".$subj_store_idv.",'".$type."','".($comment==null?'':$comment)."','".$dates."000000')";
        $db->query($sql,true);
        $sql2 = "SELECT LAST_INSERT_ID() as `id`";
        $rslt = $db->query($sql2);
        if( null!=($data = $rslt->fetch_assoc())){
            $id = $data['id'];

            $sql = "SELECT sp.`id`, `created`, `subj_store_id`, `author_store_id`, rp.name AS `subj_store_name` FROM storereports sp RIGHT JOIN retailpoints rp ON rp.store_id=subj_store_id
                  WHERE  rp.closed is NULL and sp.id=" . $id;

            $row = $db->query($sql)->fetch_assoc();
            $res = new StoreReport($shop_id, $id);
            $res->comment = $comment;
            $res->type = $type;
            $res->date = $row['created'];
            $res->subj_store_id = $row['subj_store_id'];
            $res->subj_store_name = $row['subj_store_name'];
            $res->store_id = $row['author_store_id'];
            $res->goods_list = array();
        }
        return $res;
    }


    public static function update_item( $db, $shop_id, $store_id, $operation, $subj_shop, $comment,
                                        $item_article, $item_barcode, $item_id, $item, $amount, $update, $dates ){
        $report = self::get_last_report($store_id,$operation,$db, $dates);
        if($report==null){
            $report=self::create_store_report($db, $shop_id, $store_id, $subj_shop, $operation, $comment, $dates);

        } else {
            if ($operation != $report->type || $subj_shop != $report->subj_store_id || $report->comment != $comment) {

                $sql = "UPDATE storereports SET `comment`='".($comment==null?'':$comment)."', `subj_store_id`='".$subj_shop."',`type`='".$operation."'
                 WHERE `id`=".$report->id;
                $db->query($sql,true);
            }
        }
        $found = false;
        $idx = 0;
        foreach ($report->goods_list as $good_line){
            if($good_line->name == $item){
                if($update=="true") {
                    $good_line->amount = $amount;
                } else {
                    $good_line->amount += $amount;
                    $good_line->amount = round($good_line->amount,3);
                }
                $found = true;
                //move the line to the end of the array
                array_splice( $report->goods_list, $idx, 1 );
                $report->goods_list[] = $good_line;

                $sql = "UPDATE storereportgoods SET `amount`=".$good_line->amount." WHERE `id`=".$good_line->id;
                $db->query($sql,true);
            }
            $idx = $idx + 1;
        }
        if(!$found){
            $good_line = new ReportGood();
            $good_line->name = $item;
            $good_line->amount = $amount;
            $good_line->barcode = $item_barcode;
            $good_line->article = $item_article;

            $sql = "INSERT INTO storereportgoods(`good_id`,`storereport_id`,`amount`) VALUES(".$item_id.", ".$report->id.",".$good_line->amount.")";
            $db->query($sql,true);
            $sql = "SELECT LAST_INSERT_ID()";
            $res = $db->query($sql);
            $good_line->id=$res->fetch_row()[0];
            $report->goods_list[] = $good_line;
        }
        return $report;
    }


    public static function close_report($report_id, $subj_shop, $comment, $db){


        $sql = "UPDATE storereports SET comment='".$comment."' AND `subj_store_id`='".$subj_shop."', `confirmed`=1 WHERE id=".$report_id.";";
        $db->query($sql,false);

        $sql = "SELECT sp.`id`, `created`, `subj_store_id`, `author_store_id`, rp.name AS `subj_store_name` FROM storereports sp RIGHT JOIN retailpoints rp ON rp.store_id=subj_store_id
                  WHERE  sp.id=" . $report_id;

        $row = $db->query($sql)->fetch_assoc();
        $res = new StoreReport(0, $report_id);
        $res->comment = $comment;
        $res->type = 1;
        $res->date = $row['created'];
        $res->subj_store_id = $row['subj_store_id'];
        $res->subj_store_name = $row['subj_store_name'];
        $res->store_id = $row['author_store_id'];
        $res->goods_list = array();

        return $res;
    }
}