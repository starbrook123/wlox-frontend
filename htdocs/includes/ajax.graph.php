<?php
chdir('..');

if ($_REQUEST['action'] == 'more')
	$ajax = true;

include '../lib/common.php';

$action = $_REQUEST['action'];
if ($action == 'indicators') {
	if (isset($_REQUEST['sma']) && $_REQUEST['sma'] == 'true')
		$_SESSION['sma'] = 1;
	else if (isset($_REQUEST['sma']) && $_REQUEST['sma'] != 'true')
		$_SESSION['sma'] = false;
	if (!empty($_REQUEST['sma1']))
		$_SESSION['sma1'] = preg_replace("/[^0-9]/", "",$_SESSION['sma1']);
	if (!empty($_REQUEST['sma2']))
		$_SESSION['sma2'] = preg_replace("/[^0-9]/", "",$_SESSION['sma2']);
	if (isset($_REQUEST['ema']) && $_REQUEST['ema'] == 'true')
		$_SESSION['ema'] = true;
	else if (isset($_REQUEST['ema']) && $_REQUEST['ema'] != 'true')
		$_SESSION['ema'] = false;
	if (!empty($_REQUEST['ema1']))
		$_SESSION['ema1'] = preg_replace("/[^0-9]/", "",$_SESSION['ema1']);
	if (!empty($_REQUEST['ema2']))
		$_SESSION['ema2'] = preg_replace("/[^0-9]/", "",$_SESSION['ema2']);

	exit;
}

$timeframe1 = (!empty($_REQUEST['timeframe'])) ? preg_replace("/[^0-9a-zA-Z]/", "",$_REQUEST['timeframe']) : false;
$timeframe2 = (!empty($_REQUEST['timeframe1'])) ? preg_replace("/[^0-9a-zA-Z]/", "",$_REQUEST['timeframe1']) : false;
$currency1 = (!empty($CFG->currencies[strtoupper($_REQUEST['currency'])])) ? $_REQUEST['currency'] : 'usd';
$first = (!empty($_REQUEST['first'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['first']) : false;
$last = (!empty($_REQUEST['last'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['last']) : false;
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
$vars = array();
if ($data) {
	$c = count($data) - 1;
	$first_id = ($data[0]['first_id']) ? $data[0]['first_id'] : $data[0]['id'];
	$last_id = ($data[0]['last_id']) ? $data[0]['last_id'] : $data[$c]['id'];
	
	foreach ($data as $key => $row) {
		if (!($row['t'] > 0) || $key == 's_final' || $key == 'e_final')
			continue;
		
		$vars[] = '['.(strtotime($row['t']) * 1000).','.$row['price'].','.$row['vol'].','.$row['id'].']';
	}
}
$candles = '['.implode(',', $vars).']';

echo '{"history":'.$hist.',"candles":'.$candles.',"first_id":'.$first_id.',"last_id":'.$last_id.'}';