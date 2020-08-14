<?php
require_once('settings.php');
$datadir = rtrim($datadir, '/').'/';

$response = array(
	'files1' => array(),
	'files2' => array(),
);

if (isset($_POST['shop']) && $_POST['shop'] != '') {
	$shopname = urldecode($_POST['shop']);
	
	$files1 = scandir($datadir.'operations');
	foreach ($files1 as $filename) {
		if (substr($filename, -4) == '.csv' && strpos($filename, $shopname) === 0) {
			$response['files1'][] = str_replace('.csv', '', str_replace($shopname.'_', '', $filename));
		}
	}
	
	$files2 = scandir($datadir.'whoworked');
	foreach ($files2 as $filename) {
		if (substr($filename, -4) == '.csv' && strpos($filename, $shopname) === 0) {
			$response['files2'][] = str_replace('.csv', '', str_replace($shopname.'_', '', $filename));
		}
	}
}

echo json_encode($response);
