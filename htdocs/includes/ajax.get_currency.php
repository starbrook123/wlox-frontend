<?php
chdir('..');

$ajax = true;
include '../lib/common.php';

$currency1 = (!empty($CFG->currencies[$_REQUEST['currency']])) ? $_REQUEST['currency'] : false;
$c_currency1 = (!empty($CFG->currencies[$_REQUEST['c_currency']])) ? $_REQUEST['c_currency'] : false;

API::add('Orders','getBidAsk',array($c_currency1,$currency1));
API::add('User','getAvailable');
$query = API::send();

$current_bid = $query['Orders']['getBidAsk']['results'][0]['bid'];
$current_ask = $query['Orders']['getBidAsk']['results'][0]['ask'];
$user_available = $query['User']['getAvailable']['results'][0];

$return['currency_info'] = $CFG->currencies[strtoupper($currency1)];
$return['current_bid'] = $current_bid;
$return['current_ask'] = $current_ask;
$return['available_btc'] = (!empty($user_available[$c_currency1])) ? number_format($user_available[$c_currency1],8) : 0;
$return['available_fiat'] = (!empty($user_available[$currency1])) ? number_format($user_available[$currency1],2) : 0;

echo json_encode($return);
