<?php
include '../lib/common.php';

$currencies = Settings::sessionCurrency();
$page_title = Lang::string('home-title');
$usd_field = 'usd_ask';
$currency_info = $CFG->currencies[$currencies['currency']];
$c_currency_info = $CFG->currencies[$currencies['c_currency']];
$currency_majors = array('USD','EUR','CNY','RUB','CHF','JPY','GBP','CAD','AUD');
$c_majors = count($currency_majors);
$curr_list = $CFG->currencies;

$sma = (!empty($_SESSION['sma']) || !isset($_SESSION['sma']));
$sma1 = (!empty($_SESSION['sma1'])) ? preg_replace("/[^0-9]/", "",$_SESSION['sma1']) : 8;
$sma2 = (!empty($_SESSION['sma2'])) ? preg_replace("/[^0-9]/", "",$_SESSION['sma2']) : 30;
$ema = (!empty($_SESSION['ema']));
$ema1 = (!empty($_SESSION['ema1'])) ? preg_replace("/[^0-9]/", "",$_SESSION['ema1']) : 8;
$ema2 = (!empty($_SESSION['ema2'])) ? preg_replace("/[^0-9]/", "",$_SESSION['ema2']) : 30;

$curr_list1 = array();
foreach ($currency_majors as $currency) {
	if (empty($curr_list[$currency]))
		continue;

	$curr_list1[$currency] = $curr_list[$currency];
	unset($curr_list[$currency]);
}
$curr_list = array_merge($curr_list1,$curr_list);

if (!User::isLoggedIn()) {
	API::add('Content','getRecord',array('home'));
}

API::add('Stats','getCurrent',array($currencies['c_currency'],$currencies['currency']));
API::add('Transactions','get',array(false,false,5,$currencies['c_currency'],$currencies['currency']));
API::add('Orders','get',array(false,false,5,$currencies['c_currency'],$currencies['currency'],false,false,1));
API::add('Orders','get',array(false,false,5,$currencies['c_currency'],$currencies['currency'],false,false,false,false,1));
API::add('News','get',array(false,false,3));
$query = API::send();

if (!User::isLoggedIn())
	$content = $query['Content']['getRecord']['results'][0];

$stats = $query['Stats']['getCurrent']['results'][0];
$transactions = $query['Transactions']['get']['results'][0];
$bids = $query['Orders']['get']['results'][0];
$asks = $query['Orders']['get']['results'][1];
$news = $query['News']['get']['results'][0];

if ($stats['daily_change'] > 0)
	$arrow = '<i id="up_or_down" class="fa fa-caret-up price-green"></i> ';
elseif ($stats['daily_change'] < 0)
$arrow = '<i id="up_or_down" class="fa fa-caret-down price-red"></i> ';
else
	$arrow = '<i id="up_or_down" class="fa fa-minus"></i> ';

if ($query['Transactions']['get']['results'][0][0]['maker_type'] == 'sell') {
	$arrow1 = '<i id="up_or_down1" class="fa fa-caret-up price-green"></i> ';
	$p_color = 'price-green';
}
elseif ($query['Transactions']['get']['results'][0][0]['maker_type'] == 'buy') {
	$arrow1 = '<i id="up_or_down1" class="fa fa-caret-down price-red"></i> ';
	$p_color = 'price-red';
}
else {
	$arrow1 = '<i id="up_or_down1" class="fa fa-minus"></i> ';
	$p_color = '';
}

include 'includes/head.php';

