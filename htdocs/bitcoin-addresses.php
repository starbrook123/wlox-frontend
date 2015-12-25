<?php
include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');


if ((!empty($_REQUEST['c_currency']) && array_key_exists(strtoupper($_REQUEST['c_currency']),$CFG->currencies)))
	$_SESSION['ba_c_currency'] = $_REQUEST['c_currency'];
else if (empty($_SESSION['ba_c_currency']))
	$_SESSION['ba_c_currency'] = $_SESSION['c_currency'];


$c_currency = $_SESSION['ba_c_currency'];
API::add('BitcoinAddresses','get',array(false,$c_currency,false,30,1));
API::add('Content','getRecord',array('bitcoin-addresses'));
$query = API::send();

$bitcoin_addresses = $query['BitcoinAddresses']['get']['results'][0];
$content = $query['Content']['getRecord']['results'][0];
$page_title = Lang::string('bitcoin-addresses');

if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'add' && $_SESSION["btc_uniq"] == $_REQUEST['uniq']) {
	if (strtotime($bitcoin_addresses[0]['date']) >= strtotime('-1 day'))
		Errors::add(Lang::string('bitcoin-addresses-too-soon'));
	
	if (!is_array(Errors::$errors)) {
		API::add('BitcoinAddresses','getNew',array($c_currency));
		API::add('BitcoinAddresses','get',array(false,$c_currency,false,30,1));
		$query = API::send();
		$bitcoin_addresses = $query['BitcoinAddresses']['get']['results'][0];
		
		Messages::add(Lang::string('bitcoin-addresses-added'));
	}
}

$_SESSION["btc_uniq"] = md5(uniqid(mt_rand(),true));
include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="bitcoin-addresses.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
    	<div class="text"><?= $content['content'] ?></div>
    	<div class="clearfix mar_top2"></div>
    	<div class="clear"></div>
    	<? Errors::display(); ?>
    	<? Messages::display(); ?>
    	<div class="clear"></div>
    	<div class="filters">
	    	<ul class="list_empty">
	    		<li>
	    			<label for="c_currency"><?= Lang::string('currency') ?></label>
	    			<select id="c_currency">
	    			<? 
					foreach ($CFG->currencies as $key => $currency1) {
						if (is_numeric($key) || $currency1['is_crypto'] != 'Y')
							continue;
						
						echo '<option value="'.$currency1['id'].'" '.($currency1['id'] == $c_currency ? 'selected="selected"' : '').'>'.$currency1['currency'].'</option>';
					}
					?>
	    			</select>
	    		</li>
				<li><a href="bitcoin-addresses.php?action=add&c_currency=<?= $c_currency ?>&uniq=<?= $_SESSION["btc_uniq"] ?>" class="but_user"><i class="fa fa-plus fa-lg"></i> <?= Lang::string('bitcoin-addresses-add') ?></a></li>
			</ul>
		</div>
		<div id="filters_area">
	    	<div class="table-style">
	    		<table class="table-list trades">
					<tr>
						<th><?= Lang::string('currency') ?></th>
						<th><?= Lang::string('bitcoin-addresses-date') ?></th>
						<th><?= Lang::string('bitcoin-addresses-address') ?></th>
					</tr>
					<? 
					if ($bitcoin_addresses) {
						foreach ($bitcoin_addresses as $address) {
					?>
					<tr>
						<td><?= $CFG->currencies[$address['c_currency']]['currency'] ?></td>
						<td><input type="hidden" class="localdate" value="<?= (strtotime($address['date']) + $CFG->timezone_offset) ?>" /></td>
						<td><?= $address['address'] ?></td>
					</tr>
					<?
						}
					}
					else {
						echo '<tr><td colspan="3">'.Lang::string('bitcoin-addresses-no').'</td></tr>';
					}
					?>
				</table>
			</div>
		</div>
    </div>
	<div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>
