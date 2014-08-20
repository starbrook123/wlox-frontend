<?php
include '../cfg/cfg.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

if ($_REQUEST['currency'])
	$_SESSION['currency'] = ereg_replace("[^a-z]", "",$_REQUEST['currency']);
elseif (!$_SESSION['currency'])
	$_SESSION['currency'] = (User::$info['default_currency_abbr']) ? strtolower(User::$info['default_currency_abbr']) : 'usd';

$currency1 = ereg_replace("[^a-z]", "",$_SESSION['currency']);
$currency_info = $CFG->currencies[strtoupper($currency1)];
$confirmed = $_REQUEST['confirmed'];
$cancel = $_REQUEST['cancel'];
$bypass = $_REQUEST['bypass'];
$buy_market_price1 = 0;
$sell_market_price1 = 0;
$buy_limit = 1;
$sell_limit = 1;

API::add('FeeSchedule','getRecord',array(User::$info['fee_schedule']));
API::add('User','getAvailable');
API::add('Orders','getCurrentBid',array($currency1));
API::add('Orders','getCurrentAsk',array($currency1));
API::add('Orders','get',array(false,false,10,$currency1,false,false,1));
API::add('Orders','get',array(false,false,10,$currency1,false,false,false,false,1));
API::add('BankAccounts','get',array($currency_info['id']));

if (time() < strtotime('2014-09-6 00:00:00'))
	API::add('Competition','getUserRank');

if ($_REQUEST['buy'] && !$_REQUEST['buy_market_price']) {
	API::add('Orders','checkOutbidSelf',array($_REQUEST['buy_price'],$currency1));
	API::add('Orders','checkOutbidStops',array($_REQUEST['buy_price'],$currency1));
}
elseif ($_REQUEST['sell'] && !$_REQUEST['sell_market_price']) {
	API::add('Orders','checkOutbidSelf',array($_REQUEST['sell_price'],$currency1,1));
	API::add('Orders','checkStopsOverBid',array($_REQUEST['sell_stop_price'],$currency1));
}

$query = API::send();

$user_fee_both = $query['FeeSchedule']['getRecord']['results'][0];
$user_available = $query['User']['getAvailable']['results'][0];
$current_bid = $query['Orders']['getCurrentBid']['results'][0];
$current_ask =  $query['Orders']['getCurrentAsk']['results'][0];
$bids = $query['Orders']['get']['results'][0];
$asks = $query['Orders']['get']['results'][1];
$self_orders = $query['Orders']['checkOutbidSelf']['results'][0][0]['price'];
$self_stops = $query['Orders']['checkOutbidStops']['results'][0][0]['price'];
$self_limits = $query['Orders']['checkStopsOverBid']['results'][0][0]['price'];
$self_orders_currency = $query['Orders']['checkOutbidSelf']['results'][0][0]['currency'];
$self_stops_currency = $query['Orders']['checkOutbidStops']['results'][0][0]['currency'];
$self_limits_currency = $query['Orders']['checkStopsOverBid']['results'][0][0]['currency'];
$user_fee_bid = (($_REQUEST['buy_amount'] > 0 && $_REQUEST['buy_price'] >= $asks[0]['btc_price']) || $_REQUEST['buy_market_price'] || !$_REQUEST['buy_amount']) ? $query['FeeSchedule']['getRecord']['results'][0]['fee'] : $query['FeeSchedule']['getRecord']['results'][0]['fee1'];
$user_fee_ask = (($_REQUEST['sell_amount'] > 0 && $_REQUEST['sell_price'] <= $bids[0]['btc_price']) || $_REQUEST['sell_market_price'] || !$_REQUEST['sell_amount']) ? $query['FeeSchedule']['getRecord']['results'][0]['fee'] : $query['FeeSchedule']['getRecord']['results'][0]['fee1'];
$user_rank = $query['Competition']['getUserRank']['results'][0];


$buy_amount1 = ($_REQUEST['buy_amount'] > 0) ? ereg_replace("[^0-9.]", "",$_REQUEST['buy_amount']) : 0;
$buy_price1 = ($_REQUEST['buy_price'] > 0) ? ereg_replace("[^0-9.]", "",$_REQUEST['buy_price']) : $current_ask;
$buy_subtotal1 = $buy_amount1 * $buy_price1;
$buy_fee_amount1 = ($user_fee_bid * 0.01) * $buy_subtotal1;
$buy_total1 = $buy_subtotal1 + $buy_fee_amount1;

$sell_amount1 = ($_REQUEST['sell_amount'] > 0) ? ereg_replace("[^0-9.]", "",$_REQUEST['sell_amount']) : 0;
$sell_price1 = ($_REQUEST['sell_price'] > 0) ? ereg_replace("[^0-9.]", "",$_REQUEST['sell_price']) : $current_bid;
$sell_subtotal1 = $sell_amount1 * $sell_price1;
$sell_fee_amount1 = ($user_fee_ask * 0.01) * $sell_subtotal1;
$sell_total1 = $sell_subtotal1 - $sell_fee_amount1;