if (!User::isLoggedIn()) {
?>

<div class="container_full">
	<?php 
	if ($CFG->language == 'en' || $CFG->language == 'es' || empty($CFG->language))
		$wordwrap = 80;
	elseif ($CFG->language == 'ru')
		$wordwrap = 150;
	elseif ($CFG->language == 'zh')
		$wordwrap = 150;
	?>
	<div class="mobilebanner">
		<div class="container">
			<h1 <?= ($CFG->language == 'ru') ? 'class="caption_ru"' : false ?>><?= $content['title'] ?></h1>
			<p class="text"><?= wordwrap(strip_tags($content['content']),$wordwrap,'<br/>') ?> <a class="morestuff" href="<?= Lang::url('about.php') ?>">>></a></p>   
			<div class="crypto_logo"><a target="_blank" href="https://cryptocapital.co">Integrated With <img src="images/crypto_logo.png" /></a></div>
			<a href="login.php" class="button_slider"><i class="fa fa-key"></i>&nbsp;&nbsp;<?= Lang::string('home-login') ?></a>       
			<a href="<?= Lang::url('register.php') ?>" class="button_slider"><i class="fa fa-user"></i>&nbsp;&nbsp;<?= Lang::string('home-register') ?></a>
			<div class="clear"></div>
		</div>
		<div class="ticker">
			<div class="contain">
				<div class="scroll">
				<?
				if ($curr_list) {
					foreach ($curr_list as $key => $currency) {
						if (is_numeric($key) || $currency['id'] == $c_currency_info['id'])
							continue;
				
						$last_price = number_format($stats['last_price'] * ((empty($currency_info) || $currency_info['currency'] == 'USD') ? 1/$currency[$usd_field] : $currency_info[$usd_field] / $currency[$usd_field]),2);
						echo '<a class="'.(($currency_info['id'] == $currency['id']) ? $p_color.' selected' : '').'" href="index.php?currency='.$currency['id'].'"><span class="abbr">'.$currency['currency'].'</span> <span class="price_'.$currency['currency'].'">'.$last_price.'</span></a>';
					}
				}
				?>
				</div>
			</div>
			<div class="bg"></div>
		</div>
	</div>
</div>

<div class="clearfix"></div>
<? } ?>
<div class="fresh_projects global_stats">
	<a name="global_stats"></a>
	<div class="clearfix mar_top6"></div>
	<div class="container">
		<? if (!User::isLoggedIn()) { ?>
    	<h2>
    		<select name="c_currency" id="c_currency" class="c_currency_big">
    		<?
    		if ($CFG->currencies) {
    			foreach ($CFG->currencies as $key => $currency) {
    				if (is_numeric($key) || $currency['is_crypto'] != 'Y')
    					continue;
    		
    				echo '<option '.(($c_currency_info['id'] == $currency['id']) ? 'selected="selected"' : '').' value="'.$currency['id'].'">'.$currency['currency'].'</option>';
    			}
    		}
    		?>
    		</select>
    		<?= Lang::string('home-bitcoin-market') ?>
    	</h2>
        <p class="explain"><?= str_replace('[c_currency]',$c_currency_info['name_'.$CFG->language],Lang::string('home-bitcoin-market-explain')) ?></p>
        <? } else { ?>
        <h2>
        	<select name="c_currency" id="c_currency" class="c_currency_big">
    		<?
    		if ($CFG->currencies) {
    			foreach ($CFG->currencies as $key => $currency) {
    				if (is_numeric($key) || $currency['is_crypto'] != 'Y')
    					continue;
    		
    				echo '<option '.(($c_currency_info['id'] == $currency['id']) ? 'selected="selected"' : '').' value="'.$currency['id'].'">'.$currency['currency'].'</option>';
    			}
    		}
    		?>
    		</select>
        	<?= Lang::string('home-overview') ?>
        </h2>
        <? } ?>
        <div class="mar_top3"></div>
        <div class="clear"></div>
        <div class="panel panel-default">
        	<div class="panel-heading non-mobile">
        		<div class="one_fifth"><?= Lang::string('home-stats-last-price') ?></div>
		        <div class="one_fifth"><?= Lang::string('home-stats-daily-change') ?></div>
		        <div class="one_fifth"><?= Lang::string('home-stats-days-range') ?></div>
		        <div class="one_fifth"><?= Lang::string('home-stats-todays-open') ?></div>
		        <div class="one_fifth last"><?= Lang::string('home-stats-24h-volume') ?></div>
		        <div class="clear"></div>
        	</div>
        	<div class="panel-body">
				<div class="one_fifth">
		        	<div class="m_head"><?= Lang::string('home-stats-last-price') ?></div>
		        	<p class="stat1 <?= ($query['Transactions']['get']['results'][0][0]['maker_type'] == 'sell') ? 'price-green' : 'price-red' ?>"><?= $arrow1.'<span class="buy_currency_char">'.$currency_info['fa_symbol'].'</span><span id="stats_last_price">'.number_format($stats['last_price'],2).'</span>'?><small id="stats_last_price_curr"><?= ($query['Transactions']['get']['results'][0][0]['currency'] != $currency_info['id'] && $query['Transactions']['get']['results'][0][0]['currency1'] != $currency_info['id'] && $query['Transactions']['get']['results'][0][0]['currency']) ? ' ('.$CFG->currencies[$query['Transactions']['get']['results'][0][0]['currency1']]['currency'].')' : '' ?></small></p>
		        </div>
		        <div class="one_fifth">
		        	<div class="m_head"><?= Lang::string('home-stats-daily-change') ?></div>
		        	<p class="stat1"><?= $arrow.'<span id="stats_daily_change_abs">'.number_format(abs($stats['daily_change']),2).'</span>' ?> <small><?= '<span id="stats_daily_change_perc">'.number_format(abs($stats['daily_change_percent']),2).'</span>%'?></small></p>
		        </div>
		        <div class="one_fifth">
		        	<div class="m_head"><?= Lang::string('home-stats-days-range') ?></div>
		        	<p class="stat1"><?= '<span class="buy_currency_char">'.$currency_info['fa_symbol'].'</span><span id="stats_min">'.number_format($stats['min'],2).'</span> - <span id="stats_max">'.number_format($stats['max'],2).'</span>' ?></p>
		        </div>
		        <div class="one_fifth">
		        	<div class="m_head"><?= Lang::string('home-stats-todays-open') ?></div>
		        	<p class="stat1"><?= '<span class="buy_currency_char">'.$currency_info['fa_symbol'].'</span><span id="stats_open">'.number_format($stats['open'],2).'</span>'?></p>
		        </div>
		        <div class="one_fifth last">
		        	<div class="m_head"><?= Lang::string('home-stats-24h-volume') ?></div>
		        	<p class="stat1"><?= '<span id="stats_traded">'.number_format($stats['total_btc_traded'],2).'</span>' ?> <?= $c_currency_info['currency']?></p>
		        </div>
		        <div class="panel-divider"></div>
		        <div class="one_third">
		        	<h5><?= Lang::string('home-stats-market-cap') ?>: <em class="stat2"><?= '<span class="buy_currency_char">'.$currency_info['fa_symbol'].'</span><span id="stats_market_cap">'.number_format($stats['market_cap']).'</span>'?></em></h5>
		        </div>
		        <div class="one_third">
		        	<h5><?= str_replace('[c_currency]',$c_currency_info['currency'],Lang::string('home-stats-total-btc')) ?>: <em class="stat2"><?= '<span id="stats_total_btc">'.number_format($stats['total_btc']).'</span>' ?></em></h5>
		        </div>
		        <div class="one_third last">
		        	<h5><?= Lang::string('home-stats-global-volume') ?>: <em class="stat2"><?= '<span class="buy_currency_char">'.$currency_info['fa_symbol'].'</span><span id="stats_trade_volume">'.number_format($stats['trade_volume']).'</span>' ?></em></h5>
		        </div>
		        <div class="clear"></div>
			</div>
			 <div class="clear"></div>
		</div>
        <div class="clear"></div>
        <div class="currencies panel panel-default">
        	<div class="panel-body">
	        <? 
	        if ($curr_list) {
				foreach ($curr_list as $key => $currency) {
					if (is_numeric($key) || $currency['id'] == $c_currency_info['id'])
						continue;
						
					$last_price = number_format($stats['last_price'] * ((empty($currency_info) || $currency_info['currency'] == 'USD') ? 1/$currency[$usd_field] : $currency_info[$usd_field] / $currency[$usd_field]),2);
					echo '<a class="'.(($currency_info['id'] == $currency['id']) ? $p_color.' selected' : '').'" href="index.php?currency='.$currency['id'].'#global_stats"><span class="abbr">'.$currency['currency'].'</span> <span class="price_'.$currency['currency'].'">'.$last_price.'</span></a>';
				}
			}
	        ?>
        	</div>
        	<div class="repeat-line o1"></div>
        	<div class="repeat-line o2"></div>
        	<div class="repeat-line o3"></div>
        	<div class="repeat-line o4"></div>
        	<div class="repeat-line o5"></div>
        	<div class="repeat-line o6"></div>
        	<div class="repeat-line o7"></div>
        	<div class="repeat-line o8"></div>
        	<div class="repeat-line o9"></div>
        	<div class="repeat-line o10"></div>
        </div>
        <div class="graph_options">
        	<a href="#" class="selected" data-option="1mon">1m</a>
        	<a href="#" data-option="3mon">3m</a>
        	<a href="#" data-option="6mon">6m</a>
        	<a href="#" data-option="ytd">YTD</a>
        	<a href="#" class="last" data-option="1year">1y</a>
        	<div class="clear"></div>
        </div>
        <?php /*
	        <div class="graph_options">
	        	<a href="#" data-option="1mon">1m</a>
	        	<a href="#" data-option="3mon">3m</a>
	        	<a href="#" data-option="6mon">6m</a>
	        	<a href="#" data-option="ytd">YTD</a>
	        	<a href="#" class="selected last" data-option="1year">1y</a>
	        	<span>
	        	<label for="currency_selector"><?= Lang::string('currency') ?></label>
	        	<select id="currency_selector">
	        	<? 
	        	if ($CFG->currencies) {
					foreach ($CFG->currencies as $key => $currency) {
						if (is_numeric($key) || $currency['currency'] == 'BTC')
							continue;
						
						echo '<option value="'.strtolower($currency['currency']).'" '.(($currency1 == strtolower($currency['currency']) || (!$currency1 && strtolower($currency['currency']) == 'usd')) ? 'selected="selected"' : '').'>'.$currency['currency'].'</option>';
					}
				}
	        	?>
	        	</select>
	        	</span>
	        </div>
	    */ ?>
        <div class="graph_contain">
        	<input type="hidden" id="is_crypto" value="<?= $currency_info['is_crypto'] ?>" />
        	<input type="hidden" id="graph_price_history_currency" value="<?= $currencies['currency'] ?>" />
        	<input type="hidden" id="graph_price_history_c_currency" value="<?= $currencies['c_currency'] ?>" />
        	<div id="graph_candles"></div>
	        <div class="clear_300"></div>
	        <div class="clear"></div>
	        <div id="graph_price_history"></div>
	        <div class="drag_zoom">
	        	<div class="contain">
		        	<div id="zl" class="handle"></div>
		        	<div id="zr" class="handle"></div>
		        	<div class="bg"></div>
	        	</div>
	        </div>
	        <a id="graph_settings" class="fa fa-gear"></a>
	        <div class="graph_settings">
	        	<div class="label"><?= Lang::string('indicators') ?></div>
	        	<div class="indicators">
	        		<div class="row">
	        			<input class="check" type="checkbox" id="sma" <?= ($sma) ? 'checked="checked"' : '' ?> />
	        			<a class="selected" href="#">SMA</a>
	        			<input id="sma1" class="indicator" value="<?= $sma1 ?>" type="text" style="background-color:#C4D5FF" />
	        			<input id="sma2" class="indicator" value="<?= $sma2 ?>" type="text" style="background-color:#FFEFC4" />
	        		</div>
	        		<div class="row">
	        			<input class="check" type="checkbox" id="ema" <?= ($ema) ? 'checked="checked"' : '' ?> />
	        			<a class="selected" href="#">EMA</a>
	        			<input id="ema1" class="indicator" value="<?= $ema1 ?>" type="text" style="background-color:#FCC4FF" />
	        			<input id="ema2" class="indicator" value="<?= $ema2 ?>" type="text" style="background-color:#C5FFC4" />
	        		</div>
	        	</div>
	        	<a class="highlight blue" href="#"><?= Lang::string('restore-defaults') ?></a>
	        </div>
        </div>
        <div class="mar_top4"></div>
        <div class="one_half">
        	<input type="hidden" id="transactions_timestamp" value="<?= time() * 1000 ?>" />
        	<h3><?= Lang::string('home-live-trades') ?></h3>
        	<div class="table-style">
        		<table class="table-list trades" id="transactions_list">
        			<tr>
        				<th><?= Lang::string('transactions-time-since') ?></th>
        				<th><?= Lang::string('transactions-amount') ?></th>
        				<th><?= str_replace('[c_currency]',$c_currency_info['currency'],Lang::string('transactions-price')) ?></th>
        			</tr>
        			<? 
        			if ($transactions) {
						foreach ($transactions as $transaction) {
							echo '
					<tr id="order_'.$transaction['id'].'">
						<td><span class="time_since"></span><input type="hidden" class="time_since_seconds" value="'.strtotime($transaction['date']).'" /></td>
						<td>'.number_format($transaction['btc'],8).' '.$c_currency_info['currency'].'</td>
						<td><span class="buy_currency_char">'.$currency_info['fa_symbol'].'</span>'.number_format($transaction['btc_price'],($currency_info['is_crypto'] == 'Y' ? 8 : 2)).(($transaction['currency'] == $currency_info['id']) ? false : (($transaction['currency1'] == $currency_info['id']) ? false : ' ('.$CFG->currencies[$transaction['currency1']]['currency'].')')).'</td>
					</tr>';
						}
					}
					echo '<tr id="no_transactions" style="'.(is_array($transactions) && count($transactions) > 0 ? 'display:none;' : '').'"><td colspan="3">'.Lang::string('transactions-no').'</td></tr>';
        			?>
        		</table>
        	</div>
        </div>
        <div class="one_half last">
        	<h3><?= Lang::string('home-live-orders') ?> <a href="order-book.php" class="highlight gray"><i class="fa fa-plus-square"></i> <?= Lang::string('order-book-see') ?></a></h3>
        	<div class="one_half">
        		<div class="table-style">
        			<table class="table-list trades" id="bids_list">
        			<tr>
        				<th colspan="2"><?= Lang::string('orders-bid') ?></th>
        			</tr>
        			<? 
        			if ($bids) {
						foreach ($bids as $bid) {
							$mine = (!empty(User::$info['user']) && $bid['user_id'] == User::$info['user'] && $bid['btc_price'] == $bid['fiat_price']) ? '<a class="fa fa-user" href="open-orders.php?id='.$bid['id'].'" title="'.Lang::string('home-your-order').'"></a>' : '';
							echo '
					<tr id="bid_'.$bid['id'].'" class="bid_tr">
						<td>'.$mine.'<span class="order_amount">'.number_format($bid['btc'],8).'</span> '.$c_currency_info['currency'].'<input type="hidden" id="order_id" value="'.$bid['id'].'" /></td>
						<td><span class="buy_currency_char">'.$currency_info['fa_symbol'].'</span><span class="order_price">'.number_format($bid['btc_price'],($currency_info['is_crypto'] == 'Y' ? 8 : 2)).'</span> '.(($bid['btc_price'] != $bid['fiat_price']) ? '<a title="'.str_replace('[currency]',$CFG->currencies[$bid['currency']]['currency'],Lang::string('orders-converted-from')).'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '').'</td>
					</tr>';
						}
					}
					echo '<tr id="no_bids" style="'.(is_array($bids) ? 'display:none;' : '').'"><td colspan="2">'.Lang::string('orders-no-bid').'</td></tr>';
        			?>
        		</table>
        		</div>
        	</div>
        	<div class="one_half last">
        		<div class="table-style">
        			<table class="table-list trades" id="asks_list">
        			<tr>
        				<th colspan="2"><?= Lang::string('orders-ask') ?></th>
        			</tr>
        			<? 
        			if ($asks) {
						foreach ($asks as $ask) {
							$mine = (!empty(User::$info['user']) && $ask['user_id'] == User::$info['user'] && $ask['btc_price'] == $ask['fiat_price']) ? '<a class="fa fa-user" href="open-orders.php?id='.$ask['id'].'" title="'.Lang::string('home-your-order').'"></a>' : '';
							echo '
					<tr id="ask_'.$ask['id'].'" class="ask_tr">
						<td>'.$mine.'<span class="order_amount">'.number_format($ask['btc'],8).'</span> '.$c_currency_info['currency'].'<input type="hidden" id="order_id" value="'.$ask['id'].'" /></td>
						<td><span class="buy_currency_char">'.$currency_info['fa_symbol'].'</span><span class="order_price">'.number_format($ask['btc_price'],($currency_info['is_crypto'] == 'Y' ? 8 : 2)).'</span> '.(($ask['btc_price'] != $ask['fiat_price']) ? '<a title="'.str_replace('[currency]',$CFG->currencies[$ask['currency']]['currency'],Lang::string('orders-converted-from')).'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '').'</td>
					</tr>';
						}
					}
					echo '<tr id="no_asks" style="'.(is_array($asks) ? 'display:none;' : '').'"><td colspan="2">'.Lang::string('orders-no-ask').'</td></tr>';
        			?>
        		</table>
        		</div>
        	</div>
        </div>
    </div>
    
	<div class="clearfix mar_top3"></div>
    
