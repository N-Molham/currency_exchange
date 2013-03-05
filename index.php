<?php
/**
 * NBE Currency Exchange for Egyptian Pound(EGP)
 * 
 * A way to read currencies' prices from National Bank of Egypt website.
 * Using phpQuery to parse html content of the exchangerate page to get values,
 * Hope you find it helpful :)
 * 
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * 
 * @author Nabeel Molham - n.molham@gmail.com
 * @version 1.0
 */

// start session to save prices
if('' == session_id())
	session_start();

// require phpQuery
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpQuery-onefile.php';

// update timegap setup
if (!isset($_SESSION['nbe_time']))
	$_SESSION['nbe_time'] = 0;

// get NBE table
$time = time();
if (!isset($_SESSION['nbe_html']) || $time > $_SESSION['nbe_time'])
{
	// 5 min timegap
	$_SESSION['nbe_time'] = $time + 300;
	// get NBE page html content
	$_SESSION['nbe_html'] = file_get_contents('http://www.nbe.com.eg/exchangerate.aspx');
}

// init phpQuery
phpQuery::newDocumentHTML($_SESSION['nbe_html'], 'windows-1256');

// prices array
$prices = array(
		'usd' => array(), // USD selectors
		'eur' => array(), // EUR selectors
		'aud' => array(), // AUD selectors
);

// phpQuery prices table
$table = pq('#dgPrices');

// phpQuery USD selectors
$els = $table->find('tr:eq(1) td:gt(2) input');
$prices['usd']['buy'] = floatval($els->get(0)->getAttribute('value'));
$prices['usd']['sell'] = floatval($els->get(1)->getAttribute('value'));
$prices['usd']['buy_transfer'] = floatval($els->get(2)->getAttribute('value'));
$prices['usd']['sell_transfer'] = floatval($els->get(3)->getAttribute('value'));

// phpQuery EUR selectors
$els = $table->find('tr:eq(2) td:gt(2) input');
$prices['eur']['buy'] = floatval($els->get(0)->getAttribute('value'));
$prices['eur']['sell'] = floatval($els->get(1)->getAttribute('value'));
$prices['eur']['buy_transfer'] = floatval($els->get(2)->getAttribute('value'));
$prices['eur']['sell_transfer'] = floatval($els->get(3)->getAttribute('value'));

// phpQuery AUD selectors
$els = $table->find('tr:eq(10) td:gt(2) input');
$prices['aud']['buy'] = floatval($els->get(0)->getAttribute('value'));
$prices['aud']['sell'] = floatval($els->get(1)->getAttribute('value'));
$prices['aud']['buy_transfer'] = floatval($els->get(2)->getAttribute('value'));
$prices['aud']['sell_transfer'] = floatval($els->get(3)->getAttribute('value'));

/**
 * Display data dump for something
 * 
 * @param mixed $var
 * @param boolean $data_type (Optional) if true it will dump data with data type, default false
 */
function dump_data($var, $data_type = false)
{
	echo '<pre style="color:#000;direction:ltr;text-align:left;background:#fff;padding:5px;">';
	$data_type ? var_dump($var) : print_r($var);
	echo '</pre>';
}

?><!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<title>NBE Currency Exchange for Egyptian Pound(EGP)</title>
	<link rel="stylesheet" type="text/css" href="style.css" media="all" />
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script>
	(function(window){
		// export prices to js JSON format ( JS Object )
		window.prices = <?php echo json_encode($prices); ?>;
	})(window); // self-executable anonymous function
	</script>
	<script src="script.js"></script>
</head>
<body>
	<header>
		<h1><a href="./">NBE Currency Exchange for Egyptian Pound(EGP)</a></h1>
	</header>
	<!-- Target Amount -->
	<h2>Amount</h2>
	<input type="number" value="1" id="amount" />
	<!-- Convert Results -->
	<h2>US DOLLAR</h2>
	<div class="results">
		<label>Buy: <input type="text" readonly="readonly" data-cur="usd" data-method="buy" /></label>
		<label>Sell: <input type="text" readonly="readonly" data-cur="usd" data-method="sell" /></label>
		<label>Transfers/Buy: <input type="text" readonly="readonly" data-cur="usd" data-method="buy_transfer" /></label>
		<label>Transfers/Sell: <input type="text" readonly="readonly" data-cur="usd" data-method="sell_transfer" /></label>
	</div>
	<h2>EURO</h2>
	<div class="results">
		<label>Buy: <input type="text" readonly="readonly" data-cur="eur" data-method="buy" /></label>
		<label>Sell: <input type="text" readonly="readonly" data-cur="eur" data-method="sell" /></label>
		<label>Transfers/Buy: <input type="text" readonly="readonly" data-cur="eur" data-method="buy_transfer" /></label>
		<label>Transfers/Sell: <input type="text" readonly="readonly" data-cur="eur" data-method="sell_transfer" /></label>
	</div>
	<h2>AUSTRALIAN DOLLAR</h2>
	<div class="results">
		<label>Buy: <input type="text" readonly="readonly" data-cur="aud" data-method="buy" /></label>
		<label>Sell: <input type="text" readonly="readonly" data-cur="aud" data-method="sell" /></label>
		<label>Transfers/Buy: <input type="text" readonly="readonly" data-cur="aud" data-method="buy_transfer" /></label>
		<label>Transfers/Sell: <input type="text" readonly="readonly" data-cur="aud" data-method="sell_transfer" /></label>
	</div>
	<p class="note">Note: Info updated every 5 minutes</p>
	<p class="note fork"><a href="https://github.com/N-Molham/currency_exchange">If you like it, Fork on GitHub &crarr;</a></p>
</body>
</html>