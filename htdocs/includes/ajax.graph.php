<?php
chdir('..');

if ($_REQUEST['action'] == 'more')
	$ajax = true;

include '../lib/common.php';

$timeframe1 = (!empty($_REQUEST['timeframe'])) ? preg_replace("/[^0-9a-zA-Z]/", "",$_REQUEST['timeframe']) : false;
$timeframe2 = (!empty($_REQUEST['timeframe1'])) ? preg_replace("/[^0-9a-zA-Z]/", "",$_REQUEST['timeframe1']) : false;
$currency1 = (!empty($CFG->currencies[strtoupper($_REQUEST['currency'])])) ? $_REQUEST['currency'] : 'usd';
$first = (!empty($_REQUEST['first'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['first']) : false;
$last = (!empty($_REQUEST['last'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['last']) : false;
$action = $_REQUEST['action'];
$_SESSION['timeframe'] = $timeframe1;

if ($action != 'more')
	API::add('Stats','getHistorical',array($timeframe2,$currency1));

API::add('Transactions','candlesticks',array($timeframe1,$currency1,false,$first,$last));
$query = API::send();

if ($action != 'more') {
	$stats = $query['Stats']['getHistorical']['results'][0];
	$vars = array();
	if ($stats) {
		foreach ($stats as $row) {
			$vars[] = '['.$row['date'].','.$row['price'].']';
		}
	}
	$hist = '['.implode(',', $vars).']';
}
else {
	$hist = '[]';
}

$first_id = 0;
$last_id = 0;

$data = $query['Transactions']['candlesticks']['results'][0];
$data = array_reverse($data);
$vars = array();
if ($data) {
	$c = count($data) - 1;
	$first_id = $data[0]['first_id'];
	$last_id = $data[$c]['last_id'];
	foreach ($data as $row) {
		$vars[] = '['.(strtotime($row['t']) * 1000).','.$row['open'].','.$row['close'].','.$row['low'].','.$row['high'].','.$row['volume'].']';
	}
}
$candles = '['.implode(',', $vars).']';

echo '{"history":'.$hist.',"candles":'.$candles.',"first_id":'.$first_id.',"last_id":'.$last_id.'}';