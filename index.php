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
 * @version 2.0
 **/

date_default_timezone_set( 'Africa/Cairo' );

if ( '' === session_id() ) {
	// start session to save prices
	session_start();
}

// composer packages
require_once __DIR__ . '/vendor/autoload.php';

/**
 * @return string
 *
 * @throws Exception
 */
function S4(): string {

	$string = 'abcdef0123456789';

	$s4 = $string[ random_int( 1, 15 ) ];
	$s4 .= $string[ random_int( 1, 15 ) ];
	$s4 .= $string[ random_int( 1, 15 ) ];
	$s4 .= $string[ random_int( 1, 15 ) ];

	return $s4;

}

/**
 * @return string
 *
 * @throws Exception
 */
function generate_track_id(): string {

	return S4() . S4() . '-' . S4() . '-4' . substr( S4(), 0, 3 ) .
	       '-' . S4() . '-' . S4() . S4() . S4();

}

/**
 * @param string $file_name
 * @param int    $current_time
 * @param int    $cache_duration
 *
 * @return array
 * @throws Exception
 */
function nbe_load_html( $file_name, $current_time, $cache_duration = 3600 ): array {

	/** @noinspection PhpUnhandledExceptionInspection */
	$track_id  = generate_track_id();
	$timestamp = date( 'Y-m-d\TH:i:s' );

	if ( file_exists( $file_name ) && is_readable( $file_name ) ) {

		/** @noinspection PhpComposerExtensionStubsInspection */
		$cached_data = json_decode( file_get_contents( $file_name ), true );

		if ( is_array( $cached_data ) && $cached_data['time'] + $cache_duration > $current_time ) {

			return $cached_data;

		}

	}

	$input = [
		'__type' => 'eChannelManagerBusinessXML.eBank',
		'Item'   => [
			'__type' => 'eChannelManagerBusinessXML.schExchangeRateGetRates',
			'Body'   => [
				'__type'                        =>
					'eChannelManagerBusinessXML.schExchangeRateGetRatesBody',
				'ExchangeRateGetRatesReqParams' => [
					'__type'            =>
						'eChannelManagerBusinessXML.schExchangeRateGetRatesBodyExchangeRateGetRatesReqParams',
					'HighLightCurrency' => false,
					'RequestType'       => 'ExchangeRates',
				],
			],
			'Header' => [
				'__type'                  => 'eChannelManagerBusinessXML.Header',
				'Customer'                => [
					'__type'               => 'eChannelManagerBusinessXML.CustomerHeaderInfoType',
					'CustomerID'           => '',
					'CustomerPin'          => '',
					'CustDeviceID'         => '127.0.0.11',
					'CustLoginIDOnChannel' => '_',
				],
				'FrontEnd'                => [
					'FrontEndID'       => 'eCM-Web',
					'FrontEndType'     => 'eCM-Web',
					'FrontEndPassword' => '',
				],
				'Audit'                   => [
					'__type'         => 'eChannelManagerBusinessXML.AuditHeaderInfoType',
					'TransactionObj' => [
						'__type'            => 'eChannelManagerBusinessXML.TransactionObjType',
						'MasterLogLevel'    => '',
						'TransactionID'     => $track_id,
						'TransactionPath'   => 'ExchangeRateGetRates',
						'TransactionType'   => 'ExchangeRateGetRates',
						'TransCustID'       => $timestamp,
						'TransFrontEndID'   => 'eCM-Mobile',
						'TransFrontEndType' => 'eCM-Web',
					],
					'SessionObj'     => [
						'__type'       => 'eChannelManagerBusinessXML.SessionObjType',
						'SessionObjID' => 'Session_',
					],
				],
				'User'                    => [
					'__type'  => 'eChannelManagerBusinessXML.UserHeaderInfoType',
					'UserID'  => '',
					'UserPin' => '',
				],
				'MemoList'                => [
					'MemoItem1' => '',
					'MemoItem6' => '',
					'MemoItem7' => '',
					'MemoItem8' => '',
					'MemoItem2' => 'false',
				],
				'Service'                 => [
					'__type'                      => 'eChannelManagerBusinessXML.ServiceHeaderInfoType',
					'ServiceID'                   => 'ExchangeRateGetRates',
					'ServiceMessageType'          => 'ExchangeRateGetRates',
					'ServiceRequestID'            => $track_id,
					'ServiceRequestLanguageCode'  => 'EN',
					'ServiceRequestTime'          => $timestamp . '.000Z',
					'ServiceRequestTimeSpecified' => true,
					'ServiceResult'               => [
						'__type'     =>
							'eChannelManagerBusinessXML.ResultHeaderInfoType',
						'ResultCode' => '0',
						'ResultDesc' => '',
					],
				],
				'CachingAndExpiryControl' => [
					'__type'            =>
						'eChannelManagerBusinessXML.HeaderCachingAndExpiryControl',
					'DataHashSignature' => '',
				],
			],
		],
	];

	/** @noinspection PhpComposerExtensionStubsInspection */
	$response = json_decode( file_get_contents(
		'https://www.nbe.com.eg/NBEeChannelManager/CallMW.aspx?TrackID=' . $track_id,
		false,
		stream_context_create( [
			'http' => [
				'method'  => 'POST',
				'header'  =>
					'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
				'content' => http_build_query( [
					'I_InputObjectJSONStr' => json_encode( $input ),
				] ),
			],
		] )
	), true );

	$data = [
		'time' => time(),
		'list' => @$response['RspObj']['Item']['Body']['ExchangeRateGetRatesRspParams']['Currencies'],
	];

	if ( empty( $data['list'] ) ) {

		if ( isset( $cached_data ) && is_array( $cached_data ) ) {

			return $cached_data;

		}

		// display error if network error happened
		die( '<h1>Unable ot load data from NBE website!</h1>' );

	}

	/** @noinspection PhpComposerExtensionStubsInspection */
	file_put_contents( $file_name, json_encode( $data ) );

	return $data;

}

