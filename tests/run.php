<?php
/**
 * Zero-dependency tests for the pure helpers. Usage: php tests/run.php
 */

define( 'ABSPATH', __DIR__ . '/' );
foreach ( [ 'Payment_Reference', 'Phone_Numbers', 'Currency_Codes', 'Credentials' ] as $pb_class ) {
	require __DIR__ . '/../classes/' . $pb_class . '.php';
}

use Papaki\PiraeusBank\WooCommerce\Payment_Reference;
use Papaki\PiraeusBank\WooCommerce\Phone_Numbers;
use Papaki\PiraeusBank\WooCommerce\Currency_Codes;
use Papaki\PiraeusBank\WooCommerce\Credentials;

$pass = 0;
$fail = 0;

function check( string $label, $expected, $actual ) {
	global $pass, $fail;
	if ( $expected === $actual ) {
		$pass++;
		echo "  ✓ {$label}\n";
	} else {
		$fail++;
		echo "  ✗ {$label}: expected " . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . "\n";
	}
}

// build()
$ref = Payment_Reference::build( 1234 );
check( 'build: format {id}-{12 upper alnum}', 1, preg_match( '/^1234-[A-Z0-9]{12}$/', $ref ) );
check( 'build: two attempts differ', true, Payment_Reference::build( 1234 ) !== Payment_Reference::build( 1234 ) );
check( 'build: fixed suffix', '77-ABC', Payment_Reference::build( 77, 'ABC' ) );
check( 'build: capped at 50 chars', 50, strlen( Payment_Reference::build( 1, str_repeat( 'A', 60 ) ) ) );

// order_id()
check( 'order_id: new format', 1234, Payment_Reference::order_id( $ref ) );
check( 'order_id: legacy plain id', 987, Payment_Reference::order_id( '987' ) );
check( 'order_id: surrounding whitespace', 987, Payment_Reference::order_id( ' 987 ' ) );
check( 'order_id: empty', 0, Payment_Reference::order_id( '' ) );
check( 'order_id: non-numeric', 0, Payment_Reference::order_id( 'abc-DEF' ) );
check( 'order_id: junk after suffix', 0, Payment_Reference::order_id( '12-AB CD' ) );
check( 'order_id: sql-ish', 0, Payment_Reference::order_id( "12' OR 1=1" ) );

// is_iris()
check( 'is_iris: CardType 15', true, Payment_Reference::is_iris( '15', '' ) );
check( 'is_iris: PaymentMethod IRIS', true, Payment_Reference::is_iris( '', 'IRIS' ) );
check( 'is_iris: PaymentMethod lower-case', true, Payment_Reference::is_iris( '', 'iris' ) );
check( 'is_iris: card', false, Payment_Reference::is_iris( '1', 'Card' ) );

// is_iris_pending()
check( 'iris pending: IRIS + 09', true, Payment_Reference::is_iris_pending( '09', '15', 'IRIS' ) );
check( 'iris pending: IRIS + 05 (declined)', false, Payment_Reference::is_iris_pending( '05', '15', 'IRIS' ) );
check( 'iris pending: card + 09', false, Payment_Reference::is_iris_pending( '09', '1', 'Card' ) );
check( 'iris pending: IRIS + 9 (unpadded)', true, Payment_Reference::is_iris_pending( '9', '', 'IRIS' ) );

// Phone_Numbers::greek()
check( 'phone: GR mobile, no prefix', [ 'type' => 'mobile', 'number' => '30-6912345678' ], Phone_Numbers::greek( '691 234 5678', 'GR' ) );
check( 'phone: GR landline, no prefix', [ 'type' => 'home', 'number' => '30-2101234567' ], Phone_Numbers::greek( '210-1234567', 'GR' ) );
check( 'phone: Athens 211 is not South Sudan', [ 'type' => 'home', 'number' => '30-2111234567' ], Phone_Numbers::greek( '2111234567', 'GR' ) );
check( 'phone: Athens 213 is not Algeria', [ 'type' => 'home', 'number' => '30-2131234567' ], Phone_Numbers::greek( '2131234567', 'GR' ) );
check( 'phone: +30 prefix, foreign address', [ 'type' => 'mobile', 'number' => '30-6981234567' ], Phone_Numbers::greek( '+30 698 123 4567', 'DE' ) );
check( 'phone: 0030 prefix', [ 'type' => 'home', 'number' => '30-2310123456' ], Phone_Numbers::greek( '0030 2310 123456', 'GR' ) );
check( 'phone: 30 + 10 digits', [ 'type' => 'mobile', 'number' => '30-6971234567' ], Phone_Numbers::greek( '306971234567', 'GR' ) );
check( 'phone: no prefix, non-GR address', null, Phone_Numbers::greek( '6912345678', 'CY' ) );
check( 'phone: wrong length', null, Phone_Numbers::greek( '69123456', 'GR' ) );
check( 'phone: Greek 800 number', null, Phone_Numbers::greek( '8001234567', 'GR' ) );
check( 'phone: +357 Cyprus', null, Phone_Numbers::greek( '+357 99 123456', 'CY' ) );
check( 'phone: empty', null, Phone_Numbers::greek( '', 'GR' ) );

// Currency_Codes::numeric()
check( 'currency: EUR', '978', Currency_Codes::numeric( 'EUR' ) );
check( 'currency: lower-case usd', '840', Currency_Codes::numeric( 'usd' ) );
check( 'currency: AUD keeps leading zero', '036', Currency_Codes::numeric( 'AUD' ) );
check( 'currency: unknown', '', Currency_Codes::numeric( 'XYZ' ) );

// Credentials
$digest = md5( 'secret' );
check( 'credentials: new password stored as digest', 'md5:' . $digest, Credentials::to_store( 'secret', '' ) );
check( 'credentials: blank keeps stored digest', 'md5:' . $digest, Credentials::to_store( '', 'md5:' . $digest ) );
check( 'credentials: blank converts legacy plain', 'md5:' . $digest, Credentials::to_store( '', 'secret' ) );
check( 'credentials: blank with nothing stored', '', Credentials::to_store( '', '' ) );
check( 'credentials: new password replaces digest', 'md5:' . md5( 'other' ), Credentials::to_store( 'other', 'md5:' . $digest ) );
check( 'credentials: digest from stored digest', $digest, Credentials::digest( 'md5:' . $digest ) );
check( 'credentials: digest from legacy plain', $digest, Credentials::digest( 'secret' ) );
check( 'credentials: digest of nothing', '', Credentials::digest( '' ) );
check( 'credentials: plain password that starts with md5:', md5( 'md5:short' ), Credentials::digest( 'md5:short' ) );

echo "\n{$pass} passed, {$fail} failed\n";
exit( $fail > 0 ? 1 : 0 );
