<?php

class Settings {
	public static function assign($all) {
		global $CFG;
		
		if (is_array($all) && is_object($CFG)) {
			foreach ($all as $name => $value) {
				$name = str_replace('frontend_','',$name);
				$CFG->$name = $value;
			}
		}
	}
	
	public static function sessionCurrency() {
		global $CFG;
		
		API::add('Currencies','getMain');
		$query = API::send();
		$main = $query['Currencies']['getMain']['results'][0];
		
		if (empty($_REQUEST['currency']) && empty($_SESSION['currency']) && !empty(User::$info['default_currency']))
			$_SESSION['currency'] = User::$info['default_currency'];
		elseif (empty($_REQUEST['currency']) && empty($_SESSION['currency']) && empty(User::$info['default_currency']))
			$_SESSION['currency'] = $main['fiat'];
		elseif (!empty($_REQUEST['currency']))
			$_SESSION['currency'] = preg_replace("/[^0-9]/", "",$_REQUEST['currency']);
		
		if (empty($_REQUEST['c_currency']) && empty($_SESSION['c_currency']) && !empty(User::$info['default_c_currency']))
			$_SESSION['c_currency'] = User::$info['default_c_currency'];
		elseif (empty($_REQUEST['c_currency']) && empty($_SESSION['c_currency']) && empty(User::$info['default_c_currency']))
			$_SESSION['c_currency'] = $main['crypto'];
		elseif (!empty($_REQUEST['c_currency']))
			$_SESSION['c_currency'] = preg_replace("/[^0-9]/", "",$_REQUEST['c_currency']);
		
		if ($_SESSION['currency'] && !is_numeric($_SESSION['currency']))
			$_SESSION['currency'] = $CFG->currencies[strtoupper($_SESSION['currency'])]['id'];
		
		if ($CFG->currencies[$_SESSION['c_currency']]['is_crypto'] != 'Y')
			$_SESSION['c_currency'] = $main['crypto'];
		if ($_SESSION['c_currency'] == $_SESSION['currency'])
			$_SESSION['currency'] = $main['fiat'];
		
		return array('currency'=>$_SESSION['currency'],'c_currency'=>$_SESSION['c_currency']);
	}
}

?>