</div><!-- end fresh projects -->


<div class="clearfix mar_top5"></div>

<div class="features_sec03">
	<div class="container">
    	<h2><?= Lang::string('news-latest') ?></h2>
        <p class="explain"><?= Lang::string('news-explain') ?></p>
        <div class="clearfix mar_top3"></div>
        <? 
        if ($news) {
			$i = 1;
			$c = count($news);
			foreach ($news as $news_item) {
			?>
		<div class="blog_post">
			<div class="blog_postcontent">
				<div class="post_info_content_small fullwidth">
					<a class="date" href="#" onclick="return false;"><strong><?= date('j',strtotime($news_item['date']))?></strong><i><?= Lang::string(strtolower(date('M',strtotime($news_item['date'])))) ?></i></a>
					<div class="postcontent">	
						<h3><a href="#" onclick="return false;"><?= $news_item['title_'.$CFG->language] ?></a></h3>
						<div class="posttext"><?= $news_item['content_'.$CFG->language] ?></div>
					</div>
				</div>
			</div>
		</div>
		<?= ($c != $i) ? '<div class="clearfix divider_line3"></div>' : '' ?>
			<?
			$i++;
			}
		}
        ?>
        <div class="clearfix mar_top5"></div>
        <a href="<?= Lang::url('press-releases.php') ?>" class="highlight gray bigger"><i class="fa fa-plus-square"></i> <?= Lang::string('news-see-all') ?></a>
    </div>
	<div class="clearfix mar_top8"></div>
</div><!-- end features section 3 -->




<? include 'includes/foot.php'; ?>
