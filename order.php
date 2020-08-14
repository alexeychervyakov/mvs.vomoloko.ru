
<?php
require_once('auth.php');
require_once('ReportManager.php');


$report_id = $_POST['report_id'];
$op_type = $_POST['operation'];
$subj_shop = $_POST['subj'];
$comment =  $_POST['comment'];

if ($shopname === false || $report_id == null || $report_id==''){
    echo json_encode(0);
    exit;
}

$db = new MysqlWrapper();
$report = ReportManager::close_report($report_id,$subj_shop, $comment, $db);
$db->disconnect();

$dates = date('Ymd', time());
if (isset($_POST['date']) && $_POST['date'] != '') {
    list($d, $m, $y) = explode('.', $_POST['date']);
    $dates = $y.$m.$d;
}

$filename = $dates.' '.$operations[$op_type].' '.$shopname;
$num = '';
if (file_exists($datadir.$filename.'.csv')) {
    $num = 2;
    while (file_exists($datadir.$filename.' ('.$num.').csv')) {
        $num++;
    }
}

if ($num == '') $filename = $datadir.$filename.'.csv'; else $filename = $datadir.$filename.' ('.$num.').csv';

$fp = fopen($filename, 'w');
$row = "Артикул;Код на складе;Наименование товара;Количество";
fwrite($fp, $row."\r\n");

foreach ($_POST['items'] as $item) {
    $row = $item['code'].';'.$item['code'].';'.$item['title'].';'.$item['quantity'];
    $row = iconv('utf-8', 'windows-1251', $row);
    fwrite($fp, $row."\r\n");
}
fclose($fp);

$mailto = array();
$rows = file('mailto.txt');
foreach ($rows as $row) {
    $row = trim($row);
    if ($row == '') continue;
    $mailto[] = $row;
}
if (count($mailto) > 0) {
    $subject = $shopname.' '.$operations[$op_type].' '.$_POST['date'];
    if ($report->subj_store_name != '') {
        if(1 == $op_type){
            $subject = $subject." <-- ".$report->subj_store_name;
        } else if(2 == $op_type){
            $subject = $subject." --> ".$report->subj_store_name;
        }
    }
    $message = trim($_POST['comment']);
    if ($message == '') $message = 'Без комментариев';

    $filecontent = file_get_contents($filename);
    $filecontent = chunk_split(base64_encode($filecontent));
    $separator = md5(time());

    $headers = "From: ".$mailfrom." <".$mailfrom.">\r\n";

    if (count($mailto) > 1) {
        $bcc = $mailto;
        array_shift($bcc);
        $headers .= 'BCC: ' . implode(', ', $bcc) . "\r\n";
    }

    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"\r\n\r\n";

    $nmessage = "--" . $separator . "\r\n";
    $nmessage .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
    $nmessage .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $nmessage .= $message . "\r\n\r\n";

    $nmessage .= "--" . $separator . "\r\n";
    $nmessage .= "Content-Type: application/octet-stream; name=\"=?utf-8?B?" . base64_encode(basename($filename)) . "?=\"\r\n";
    $nmessage .= "Content-Transfer-Encoding: base64\r\n";
    $nmessage .= "Content-Disposition: attachment\r\n\r\n";
    $nmessage .= $filecontent . "\r\n\r\n";
    $nmessage .= "--" . $separator . "--";

    mail($mailto[0], $subject, $nmessage, $headers);
}
echo json_encode(1);
exit;