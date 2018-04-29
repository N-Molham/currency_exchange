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

if ( '' === session_id() ) {
	// start session to save prices
	session_start();
}

// composer packages
require_once __DIR__ . '/vendor/autoload.php';

$init_amount = filter_input( INPUT_GET, 'amount', FILTER_SANITIZE_NUMBER_FLOAT );
if ( ! $init_amount ) {
	// init convert amount
	$init_amount = 1;
}

$current_time = time();
$page_data = nbe_load_html( 'nbe.temp', $current_time );

/**
 * @param string $file_name
 * @param int    $current_time
 * @param int    $cache_duration
 *
 * @return array
 */
function nbe_load_html( $file_name, $current_time, $cache_duration = 3600 ) {

	if ( file_exists( $file_name ) && is_readable( $file_name ) ) {

		$data = json_decode( file_get_contents( $file_name ), true );

		if ( is_array( $data ) && $data['time'] + $cache_duration > $current_time ) {

			return $data;

		}

	}

	$data = [
		'time' => time(),
		'html' => file_get_contents( 'http://www.nbe.com.eg/en/ExchangeRate.aspx' ),
	];

	if ( empty( $data['html'] ) ) {

		// display error if network error happened
		die( '<h1>Unable ot load data from NBE website!</h1>' );

	}

	file_put_contents( $file_name, json_encode( $data ) );

	return $data;
}

// init phpQuery
$dom = pQuery::parseStr( $page_data['html'] );

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
foreach ( $prices as $currency_code => $args ) {

	// unit code
	$prices[ $currency_code ]['code'] = trim( $dom->query( $args['code'] )->text() );

	// rates
	foreach ( $args['selector'] as $selector_name => $selector_query ) {

		// query selectors
		$selector_val = trim( $dom->query( $selector_query )->text() );

		$prices[ $currency_code ][ $selector_name ] = empty( $selector_val ) ? 0 : (float) $selector_val;

	}

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
foreach ( $prices as $currency_code => $args ) {
	echo '<h2 class="currency-title" data-cur="', $currency_code, '">', $args['title'], ': <span></span></h2>';
	echo '<div class="results">';
	echo '<label>Buy: <input type="text" readonly data-cur="', $currency_code, '" data-method="buy" /></label>';
	echo '<label>Sell: <input type="text" readonly data-cur="', $currency_code, '" data-method="sell" /></label>';
	echo '<label>Transfers/Buy: <input type="text" readonly data-cur="', $currency_code, '" data-method="buy_transfer" /></label>';
	echo '<label>Transfers/Sell: <input type="text" readonly data-cur="', $currency_code, '" data-method="sell_transfer" /></label></div>';
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