<?php
/**
 * Regression checks for committed vendor code loaded during WordPress bootstrap.
 * Run: php tests/php82-vendor-compat.php
 */

$inflections = dirname( __DIR__ ) . '/vendor/icanboogie/inflector/lib/inflections.php';
$source      = file_get_contents( $inflections );

if ( $source === false ) {
	fwrite( STDERR, "Unable to read Inflector source.\n" );
	exit( 1 );
}

if ( strpos( $source, '$rule{0}' ) !== false ) {
	fwrite( STDERR, "Inflector still uses curly-brace string offsets removed in PHP 8.\n" );
	exit( 1 );
}

echo "PHP 8 vendor compatibility checks passed.\n";
