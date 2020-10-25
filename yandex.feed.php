<?php

require_once('db.conf.php');
require_once('loaders/goods_loader.php');


$db = new MysqlWrapper();
list($groups,$goods,$last_catalog_update_time) = load_goods($db);

// "Create" the document.
$xml = new DOMDocument( "1.0", "UTF-8" );

// Create some elements. SHOP  https://yandex.ru/support/partnermarket/elements/shop.html
$yml_catalog = $xml->createElement( "yml_catalog" );
$yml_catalog->setAttribute('date', date_format(new DateTime($last_catalog_update_time),DATE_ISO8601));

$shop = $xml->createElement( "shop" );
$shop->appendChild( $xml->createElement('name', 'Во!Молоко'));
$shop->appendChild( $xml->createElement('company', 'Во!Молоко'));
$shop->appendChild( $xml->createElement('agency', 'Магазин Вологодскиех Продуктов'));
$shop->appendChild( $xml->createElement('url', 'http://vomoloko.ru'));
$shop->appendChild( $xml->createElement('email', 'marina@vomoloko.ru'));
$currencies = $xml->createElement( "currencies" );
$currency = $xml->createElement( "currency" );
$currency->setAttribute('id','RUR');
$currency->setAttribute('rate','1');
$currencies->appendChild($currency);
$shop->appendChild($currencies);
//<categories>
$categories = $xml->createElement( "categories" );
foreach ($groups as $id=>$group){
    $ge = $xml->createElement( "category", $group->name );
    $ge->setAttribute('id',$group->id);
    $ge->setAttribute('parentId',$group->parent);
    $categories->appendChild($ge);
}
$shop->appendChild($categories);

//<delivery-options>
$delivery_option = $xml->createElement('delivery-options');
$doption = $xml->createElement('option');
$doption->setAttribute('cost','200');
$doption->setAttribute('days','1');
$delivery_option->appendChild($doption);
$shop->appendChild($delivery_option);

//<pickup-options>
$pickup_option = $xml->createElement('pickup-options');
$poption = $xml->createElement('option');
$poption->setAttribute('cost','0');
$poption->setAttribute('days','1');
$pickup_option->appendChild($poption);
$shop->appendChild($pickup_option);

//<offers/>
$offers = $xml->createElement( "offers" );
foreach ($goods as $id=>$good){
    $ge = $xml->createElement( "offer");
    $ge->setAttribute('id',$good->article);
    $ge->appendChild( $xml->createElement('name', $good->name));
    $ge->appendChild( $xml->createElement('vendor', $good->vendor));
    $ge->appendChild( $xml->createElement('url', $good->url));
    $ge->appendChild( $xml->createElement('price', $good->price));
    if( 0<$good->old_price) $ge->appendChild( $xml->createElement('oldprice', $good->old_price));
    $ge->appendChild( $xml->createElement('currencyId', $good->currencyId));
    $ge->appendChild( $xml->createElement('categoryId', $good->groupId));
    foreach ($good->pictureURL as $imageUrl) {
        $ge->appendChild( $xml->createElement('picture', $imageUrl));
    }
    $ge->appendChild( $xml->createElement('delivery', $good->delivery));
    $ge->appendChild( $xml->createElement('pickup', $good->pickup));
    $ge->appendChild( $xml->createElement('store', $good->store));
    if( ""<>trim($good->barcode)) $ge->appendChild( $xml->createElement('barcode', $good->barcode));
    $ge->appendChild( $xml->createElement('weight', $good->weight));
    if ($good->min_order_units!=1) {
        $ge->appendChild($xml->createElement('sales_notes', 'Минимальный заказ '.$good->min_order_units.' '.$good->unit));
    }

    $de = $xml->createElement('description');
    $de->appendChild($xml->createCDATASection($good->description));
    $ge->appendChild($de);
    $offers->appendChild($ge);
}
$shop->appendChild($offers);
$yml_catalog->appendChild($shop);
$xml->appendChild($yml_catalog);

// Set the content type to be XML, so that the browser will   recognise it as XML.
header( "content-type: application/xml; charset=UTF-8" );
print $xml->saveXML();