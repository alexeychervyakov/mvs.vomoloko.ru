<?php

require_once('db.conf.php');
require_once('loaders/goods_loader.php');

function create_category_string( $groups, $group_id ){
    if( $groups[$group_id]->parent=-1) {
        return $groups[$group_id]->name;
    } else {
        return create_category_string( $groups, $groups[$group_id]->parent ) . ' > ' . $groups[$group_id]->name;
    }
}

$db = new MysqlWrapper();
list($groups,$goods,$last_catalog_update_time) = load_goods($db);


// "Create" the document.
$xml = new DOMDocument( "1.0", "UTF-8" );

// Create some elements. SHOP  https://yandex.ru/support/partnermarket/elements/shop.html
$g_atom_catalog = $xml->createElement( "feed" );
$g_atom_catalog->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
$g_atom_catalog->setAttribute('xmlns:g', 'http://base.google.com/ns/1.0');

$g_atom_catalog->appendChild( $xml->createElement('title', 'Во!Молоко'));

$link_xel = $xml->createElement('link');
$link_xel->setAttribute('rel', 'self');
$link_xel->setAttribute('href', 'https://vomoloko.ru');
$g_atom_catalog->appendChild($link_xel);

$g_atom_catalog->appendChild( $xml->createElement('updated', date_format(new DateTime($last_catalog_update_time),DATE_ATOM)));

//<offers/>
foreach ($goods as $id=>$good){
    $ge = $xml->createElement( "entry");
    $ge->appendChild( $xml->createElement('g:id', $good->article));
    $ge->appendChild( $xml->createElement('g:title', $good->name));

    $de = $xml->createElement('g:description');
    $de->appendChild($xml->createCDATASection($good->description == '' ? '*****' : $good->description));
    $ge->appendChild($de);

    $ge->appendChild( $xml->createElement('g:link', $good->url));
    $ge->appendChild( $xml->createElement('g:brand', $good->vendor));

    $gre = $xml->createElement('g:product_type');
    $gre->appendChild($xml->createCDATASection(create_category_string( $groups, $good->groupId )));
    $ge->appendChild($gre);

    $first_link = true;
    foreach ($good->pictureURL as $imageUrl) {
        $ge->appendChild( $xml->createElement($first_link ? 'g:image_link' : 'additional_image_link', $imageUrl));
        $first_link = false;
    }
    $ge->appendChild( $xml->createElement('g:availability', 'in stock'));
    if( 0<$good->old_price) {
        $ge->appendChild($xml->createElement('g:sale_price', $good->price. ' RUB'));
        $ge->appendChild($xml->createElement('g:price', $good->old_price . ' RUB'));
    } else {
        $ge->appendChild($xml->createElement('g:price', $good->price. ' RUB'));
    }
    /*if($good->unit<>'шт.') {
        $ge->appendChild( $xml->createElement('g:unit_pricing_base_measure', $good->unit ));
    }
    if ($good->min_order_units!=1) {
        $ge->appendChild($xml->createElement('g:unit_pricing_measure', $good->min_order_units.$good->unit));
    }*/
    if( ""<>trim($good->barcode)) $ge->appendChild( $xml->createElement('g:tin', $good->barcode));
    $g_atom_catalog->appendChild($ge);
}
$xml->appendChild($g_atom_catalog);
// Set the content type to be XML, so that the browser will   recognise it as XML.
header( "content-type: application/xml; charset=UTF-8" );
print $xml->saveXML();