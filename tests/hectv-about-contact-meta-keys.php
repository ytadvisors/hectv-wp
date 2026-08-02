<?php
/**
 * Structural: Page about/contact resolvers must use live ACF field names.
 * Run: php tests/hectv-about-contact-meta-keys.php
 */
$src = file_get_contents( dirname( __DIR__ ) . '/staging-harness/mu-plugins/hectv-graphql-compat.php' );
if ( $src === false ) {
	fwrite( STDERR, "missing hectv-graphql-compat.php\n" );
	exit( 1 );
}

$required = array(
	"'phone_number'",
	"'fax_number'",
	"'video_id'",
	"'tv_providers'",
	"'directions'",
	"'opportunities'",
	// primary ACF names appear as pick() first args
	"\$pick( \$source, 'address'",
	"\$pick( \$source, 'team'",
	"function_exists( 'get_field' )",
);

foreach ( $required as $needle ) {
	if ( strpos( $src, $needle ) === false ) {
		fwrite( STDERR, "FAIL missing: $needle\n" );
		exit( 1 );
	}
}

echo "OK about/contact meta keys use live ACF names + get_field\n";
