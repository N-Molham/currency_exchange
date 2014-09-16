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
if ( '' == session_id() )
	session_start();

// require phpQuery
require dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'phpQuery-onefile.php';

// update timegap setup
if ( !isset( $_SESSION['nbe_time'] ) )
	$_SESSION['nbe_time'] = 0;

// init convert amount
$init_amount = filter_input( INPUT_GET, 'amount', FILTER_SANITIZE_NUMBER_FLOAT );
if ( !$init_amount )
	$init_amount = 1;

// get NBE table
$time = time();
if ( !isset( $_SESSION['nbe_html'] ) || $time > $_SESSION['nbe_time'] || isset( $_REQUEST['force_reload'] ) )
{
	// 5 min timegap
	$_SESSION['nbe_time'] = $time + 300;

	// get NBE page html content
	$_SESSION['nbe_html'] = @file_get_contents( 'http://www.nbe.com.eg/exchangerate.aspx' );
	if ( false === $_SESSION['nbe_html'] )
	{
		// display error if network error happened
		die( '<h1>Connection Error</h1>' );
	}
}

// init phpQuery
phpQuery::newDocumentHTML( $_SESSION['nbe_html'], 'windows-1256' );

// prices array
$prices = array (
		'usd' => array (
			'title' => 'US DOLLAR',
			'unit' => '$',
			'selector' => 'tr:eq(1) td:gt(2) input',
		), // USD selectors
		'eur' => array (
			'title' => 'EURO',
			'unit' => '&#128;',
			'selector' => 'tr:eq(2) td:gt(2) input',
		), // EUR selectors
		'aud' => array (
			'title' => 'AUSTRALIAN DOLLAR',
			'unit' => 'AU$',
			'selector' => 'tr:eq(10) td:gt(2) input',
		), // AUD selectors
);

// phpQuery prices table
$table = pq( '#dgPrices' );

// selectors holder
$els = null;

// prices values loop
foreach ( $prices as $key => $args ) 
{
	// phpQuery selectors
	$els = $table->find( $args['selector'] );

	// check match length
	if ( $els->length() )
	{
		// match found
		$prices[$key]['buy'] = floatval( $els->get(0)->getAttribute('value') );
		$prices[$key]['sell'] = floatval( $els->get(1)->getAttribute('value') );
		$prices[$key]['buy_transfer'] = floatval( $els->get(2)->getAttribute('value') );
		$prices[$key]['sell_transfer'] = floatval( $els->get(3)->getAttribute('value') );
	}
	else
	{
		// not match found
		$prices[$key]['buy'] = $prices[$key]['sell'] = $prices[$key]['buy_transfer'] = $prices[$key]['sell_transfer'] = 0;
	}
}

/**
 * Display data dump for something
 * 
 * @param mixed $var
 * @param boolean $data_type (Optional) if true it will dump data with data type, default false
 */
function dump_data( $var, $data_type = false )
{
	echo '<pre style="color:#000;direction:ltr;text-align:left;background:#fff;padding:5px;">';
	$data_type ? var_dump( $var ) : print_r( $var );
	echo '</pre>';
}

?><!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<title>NBE Currency Exchange for Egyptian Pound(EGP)</title>
	<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>
<body>
	<header>
		<h1><a href="./">NBE Currency Exchange for Egyptian Pound(EGP)</a></h1>
	</header>

	<!-- Target Amount -->
	<h2>Foreign Currency Amount</h2>
	<input type="number" value="<?php echo (float) $init_amount; ?>" id="amount" />

	<!-- Convert Results -->
	<?php
	// prices layouts loop
	foreach ( $prices as $key => $args ) 
	{
		echo '<h2 class="currency-title" data-cur="', $key ,'">', $args['title'] ,': <span></span></h2>';
		echo '<div class="results">';
		echo '<label>Buy: <input type="text" readonly data-cur="', $key ,'" data-method="buy" /></label>';
		echo '<label>Sell: <input type="text" readonly data-cur="', $key ,'" data-method="sell" /></label>';
		echo '<label>Transfers/Buy: <input type="text" readonly data-cur="', $key ,'" data-method="buy_transfer" /></label>';
		echo '<label>Transfers/Sell: <input type="text" readonly data-cur="', $key ,'" data-method="sell_transfer" /></label></div>';
	}
	?>

	<!-- footer -->
	<p class="note">Note: Info updated every 5 minutes</p>
	<a href="https://github.com/N-Molham/currency_exchange" class="fork">
		<img src="https://s3.amazonaws.com/github/ribbons/forkme_right_gray_6d6d6d.png" alt="Fork me on GitHub">
	</a>

	<!-- JS code -->
	<script>
	// export prices to js JSON format ( JS Object )
	var prices = <?php echo json_encode( $prices ); ?>;
	</script>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script src="script.js"></script>
</body>
</html>