if ($_REQUEST['buy']) {
	$buy_market_price1 = ereg_replace("[^0-9]", "",$_REQUEST['buy_market_price']);
	$buy_stop = ereg_replace("[^0-9]", "",$_REQUEST['buy_stop']);
	$buy_stop_price1 = ($buy_stop) ? ereg_replace("[^0-9.]", "",$_REQUEST['buy_stop_price']) : false;
	$buy_limit = ereg_replace("[^0-9]", "",$_REQUEST['buy_limit']);
	$buy_limit = (!$buy_stop && !$buy_market_price1) ? 1 : $buy_limit;

	if (!($buy_amount1 > 0))
		Errors::add(Lang::string('buy-errors-no-amount'));
	if (!($_REQUEST['buy_price'] > 0) && ($buy_limit || $buy_market_price1))
		Errors::add(Lang::string('buy-errors-no-price'));
	if (!$currency1)
		Errors::add(Lang::string('buy-errors-no-currency'));
	if ($buy_total1 > $user_available[strtoupper($currency1)])
		Errors::add(Lang::string('buy-errors-balance-too-low'));
	if (!$asks && $buy_market_price1)
		Errors::add(Lang::string('buy-errors-no-compatible'));
	if (($buy_subtotal1 * $currency_info['usd_ask']) < $CFG->orders_min_usd && $buy_amount1 > 0)
		Errors::add(str_replace('[amount]',number_format(($CFG->orders_min_usd/$currency_info['usd_ask']),2),str_replace('[fa_symbol]',$currency_info['fa_symbol'],Lang::string('buy-errors-too-little'))));
	if ($self_orders)
		Errors::add(Lang::string('buy-errors-outbid-self').(($currency_info['id'] != $self_orders_currency) ? str_replace('[price]',$currency_info['fa_symbol'].number_format($self_orders,2),' '.Lang::string('limit-max-price')) : ''));
	if ($buy_stop_price1 <= $current_ask && $buy_stop)
		Errors::add(Lang::string('buy-stop-lower-ask'));
	if ($buy_stop_price1 <= $buy_price1 && $buy_stop && $buy_limit)
		Errors::add(Lang::string('buy-stop-lower-price'));
	if ($buy_stop && !($buy_stop_price1 > 0))
		Errors::add(Lang::string('buy-errors-no-stop'));
	if ($buy_price1 < ($current_ask - ($current_ask * (0.01 * $CFG->orders_under_market_percent))))
		Errors::add(str_replace('[percent]',$CFG->orders_under_market_percent,Lang::string('buy-errors-under-market')));
	if ($self_stops)
		Errors::add(Lang::string('buy-limit-under-stops').(($currency_info['id'] != $self_stops_currency) ? str_replace('[price]',$currency_info['fa_symbol'].number_format($self_stops,2),' '.Lang::string('limit-min-price')) : ''));
	if (time() < strtotime('2014-08-20 11:20:00'))
		Errors::add(Lang::string('competition-feature-before-start'));
	
	if (!is_array(Errors::$errors) && !$cancel) {
		if ($confirmed) {
			$buy_price1 = ($buy_stop && !$buy_limit) ? $buy_stop_price1 : $buy_price1;
			API::add('Orders','executeOrder',array(1,$buy_price1,$buy_amount1,$currency1,$user_fee_bid,$buy_market_price1,false,false,false,$buy_stop_price1));
			$query = API::send();
			$operations = $query['Orders']['executeOrder']['results'][0];

			if ($operations['new_order'] > 0) {
				Link::redirect('open-orders.php',array('transactions'=>$operations['transactions'],'new_order'=>1));
				exit;
			}
			else {
				Link::redirect('transactions.php',array('transactions'=>$operations['transactions']));
				exit;
			}
		}
		else {
			$ask_confirm = true;
		}
	}
}