$init_amount = filter_input( INPUT_GET, 'amount', FILTER_SANITIZE_NUMBER_FLOAT ) ?? 1;

$current_time = time();

/** @noinspection PhpUnhandledExceptionInspection */
$prices_data = nbe_load_html( 'nbe.temp', $current_time );

$prices = [];

$wanted_currencies = [ 'USD', 'EURO', 'GBP', 'AUD', 'CAD', 'SAR', 'AED' ];

// prices values loop
foreach ( $prices_data['list'] as $currency ) {

	$currency_code = $currency['CurrencyISOCode'];

	if ( false === in_array( $currency_code, $wanted_currencies, true ) ) {

		continue;

	}

	$prices[ $currency_code ]['code']          = $currency_code;
	$prices[ $currency_code ]['title']         = $currency['Name_AR'] . ' - ' . $currency['Name_EN'];
	$prices[ $currency_code ]['buy']           = $currency['CashBuyRate'] ?? 0;
	$prices[ $currency_code ]['sell']          = $currency['CashSellRate'] ?? 0;
	$prices[ $currency_code ]['buy_transfer']  = $currency['TransferBuyRate'] ?? 0;
	$prices[ $currency_code ]['sell_transfer'] = $currency['TransferSellRate'] ?? 0;

}

?><!DOCTYPE html>
<html lang="en">
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

	echo '<h2 class="currency-title" data-cur="', $currency_code, '">', $args['title'], ' : <span></span></h2>';
	echo '<div class="results">';
	echo '<label>Buy: <input type="text" readonly data-cur="', $currency_code, '" data-method="buy" /></label>';
	echo '<label>Sell: <input type="text" readonly data-cur="', $currency_code, '" data-method="sell" /></label>';
	echo '<label>Transfers/Buy: <input type="text" readonly data-cur="', $currency_code, '" data-method="buy_transfer" /></label>';
	echo '<label>Transfers/Sell: <input type="text" readonly data-cur="', $currency_code, '" data-method="sell_transfer" /></label></div>';

}
?>
<!-- footer -->
<p class="note">Note: Info updated every 1 hour</p>
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