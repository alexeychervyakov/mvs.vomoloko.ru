<?php
require_once('settings.php');
$datadir = rtrim($datadir, '/').'/';

if (isset($_POST['fileid']) && $_POST['fileid'] != '' && isset($_POST['shop']) && $_POST['shop'] != '' && isset($_POST['rtype']) && $_POST['rtype'] != '') {
	$filename = $datadir.$_POST['rtype'].'/'.urldecode($_POST['shop']).'_'.$_POST['fileid'].'.csv';
	if (file_exists($filename)) {
		header('Content-Description: File Transfer');
	    header('Content-Type: application/octet-stream');
	    header('Content-Disposition: attachment; filename="'.basename($filename).'"');
	    header('Expires: 0');
	    header('Cache-Control: must-revalidate');
	    header('Pragma: public');
	    header('Content-Length: ' . filesize($filename));
	    readfile($filename);
	}
}
exit;