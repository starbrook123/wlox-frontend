<div class="left_sidebar">
	<? if ($CFG->self == 'buy-sell.php' || $CFG->self == 'edit-order.php') { ?>
	<div class="ts_meters">
		<div id="ts_meter_1h" class="ts_meter">
			<div class="ts_meter_t"><?= Lang::string('ts-volume1') ?></div>
			<div id="ts_meter_m1" class="ts_meter_m"></div>
			<div class="ts_row ts_meter_sell"><?= Lang::string('ts-sell') ?> <span><?= number_format($stats['btc_1h_sell'],1) ?></span></div>
			<div class="ts_row ts_meter_buy"><?= Lang::string('ts-buy') ?> <span><?= number_format($stats['btc_1h_buy'],1) ?></span></div>
			<div class="ts_row ts_meter_total"><?= Lang::string('ts-total') ?> <span><?= number_format($stats['btc_1h'],1) ?></span></div>
		</div>
		<div id="ts_meter_24h" class="ts_meter">
			<div class="ts_meter_t"><?= Lang::string('ts-volume24') ?></div>
			<div id="ts_meter_m24" class="ts_meter_m"></div>
			<div class="ts_row ts_meter_sell"><?= Lang::string('ts-sell') ?> <span><?= number_format($stats['btc_24h_sell'],1) ?></span></div>
			<div class="ts_row ts_meter_buy"><?= Lang::string('ts-buy') ?> <span><?= number_format($stats['btc_24h_buy'],1) ?></span></div>
			<div class="ts_row ts_meter_total"><?= Lang::string('ts-total') ?> <span><?= number_format($stats['btc_24h'],1) ?></span></div>
		</div>
		<div class="clear"></div>
	</div>
	<div class="ts_cryptos table-style">
		<table class="table-list trades" id="c_currencies_list">
			<tr>
				<th><?= Lang::string('ts-currency') ?></th>
				<th><?= Lang::string('ts-price') ?></th>
			</tr>
			<? 
			if ($CFG->currencies) {
				foreach ($CFG->currencies as $key => $currency) {
					if (is_numeric($key) || $currency['is_crypto'] != 'Y')
						continue;
					
					$image = '<div class="ts_placeholder">';
					if ($currency['currency'] == 'BTC') {
						$image .= '<img title="'.$currency['name_'.$CFG->language].'" src="images/btc.png" />';
					}
					$image .= '</div>';
					
					echo '
					<tr id="c_currency_'.$currency['currency'].'">
						<td><a class="switch_c_currency" href="'.$CFG->self.'?c_currency='.strtolower($currency['currency']).'">'.$currency['currency'].'</a> '.$image.'</td>
						<td class="'.$p_color.'">'.$arrow.'<span class="price">'.number_format($stats['last_price'],2).' ('.$stats['daily_change_percent'].'%)</span></td>
					</tr>';
				}
			}
			echo '<tr id="no_bids" style="'.(is_array($bids) ? 'display:none;' : '').'"><td colspan="4">'.Lang::string('orders-no-bid').'</td></tr>';
			?>
		</table>
	</div>
	<? } ?>
	<div class="sidebar_widget">
    	<div class="sidebar_title"><h3><?= Lang::string('account-nav') ?></h3></div>
		<ul class="arrows_list1">
			<li><a href="account.php" <?= ($CFG->self == 'account.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('account') ?></a></li>
			<li><a href="open-orders.php" <?= ($CFG->self == 'open-orders.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('open-orders') ?></a></li>
			<li><a href="transactions.php" <?= ($CFG->self == 'transactions.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('transactions') ?></a></li>
			<li><a href="security.php" <?= ($CFG->self == 'security.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('security') ?></a></li>
			<li><a href="settings.php" <?= ($CFG->self == 'settings.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('settings') ?></a></li>
			<li><a href="bank-accounts.php" <?= ($CFG->self == 'bank-accounts.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('bank-accounts') ?></a></li>
			<li><a href="bitcoin-addresses.php" <?= ($CFG->self == 'bitcoin-addresses.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('bitcoin-addresses') ?></a></li>
			<li><a href="history.php" <?= ($CFG->self == 'history.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('history') ?></a></li>
			<li><a href="api-access.php" <?= ($CFG->self == 'api-access.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('api-access') ?></a></li>
			<li><a href="logout.php?log_out=1&uniq=<?= $_SESSION["logout_uniq"] ?>"><i class="fa fa-angle-right"></i> <?= Lang::string('log-out') ?></a></li>
		</ul>
	</div>
	<div class="clearfix mar_top3"></div>
	<div class="sidebar_widget">
    	<div class="sidebar_title"><h3><?= Lang::string('account-functions') ?></h3></div>
		<ul class="arrows_list1">
			<li><a href="buy-sell.php" <?= ($CFG->self == 'buy-sell.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('buy-sell') ?></a></li>
			<li><a href="deposit.php" <?= ($CFG->self == 'deposit.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('deposit') ?></a></li>
			<li><a href="withdraw.php" <?= ($CFG->self == 'withdraw.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('withdraw') ?></a></li>
		</ul>
	</div>
	<div class="mar_top8"></div>
	<div class="clear"></div>
</div>