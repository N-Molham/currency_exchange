<?php
/**
 * NBE Currency Exchange for Egyptian Pound(EGP)
 *
 * A way to read currencies' prices from National Bank of Egypt website.
 * Using phpQuery to parse html content of the exchange rate page to get values,
 * Hope you find it helpful :)
 *
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @author Nabeel Molham - n.molham@gmail.com
 * @version 1.0
 **/

if ( '' == session_id() )
{
	// start session to save prices
	session_start();
}

// composer packages
require_once dirname( __FILE__ ) . '/vendor/autoload.php';

if ( !isset( $_SESSION['nbe_time'] ) )
{
	// update time gap setup
	$_SESSION['nbe_time'] = 0;
}

$init_amount = filter_input( INPUT_GET, 'amount', FILTER_SANITIZE_NUMBER_FLOAT );
if ( !$init_amount )
{
	// init convert amount
	$init_amount = 1;
}

// get NBE table
$time = time();
if ( !isset( $_SESSION['nbe_html'] ) || $time > $_SESSION['nbe_time'] || isset( $_REQUEST['force_reload'] ) )
{
	// 5 min timegap
	$_SESSION['nbe_time'] = $time + 300;

	// get NBE page html content
	$_SESSION['nbe_html'] = @file_get_contents( 'http://www.nbe.com.eg/en/exchangerate.aspx' );
	if ( false === $_SESSION['nbe_html'] )
	{
		// display error if network error happened
		die( '<h1>Connection Error</h1>' );
	}
}

// init phpQuery
$dom = pQuery::parseStr( $_SESSION['nbe_html'] );

// prices array
$prices = [
	'usd' => [
		'title'    => 'US DOLLAR',
		'code'     => '#dgPrices tr:nth-child(2) td:nth-child(2)',
		'selector' => [
			'buy'           => '#dgPrices tr:nth-child(2) td:nth-child(3)',
			'sell'          => '#dgPrices tr:nth-child(2) td:nth-child(4)',
			'buy_transfer'  => '#dgPrices tr:nth-child(2) td:nth-child(5)',
			'sell_transfer' => '#dgPrices tr:nth-child(2) td:nth-child(6)',
		],
	], // USD selectors
	'eur' => [
		'title'    => 'EURO',
		'code'     => '#dgPrices tr:nth-child(3) td:nth-child(2)',
		'selector' => [
			'buy'           => '#dgPrices tr:nth-child(3) td:nth-child(3)',
			'sell'          => '#dgPrices tr:nth-child(3) td:nth-child(4)',
			'buy_transfer'  => '#dgPrices tr:nth-child(3) td:nth-child(5)',
			'sell_transfer' => '#dgPrices tr:nth-child(3) td:nth-child(6)',
		],
	], // EUR selectors
	'aud' => [
		'title'    => 'AUSTRALIAN DOLLAR',
		'code'     => '#dgPrices tr:nth-child(11) td:nth-child(2)',
		'selector' => [
			'buy'           => '#dgPrices tr:nth-child(11) td:nth-child(3)',
			'sell'          => '#dgPrices tr:nth-child(11) td:nth-child(4)',
			'buy_transfer'  => '#dgPrices tr:nth-child(11) td:nth-child(5)',
			'sell_transfer' => '#dgPrices tr:nth-child(11) td:nth-child(6)',
		],
	], // AUD selectors
];

// selectors holder
$els = null;

// prices values loop
foreach ( $prices as $key => $args )
{
	// unit code
	$prices[ $key ]['code'] = trim( $dom->query( $args['code'] )->text() );

	// rates
	foreach ( $args['selector'] as $selector_name => $selector_query )
	{
		// query selectors
		$selector_val                     = trim( $dom->query( $selector_query )->text() );
		$prices[ $key ][ $selector_name ] = empty( $selector_val ) ? 0 : floatval( $selector_val );
	}
	unset( $selector_name, $selector_query, $selector_val );
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
	echo '<h2 class="currency-title" data-cur="', $key, '">', $args['title'], ': <span></span></h2>';
	echo '<div class="results">';
	echo '<label>Buy: <input type="text" readonly data-cur="', $key, '" data-method="buy" /></label>';
	echo '<label>Sell: <input type="text" readonly data-cur="', $key, '" data-method="sell" /></label>';
	echo '<label>Transfers/Buy: <input type="text" readonly data-cur="', $key, '" data-method="buy_transfer" /></label>';
	echo '<label>Transfers/Sell: <input type="text" readonly data-cur="', $key, '" data-method="sell_transfer" /></label></div>';
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
<script src="script.min.js"></script>
</body>
</html>