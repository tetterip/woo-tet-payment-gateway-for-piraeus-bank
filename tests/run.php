<?php
/**
 * Zero-dependency tests for the pure helpers. Usage: php tests/run.php
 */

define( 'ABSPATH', __DIR__ . '/' );
require __DIR__ . '/../classes/Payment_Reference.php';

use Papaki\PiraeusBank\WooCommerce\Payment_Reference;

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

echo "\n{$pass} passed, {$fail} failed\n";
exit( $fail > 0 ? 1 : 0 );
