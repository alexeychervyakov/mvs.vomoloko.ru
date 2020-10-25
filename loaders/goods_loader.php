<?php
require_once('db.conf.php');

class Good {
    public $id;
    public $article;
    public $name;
    public $vendor;
    public $url;
    public $price;
    public $old_price;
    public $currencyId = 'RUR';
    public $groupId;
    public $pictureURL;
    public $delivery = true;
    public $pickup = true;
    public $store = true;
    public $description;
    public $barcode;
    public $weight;
    public $unit;
    public $min_order_units;
};
class GoodGroup {
    public $id;
    public $parent;
    public $name;
};

function load_goods($db){

    $Handle = fopen('/tmp/images.map.txt', 'w');

    $sql = 'select g.id, g.article, g.price, g.old_price, g.weight,
                   c.pagetitle as name, c.parent as groupid, pc.pagetitle as groupname, c.content as descr, c.uri as uri,
                   vg.images,
                   v.name as vendor,
                   b.value as barcode,
                   vg.updated,
                   cv.value as unit_type,
                   cvv.value as min_units,
                   cvvv.value as site_image
            from vomoloko.modx_ms2_products g
                     left join vomoloko.modx_ms2_vendors v on g.vendor=v.id
                     left join vomoloko.modx_site_content c on g.id=c.id
                     left join vm_stat.goods vg on vg.part=g.article
                     left join vm_stat.barcodes b on b.good_id=vg.id
                     left join vomoloko.modx_site_content pc on c.parent=pc.id
                     left join vomoloko.modx_site_tmplvar_contentvalues cv on c.id=cv.contentid and cv.tmplvarid=9
                     left join vomoloko.modx_site_tmplvar_contentvalues cvv on c.id=cvv.contentid and cvv.tmplvarid=20
                     left join vomoloko.modx_site_tmplvar_contentvalues cvvv on c.id=cvvv.contentid and cvvv.tmplvarid=1
            where c.published = 1
            group by g.id';

    $data = $db->query($sql);
    $groups = array();
    $goods = array();
    $last_update = '0000-00-00 00:00:00';
    if (null != $data ){
        while(null!=($row = $data->fetch_assoc())) {
            $next_good = new Good();
            $next_good->id = $row['id'];
            if ($row['barcode'] == null) {
                $next_good->barcode = '';
            } else {
                $next_good->barcode = $row['barcode'];
            }
            $next_good->description = $row['descr'];
            $groupId = $row['groupid'];
            $next_good->groupId = $groupId;
            if(!array_key_exists($groupId,$groups)){
                $ngroup = new GoodGroup();
                $ngroup->id = $groupId;
                $ngroup->name = $row['groupname'];
                $ngroup->parent = -1;
                $groups[$groupId] = $ngroup;
            }
            $next_good->name = $row['name'];
            $next_good->vendor = $row['vendor'];
            $next_good->url = 'https://vomoloko.ru/katalog/?showpopup='. $next_good->id;
            $next_good->price = $row['price'];
            $next_good->old_price = $row['old_price'];
            $next_good->weight = $row['weight'];
            $next_good->article = $row['article'];

            $next_good->unit = "шт.";
            if($row['unit_type']==28) {
                $next_good->unit = "кг.";
                $next_good->description .= '<p><b>Цена указана за 1 '.$next_good->unit.'</b> Конечная цена может незначительно отличаться, она определяется при взвешивании и указывается в чеке и накладной.</p>';
            } else if($row['unit_type']==29) {
                $next_good->unit = "г.";
                $next_good->description .= '<p><b>Цена указана за 1 '.$next_good->unit.'</b> Конечная цена может незначительно отличаться, она определяется при взвешивании и указывается в чеке и накладной.</p>';
            }
            $next_good->min_order_units = 1;
            if($row['min_units']!=null){
                $next_good->min_order_units = $row['min_units'];
            }

            $images = json_decode($row['images']);
            $image_urls = array();
            if( is_array($images)) {
                foreach ($images as $image) {
                    $image_urls[] = 'https://vomoloko.ru/images/'.hash('ripemd160', $image->url).".jpg";
                }
            }
            if (sizeof($image_urls) == 0 ){
                if ($row['site_image'] != null ) {
                    $image_urls[]="https://vomoloko.ru/".$row['site_image'];
                }
            }
            if (sizeof($image_urls) != 0 ){
                $next_good->pictureURL = $image_urls;
                $goods[$next_good->id] = $next_good;
                if ($last_update < $row['updated']) {
                    $last_update = $row['updated'];
                }
            }
        }
    }
    $groups = load_parent_groups($db, $groups);
    fclose($Handle);
    return array($groups, $goods, $last_update);
}
function load_parent_groups( $db, $groups){
    foreach ($groups as $id => &$group ){
        if( $group->parent = -1 ) {
            $sql = "select parent from vomoloko.modx_site_content where id=" . $id;
            $data = $db->query($sql);
            if (null != $data) {
                if (null != ($row = $data->fetch_assoc())) {
                    $parent = $row['parent'];
                    $group->parent = $parent;
                    $groups = load_group_roots($db, $group, $groups);

                }
            }
        }
    }
    return $groups;
}
function load_group_roots($db, $group, $groups){
    if($group->parent != 0) {
        if (!array_key_exists($group->parent, $groups)) {
            $sql = "select pagetitle, parent from vomoloko.modx_site_content where id=" . $group->parent;
            $data = $db->query($sql);
            if (null != $data) {
                if (null != ($row = $data->fetch_assoc())) {
                    $ng = new GoodGroup();
                    $ng->parent = $row['parent'];
                    $ng->name = $row['pagetitle'];
                    $ng->id = $group->parent;
                    $groups[$ng->id] = $ng;
                    $groups = load_group_roots($db, $ng, $groups);
                }
            }
        }
    }
    return $groups;
}