if ($_REQUEST['sell']) {
	$sell_market_price1 = ereg_replace("[^0-9]", "",$_REQUEST['sell_market_price']);
	$sell_stop = ereg_replace("[^0-9]", "",$_REQUEST['sell_stop']);
	$sell_stop_price1 = ($sell_stop) ? ereg_replace("[^0-9.]", "",$_REQUEST['sell_stop_price']) : false;
	$sell_limit = ereg_replace("[^0-9]", "",$_REQUEST['sell_limit']);
	$sell_limit = (!$sell_stop && !$sell_market_price1) ? 1 : $sell_limit;
	
	if (!($sell_amount1 > 0))
		Errors::add(Lang::string('sell-errors-no-amount'));
	if (!($_REQUEST['sell_price'] > 0) && ($sell_limit || $sell_market_price1))
		Errors::add(Lang::string('sell-errors-no-price'));
	if (!$currency1)
		Errors::add(Lang::string('buy-errors-no-currency'));
	if ($sell_amount1 > $user_available['BTC'])
		Errors::add(Lang::string('sell-errors-balance-too-low'));
	if (!$bids && $buy_market_price1)
		Errors::add(Lang::string('buy-errors-no-compatible'));
	if (($sell_subtotal1 * $currency_info['usd_ask']) < $CFG->orders_min_usd && $sell_amount1 > 0)
		Errors::add(str_replace('[amount]',number_format(($CFG->orders_min_usd/$currency_info['usd_ask']),2),str_replace('[fa_symbol]',$currency_info['fa_symbol'],Lang::string('buy-errors-too-little'))));
	if ($self_orders)
		Errors::add(Lang::string('buy-errors-outbid-self').(($currency_info['id'] != $self_orders_currency) ? str_replace('[price]',$currency_info['fa_symbol'].number_format($self_orders,2),' '.Lang::string('limit-min-price')) : ''));
	if ($sell_stop_price1 >= $current_bid && $sell_stop)
		Errors::add(Lang::string('sell-stop-higher-bid'));
	if ($sell_stop_price1 >= $sell_price1 && $sell_stop && $sell_limit)
		Errors::add(Lang::string('sell-stop-lower-price'));
	if ($sell_stop && !($sell_stop_price1 > 0))
		Errors::add(Lang::string('buy-errors-no-stop'));
	if ($self_limits)
		Errors::add(Lang::string('sell-limit-under-stops').(($currency_info['id'] != $self_limits_currency) ? str_replace('[price]',$currency_info['fa_symbol'].number_format($self_limits,2),' '.Lang::string('limit-max-price')) : ''));
	if (time() < strtotime('2014-08-20 11:20:00'))
		Errors::add(Lang::string('competition-feature-before-start'));
	
	if (!is_array(Errors::$errors) && !$cancel) {
		if ($confirmed) {
			$sell_price1 = ($sell_stop && !$sell_limit) ? $sell_stop_price1 : $sell_price1;
			API::add('Orders','executeOrder',array(0,$sell_price1,$sell_amount1,$currency1,$user_fee_ask,$sell_market_price1,false,false,false,$sell_stop_price1));
			$query = API::send();
			$operations = $query['Orders']['executeOrder']['results'][0];

			if ($operations['new_order'] > 0) {
				Link::redirect('open-orders.php',array('transactions'=>$operations['transactions'],'new_order'=>1));
				exit;
			}
			else {
				Link::redirect('transactions.php',array('transactions'=>$operations['transactions']));
				exit;
			}
		}
		else {
			$ask_confirm = true;
		}
	}
}

$notice = '';
if ($ask_confirm && $_REQUEST['sell']) {
	$bank_accounts = $query['BankAccounts']['get']['results'][0];
	if (!$bank_accounts)
		$notice .= '<div class="message-box-wrap">'.str_replace('[currency]',$currency_info['currency'],Lang::string('buy-errors-no-bank-account')).'</div>';
	
	if (($buy_limit && $buy_stop) || ($sell_limit && $sell_stop))
		$notice .= '<div class="message-box-wrap">'.Lang::string('buy-notify-two-orders').'</div>';
}

