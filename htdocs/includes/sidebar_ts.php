<div class="left_sidebar">
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
</div>