$page_title = Lang::string('buy-sell');
if (!$bypass) {
	include 'includes/head.php';	
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="buy-sell.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
	
		<? if (time() < strtotime('2014-09-6 00:00:00')) { ?>
		<h3><?= Lang::string('trading-competition-status') ?></h3>
		<div class="one_half">
			<? if (time() < strtotime('2014-08-20 11:20:00')) { ?>
			<div class="starting_in rank"><i class="fa fa-clock-o fa-2x"></i> <?= Lang::string('competition-starting-in') ?>: <span class="time_until"></span><input type="hidden" class="time_until_seconds" value="<?= (strtotime('2014-08-20 11:20:00') * 1000) ?>" /></div>
   			<? } elseif (time() >= strtotime('2014-08-20 11:20:00') && time() < strtotime('2014-09-06 00:00:00')) { ?>
   			<div class="starting_in rank"><i class="fa fa-clock-o fa-2x"></i> <?= Lang::string('competition-time-left') ?>: <span class="time_until"></span><input type="hidden" class="time_until_seconds" value="<?= (strtotime('2014-09-06 00:00:00') * 1000) ?>" /></div>
   			<? } elseif (time() >= strtotime('2014-09-06 00:00:00') && time() < strtotime('2014-09-12 00:00:00')) { ?>
   			<div class="starting_in rank"><i class="fa fa-clock-o fa-2x"></i> <?= Lang::string('competition-time-left') ?>: <span class="prize"><?= Lang::string('competition-finished') ?></span></div>
   			<? } ?>
   		</div>
   		<? if (time() >= strtotime('2014-08-20 11:20:00') && time() < strtotime('2014-09-12 00:00:00')) { ?>
   		<div class="one_half last">
   			<div class="starting_in rank"><i class="fa fa-user fa-2x"></i> <?= Lang::string('competition-my-rank') ?>: <span class="prize"><b><?= $user_rank['rank']?></b> <small>(<?= (($user_rank['usd_gain'] >= 0) ? '+' : '').number_format($user_rank['usd_gain'],2) ?> USD)</small></span></div>
   		</div>
   		<? } ?>
   		<div class="clear"></div>
	   	<a href="contest-status.php" style="font-size:15px;text-decoration:underline;"><?= Lang::string('competition-status') ?></a>
   		<div class="clearfix mar_top2"></div><div class="clear"></div>
   		<? } ?>
	
		<? Errors::display(); ?>
		<?= ($notice) ? '<div class="notice">'.$notice.'</div>' : '' ?>
		<div class="testimonials-4">
			<? if (!$ask_confirm) { ?>
			<input type="hidden" id="user_fee" value="<?= $user_fee_both['fee'] ?>" />
			<input type="hidden" id="user_fee1" value="<?= $user_fee_both['fee1'] ?>" />
			<div class="one_half">
				<div class="content">
					<h3 class="section_label">
						<span class="left"><i class="fa fa-btc fa-2x"></i></span>
						<span class="right"><?= Lang::string('buy-bitcoins') ?></span>
					</h3>
					<div class="clear"></div>
					<form id="buy_form" action="buy-sell.php" method="POST">
						<div class="buyform">
							<div class="spacer"></div>
							<div class="calc dotted">
								<div class="label"><?= str_replace('[currency]','<span class="sell_currency_label">'.$currency_info['currency'].'</span>',Lang::string('buy-fiat-available')).((time() < strtotime('2014-09-12 00:00:00')) ? ' <span class="sim">('.Lang::string('simulation').')</span>' : '') ?></div>
								<div class="value"><span class="buy_currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="buy_user_available"><?= number_format($user_available[strtoupper($currency1)],2) ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="param">
								<label for="buy_amount"><?= Lang::string('buy-amount') ?></label>
								<input name="buy_amount" id="buy_amount" type="text" value="<?= $buy_amount1 ?>" />
								<div class="qualify">BTC</div>
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="buy_currency"><?= Lang::string('buy-with-currency') ?></label>
								<select id="buy_currency" name="currency">
								<?
								if ($CFG->currencies) {
									foreach ($CFG->currencies as $currency) {
										echo '<option '.((strtolower($currency['currency']) == $currency1) ? 'selected="selected"' : '').' value="'.strtolower($currency['currency']).'">'.$currency['currency'].'</option>';
									}
								}	
								?>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param lessbottom">
								<input class="checkbox" name="buy_market_price" id="buy_market_price" type="checkbox" value="1" <?= ($buy_market_price1 && !$buy_stop) ? 'checked="checked"' : '' ?> <?= (!$asks) ? 'readonly="readonly"' : '' ?> />
								<label for="buy_market_price"><?= Lang::string('buy-market-price') ?> <a target="_blank" title="<?= Lang::string('buy-market-rates-info') ?>" href="https://1btcxe.freshdesk.com/solution/articles/1000023402-what-are-market-price"><i class="fa fa-question-circle"></i></a></label>
								<div class="clear"></div>
							</div>
							<div class="param lessbottom">
								<input class="checkbox" name="buy_limit" id="buy_limit" type="checkbox" value="1" <?= ($buy_limit && !$buy_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="buy_limit"><?= Lang::string('buy-limit') ?> <a target="_blank" title="<?= Lang::string('buy-market-rates-info') ?>" href="https://1btcxe.freshdesk.com/support/solutions/articles/1000101680-what-is-a-limit"><i class="fa fa-question-circle"></i></a></label>
								<div class="clear"></div>
							</div>
							<div class="param lessbottom">
								<input class="checkbox" name="buy_stop" id="buy_stop" type="checkbox" value="1" <?= ($buy_stop && !$buy_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="buy_stop"><?= Lang::string('buy-stop') ?> <a target="_blank" title="<?= Lang::string('buy-market-rates-info') ?>" href="https://1btcxe.freshdesk.com/support/solutions/articles/1000101669-what-is-a-stop"><i class="fa fa-question-circle"></i></a></label>
								<div class="clear"></div>
							</div>
							<div id="buy_price_container" class="param" <?= (!$buy_limit && !$buy_market_price1) ? 'style="display:none;"' : '' ?>>
								<label for="buy_price"><span id="buy_price_limit_label" <?= (!$buy_limit) ? 'style="display:none;"' : '' ?>><?= Lang::string('buy-limit-price') ?></span><span id="buy_price_market_label" <?= ($buy_limit) ? 'style="display:none;"' : '' ?>><?= Lang::string('buy-price') ?></span></label>
								<input name="buy_price" id="buy_price" type="text" value="<?= number_format($buy_price1,2) ?>" <?= ($buy_market_price1) ? 'readonly="readonly"' : '' ?> />
								<div class="qualify"><span class="buy_currency_label"><?= $currency_info['currency'] ?></span></div>
								<div class="clear"></div>
							</div>
							<div id="buy_stop_container" class="param" <?= (!$buy_stop) ? 'style="display:none;"' : '' ?>>
								<label for="buy_stop_price"><?= Lang::string('buy-stop-price') ?></label>
								<input name="buy_stop_price" id="buy_stop_price" type="text" value="<?= number_format($buy_stop_price1,2) ?>" />
								<div class="qualify"><span class="buy_currency_label"><?= $currency_info['currency'] ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="calc">
								<div class="label"><?= Lang::string('buy-subtotal') ?></div>
								<div class="value"><span class="buy_currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="buy_subtotal"><?= number_format($buy_subtotal1,2) ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="calc">
								<div class="label"><?= Lang::string('buy-fee') ?> <a title="<?= Lang::string('account-view-fee-schedule') ?>" href="fee-schedule.php"><i class="fa fa-question-circle"></i></a></div>
								<div class="value"><span id="buy_user_fee"><?= $user_fee_bid ?></span>%</div>
								<div class="clear"></div>
							</div>
							<div class="calc bigger">
								<div class="label">
									<span id="buy_total_approx_label"><?= str_replace('[currency]','<span class="buy_currency_label">'.$currency_info['currency'].'</span>',Lang::string('buy-total-approx')) ?></span>
									<span id="buy_total_label" style="display:none;"><?= Lang::string('buy-total') ?></span>
								</div>
								<div class="value"><span class="buy_currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="buy_total"><?= number_format($buy_total1,2) ?></span></div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="buy" value="1" />
							<input type="submit" name="submit" value="<?= Lang::string('buy-bitcoins') ?>" class="but_user" />
						</div>
					</form>
				</div>
			</div>
			<div class="one_half last">
				<div class="content">
					<h3 class="section_label">
						<span class="left"><i class="fa fa-money fa-2x"></i></span>
						<span class="right"><?= Lang::string('sell-bitcoins') ?></span>
					</h3>
					<div class="clear"></div>
					<form id="sell_form" action="buy-sell.php" method="POST">
						<div class="buyform">
							<div class="spacer"></div>
							<div class="calc dotted">
								<div class="label"><?= Lang::string('sell-btc-available').((time() < strtotime('2014-09-12 00:00:00')) ? ' <span class="sim">('.Lang::string('simulation').')</span>' : '') ?></div>
								<div class="value"><span id="sell_user_available"><?= number_format($user_available['BTC'],8) ?></span> BTC</div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="param">
								<label for="sell_amount"><?= Lang::string('sell-amount') ?></label>
								<input name="sell_amount" id="sell_amount" type="text" value="<?= $sell_amount1 ?>" />
								<div class="qualify">BTC</div>
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="sell_currency"><?= Lang::string('buy-with-currency') ?></label>
								<select id="sell_currency" name="currency">
								<?
								if ($CFG->currencies) {
									foreach ($CFG->currencies as $currency) {
										echo '<option '.((strtolower($currency['currency']) == $currency1) ? 'selected="selected"' : '').' value="'.strtolower($currency['currency']).'">'.$currency['currency'].'</option>';
									}
								}	
								?>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param lessbottom">
								<input class="checkbox" name="sell_market_price" id="sell_market_price" type="checkbox" value="1" <?= ($sell_market_price1 && !$sell_stop) ? 'checked="checked"' : '' ?> <?= (!$bids) ? 'readonly="readonly"' : '' ?> />
								<label for="sell_market_price"><?= Lang::string('sell-market-price') ?> <a target="_blank" title="<?= Lang::string('buy-market-rates-info') ?>" href="https://1btcxe.freshdesk.com/solution/articles/1000023402-what-are-market-price"><i class="fa fa-question-circle"></i></a></label>
								<div class="clear"></div>
							</div>
							<div class="param lessbottom">
								<input class="checkbox" name="sell_limit" id="sell_limit" type="checkbox" value="1" <?= ($sell_limit && !$sell_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="sell_stop"><?= Lang::string('buy-limit') ?> <a target="_blank" title="<?= Lang::string('buy-market-rates-info') ?>" href="https://1btcxe.freshdesk.com/support/solutions/articles/1000101680-what-is-a-limit"><i class="fa fa-question-circle"></i></a></label>
								<div class="clear"></div>
							</div>
							<div class="param lessbottom">
								<input class="checkbox" name="sell_stop" id="sell_stop" type="checkbox" value="1" <?= ($sell_stop && !$sell_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="sell_stop"><?= Lang::string('buy-stop') ?> <a target="_blank" title="<?= Lang::string('buy-market-rates-info') ?>" href="https://1btcxe.freshdesk.com/support/solutions/articles/1000101669-what-is-a-stop"><i class="fa fa-question-circle"></i></a></label>
								<div class="clear"></div>
							</div>
							<div id="sell_price_container" class="param" <?= (!$sell_limit && !$sell_market_price1) ? 'style="display:none;"' : '' ?>>
								<label for="sell_price"><span id="sell_price_limit_label" <?= (!$sell_limit) ? 'style="display:none;"' : '' ?>><?= Lang::string('buy-limit-price') ?></span><span id="sell_price_market_label" <?= ($sell_limit) ? 'style="display:none;"' : '' ?>><?= Lang::string('buy-price') ?></span></label>
								<input name="sell_price" id="sell_price" type="text" value="<?= number_format($sell_price1,2) ?>" <?= ($sell_market_price1) ? 'readonly="readonly"' : '' ?> />
								<div class="qualify"><span class="sell_currency_label"><?= $currency_info['currency'] ?></span></div>
								<div class="clear"></div>
							</div>
							<div id="sell_stop_container" class="param" <?= (!$sell_stop) ? 'style="display:none;"' : '' ?>>
								<label for="sell_stop_price"><?= Lang::string('buy-stop-price') ?></label>
								<input name="sell_stop_price" id="sell_stop_price" type="text" value="<?= number_format($sell_stop_price1,2) ?>" />
								<div class="qualify"><span class="sell_currency_label"><?= $currency_info['currency'] ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="calc">
								<div class="label"><?= Lang::string('buy-subtotal') ?></div>
								<div class="value"><span class="sell_currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="sell_subtotal"><?= number_format($sell_subtotal1,2) ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="calc">
								<div class="label"><?= Lang::string('buy-fee') ?> <a title="<?= Lang::string('account-view-fee-schedule') ?>" href="fee-schedule.php"><i class="fa fa-question-circle"></i></a></div>
								<div class="value"><span id="sell_user_fee"><?= $user_fee_ask ?></span>%</div>
								<div class="clear"></div>
							</div>
							<div class="calc bigger">
								<div class="label">
									<span id="sell_total_approx_label"><?= str_replace('[currency]','<span class="sell_currency_label">'.$currency_info['currency'].'</span>',Lang::string('sell-total-approx')) ?></span>
									<span id="sell_total_label" style="display:none;"><?= str_replace('[currency]','<span class="sell_currency_label">'.$currency_info['currency'].'</span>',Lang::string('sell-total')) ?></span>
								</div>
								<div class="value"><span class="sell_currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="sell_total"><?= number_format($sell_total1,2) ?></span></div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="sell" value="1" />
							<input type="submit" name="submit" value="<?= Lang::string('sell-bitcoins') ?>" class="but_user" />
						</div>
					</form>
				</div>
			</div>
			<? } else { ?>
			<div class="one_half last">
				<div class="content">
					<h3 class="section_label">
						<span class="left"><i class="fa fa-exclamation fa-2x"></i></span>
						<span class="right"><?= Lang::string('confirm-transaction') ?></span>
						<div class="clear"></div>
					</h3>
					<div class="clear"></div>
					<form id="confirm_form" action="buy-sell.php" method="POST">
						<input type="hidden" name="confirmed" value="1" />
						<input type="hidden" id="cancel" name="cancel" value="" />
						<? if ($_REQUEST['buy']) { ?>
						<div class="balances" style="margin-left:0;">
							<div class="label"><?= Lang::string('buy-amount') ?></div>
							<div class="amount"><?= number_format($buy_amount1,8) ?></div>
							<input type="hidden" name="buy_amount" value="<?= $buy_amount1 ?>" />
							<div class="label"><?= Lang::string('buy-with-currency') ?></div>
							<div class="amount"><?= $currency_info['currency'] ?></div>
							<input type="hidden" name="buy_currency" value="<?= $currency1 ?>" />
							<? if ($buy_limit || $buy_market_price1) { ?>
							<div class="label"><?= ($buy_market_price1) ? Lang::string('buy-price') : Lang::string('buy-limit-price') ?></div>
							<div class="amount"><?= number_format($buy_price1,2) ?></div>
							<input type="hidden" name="buy_price" value="<?= $buy_price1 ?>" />
							<? } ?>
							<? if ($buy_stop) { ?>
							<div class="label"><?= Lang::string('buy-stop-price') ?></div>
							<div class="amount"><?= number_format($buy_stop_price1,2) ?></div>
							<input type="hidden" name="buy_stop_price" value="<?= $buy_stop_price1 ?>" />
							<? } ?>
						</div>
						<div class="buyform">
							<? if ($buy_market_price1) { ?>
							<div class="mar_top1"></div>
							<div class="param lessbottom">
								<input disabled="disabled" class="checkbox" name="dummy" id="buy_market_price" type="checkbox" value="1" <?= ($buy_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="buy_market_price"><?= Lang::string('buy-market-price') ?> <a target="_blank" title="<?= Lang::string('buy-market-rates-info') ?>" href="https://1btcxe.freshdesk.com/solution/articles/1000023402-what-are-market-price"><i class="fa fa-question-circle"></i></a></label>
								<input type="hidden" name="buy_market_price" value="<?= $buy_market_price1 ?>" />
								<div class="clear"></div>
							</div>
							<? } ?>
							<? if ($buy_limit) { ?>
							<div class="mar_top1"></div>
							<div class="param lessbottom">
								<input disabled="disabled" class="checkbox" name="dummy" id="buy_limit" type="checkbox" value="1" <?= ($buy_limit && !$buy_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="buy_limit"><?= Lang::string('buy-limit') ?> <a title="<?= Lang::string('buy-market-rates-info') ?>" href=""><i class="fa fa-question-circle"></i></a></label>
								<input type="hidden" name="buy_limit" value="<?= $buy_limit ?>" />
								<div class="clear"></div>
							</div>
							<? } ?>
							<? if ($buy_stop) { ?>
							<div class="mar_top1"></div>
							<div class="param lessbottom">
								<input disabled="disabled" class="checkbox" name="dummy" id="buy_stop" type="checkbox" value="1" <?= ($buy_stop && !$buy_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="buy_stop"><?= Lang::string('buy-stop') ?> <a title="<?= Lang::string('buy-market-rates-info') ?>" href=""><i class="fa fa-question-circle"></i></a></label>
								<input type="hidden" name="buy_stop" value="<?= $buy_stop ?>" />
								<div class="clear"></div>
							</div>
							<? } ?>
							<div class="spacer"></div>
							<div class="calc">
								<div class="label"><?= Lang::string('buy-subtotal') ?></div>
								<div class="value"><span class="sell_currency_char"><?= $currency_info['fa_symbol'] ?></span><?= number_format($buy_subtotal1,2) ?></div>
								<div class="clear"></div>
							</div>
							<div class="calc">
								<div class="label"><?= Lang::string('buy-fee') ?> <a title="<?= Lang::string('account-view-fee-schedule') ?>" href="fee-schedule.php"><i class="fa fa-question-circle"></i></a></div>
								<div class="value"><span id="sell_user_fee"><?= $user_fee_bid ?></span>%</div>
								<div class="clear"></div>
							</div>
							<div class="calc bigger">
								<div class="label">
									<span id="buy_total_approx_label"><?= str_replace('[currency]','<span class="buy_currency_label">'.$currency_info['currency'].'</span>',Lang::string('buy-total-approx')) ?></span>
									<span id="buy_total_label" style="display:none;"><?= Lang::string('buy-total') ?></span>
								</div>
								
								<div class="value"><span class="buy_currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="buy_total"><?= number_format($buy_total1,2) ?></span></div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="buy" value="1" />
						</div>
						<ul class="list_empty">
							<li style="margin-bottom:0;"><input type="submit" name="submit" value="<?= Lang::string('confirm-buy') ?>" class="but_user" /></li>
							<li style="margin-bottom:0;"><input id="cancel_transaction" type="submit" name="dont" value="<?= Lang::string('confirm-back') ?>" class="but_user grey" /></li>
						</ul>
						<div class="clear"></div>
						<? } else { ?>
						<div class="balances" style="margin-left:0;">
							<div class="label"><?= Lang::string('sell-amount') ?></div>
							<div class="amount"><?= number_format($sell_amount1,8) ?></div>
							<input type="hidden" name="sell_amount" value="<?= $sell_amount1 ?>" />
							<div class="label"><?= Lang::string('buy-with-currency') ?></div>
							<div class="amount"><?= $currency_info['currency'] ?></div>
							<input type="hidden" name="sell_currency" value="<?= $currency1 ?>" />
							<? if ($sell_limit || $sell_market_price1) { ?>
							<div class="label"><?= ($sell_market_price1) ? Lang::string('buy-price') : Lang::string('buy-limit-price') ?></div>
							<div class="amount"><?= number_format($sell_price1,2) ?></div>
							<input type="hidden" name="sell_price" value="<?= $sell_price1 ?>" />
							<? } ?>
							<? if ($sell_stop) { ?>
							<div class="label"><?= Lang::string('buy-stop-price') ?></div>
							<div class="amount"><?= number_format($sell_stop_price1,2) ?></div>
							<input type="hidden" name="sell_stop_price" value="<?= $sell_stop_price1 ?>" />
							<? } ?>
						</div>
						<div class="buyform">
							<? if ($sell_market_price1) { ?>
							<div class="mar_top1"></div>
							<div class="param lessbottom">
								<input disabled="disabled" class="checkbox" name="dummy" id="sell_market_price" type="checkbox" value="1" <?= ($sell_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="sell_market_price"><?= Lang::string('sell-market-price') ?> <a target="_blank" title="<?= Lang::string('buy-market-rates-info') ?>" href="https://1btcxe.freshdesk.com/solution/articles/1000023402-what-are-market-price"><i class="fa fa-question-circle"></i></a></label>
								<input type="hidden" name="sell_market_price" value="<?= $sell_market_price1 ?>" />
								<div class="clear"></div>
							</div>
							<? } ?>
							<? if ($sell_limit) { ?>
							<div class="mar_top1"></div>
							<div class="param lessbottom">
								<input disabled="disabled" class="checkbox" name="dummy" id="sell_limit" type="checkbox" value="1" <?= ($sell_limit && !$sell_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="sell_limit"><?= Lang::string('buy-limit') ?> <a title="<?= Lang::string('buy-market-rates-info') ?>" href=""><i class="fa fa-question-circle"></i></a></label>
								<input type="hidden" name="sell_limit" value="<?= $sell_limit ?>" />
								<div class="clear"></div>
							</div>
							<? } ?>
							<? if ($sell_stop) { ?>
							<div class="mar_top1"></div>
							<div class="param lessbottom">
								<input disabled="disabled" class="checkbox" name="dummy" id="sell_stop" type="checkbox" value="1" <?= ($sell_stop && !$sell_market_price1) ? 'checked="checked"' : '' ?> />
								<label for="sell_stop"><?= Lang::string('buy-stop') ?> <a title="<?= Lang::string('buy-market-rates-info') ?>" href=""><i class="fa fa-question-circle"></i></a></label>
								<input type="hidden" name="sell_stop" value="<?= $sell_stop ?>" />
								<div class="clear"></div>
							</div>
							<? } ?>
							<div class="spacer"></div>
							<div class="calc">
								<div class="label"><?= Lang::string('buy-subtotal') ?></div>
								<div class="value"><span class="sell_currency_char"><?= $currency_info['fa_symbol'] ?></span><?= number_format($sell_subtotal1,2) ?></div>
								<div class="clear"></div>
							</div>
							<div class="calc">
								<div class="label"><?= Lang::string('buy-fee') ?> <a title="<?= Lang::string('account-view-fee-schedule') ?>" href="fee-schedule.php"><i class="fa fa-question-circle"></i></a></div>
								<div class="value"><span id="sell_user_fee"><?= $user_fee_ask ?></span>%</div>
								<div class="clear"></div>
							</div>
							<div class="calc bigger">
								<div class="label">
									<span id="sell_total_approx_label"><?= str_replace('[currency]','<span class="sell_currency_label">'.$currency_info['currency'].'</span>',Lang::string('sell-total-approx')) ?></span>
									<span id="sell_total_label" style="display:none;"><?= str_replace('[currency]','<span class="sell_currency_label">'.$currency_info['currency'].'</span>',Lang::string('sell-total')) ?></span>
								</div>
								<div class="value"><span class="sell_currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="sell_total"><?= number_format($sell_total1,2) ?></span></div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="sell" value="1" />
						</div>
						<ul class="list_empty">
							<li style="margin-bottom:0;"><input type="submit" name="submit" value="<?= Lang::string('confirm-sale') ?>" class="but_user" /></li>
							<li style="margin-bottom:0;"><input id="cancel_transaction" type="submit" name="dont" value="<?= Lang::string('confirm-back') ?>" class="but_user grey" /></li>
						</ul>
						<div class="clear"></div>
						<? } ?>
					</form>
				</div>
			</div>
			<? } ?>
		</div>
		<div class="mar_top3"></div>
		<div class="clear"></div>
		<div id="filters_area">
<? } ?>
			<? if (!$ask_confirm) { ?>
			<div class="one_half">
				<h3><?= Lang::string('orders-bid-top-10') ?></h3>
	        	<div class="table-style">
	        		<table class="table-list trades" id="bids_list">
	        			<tr>
	        				<th><?= Lang::string('orders-price') ?></th>
	        				<th><?= Lang::string('orders-amount') ?></th>
	        				<th><?= Lang::string('orders-value') ?></th>
	        			</tr>
	        			<? 
	        			if ($bids) {
							foreach ($bids as $bid) {
								$mine = ($bid['mine']) ? '<a class="fa fa-user" href="javascript:return false;" title="'.Lang::string('home-your-order').'"></a>' : '';
								echo '
						<tr id="bid_'.$bid['id'].'" class="bid_tr">
							<td>'.$mine.$bid['fa_symbol'].'<span class="order_price">'.number_format($bid['btc_price'],2).'</span> '.(($bid['btc_price'] != $bid['fiat_price']) ? '<a title="'.str_replace('[currency]',$bid['currency_abbr'],Lang::string('orders-converted-from')).'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '').'</td>
							<td><span class="order_amount">'.number_format($bid['btc'],8).'</span></td>
							<td>'.$bid['fa_symbol'].'<span class="order_value">'.number_format(($bid['btc_price'] * $bid['btc']),2).'</span></td>
						</tr>';
							}
						}
						echo '<tr id="no_bids" style="'.(is_array($bids) ? 'display:none;' : '').'"><td colspan="4">'.Lang::string('orders-no-bid').'</td></tr>';
	        			?>
	        		</table>
				</div>
			</div>
			<div class="one_half last">
				<h3><?= Lang::string('orders-ask-top-10') ?></h3>
				<div class="table-style">
					<table class="table-list trades" id="asks_list">
						<tr>
							<th><?= Lang::string('orders-price') ?></th>
	        				<th><?= Lang::string('orders-amount') ?></th>
	        				<th><?= Lang::string('orders-value') ?></th>
						</tr>
	        			<? 
	        			if ($asks) {
							foreach ($asks as $ask) {
								$mine = ($ask['mine']) ? '<a class="fa fa-user" href="javascript:return false;" title="'.Lang::string('home-your-order').'"></a>' : '';
								echo '
						<tr id="ask_'.$ask['id'].'" class="ask_tr">
							<td>'.$mine.$ask['fa_symbol'].'<span class="order_price">'.number_format($ask['btc_price'],2).'</span> '.(($ask['btc_price'] != $ask['fiat_price']) ? '<a title="'.str_replace('[currency]',$ask['currency_abbr'],Lang::string('orders-converted-from')).'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '').'</td>
							<td><span class="order_amount">'.number_format($ask['btc'],8).'</span></td>
							<td>'.$ask['fa_symbol'].'<span class="order_value">'.number_format(($ask['btc_price'] * $ask['btc']),2).'</span></td>
						</tr>';
							}
						}
						echo '<tr id="no_asks" style="'.(is_array($asks) ? 'display:none;' : '').'"><td colspan="4">'.Lang::string('orders-no-ask').'</td></tr>';
	        			?>
					</table>
				</div>
				<div class="clear"></div>
			</div>
			<? } ?>
<? if (!$bypass) { ?>
		</div>
		<div class="mar_top5"></div>
	</div>
</div>
<? include 'includes/foot.php'; ?>
<? } ?>
