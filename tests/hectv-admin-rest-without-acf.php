<?php

// The production REST API must remain readable when ACF is not active.
if ( function_exists( 'get_field' ) ) {
    fwrite( STDERR, "Test requires get_field() to be unavailable.\n" );
    exit( 1 );
}

require_once dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv/hectv_admin.php';

$reflection = new ReflectionClass( 'HECTV\\HECTV_Admin' );
$admin = $reflection->newInstanceWithoutConstructor();
$thumbnail = $admin->get_thumbnail( array( 'id' => 123 ), 'thumbnail', null );

if ( '' !== $thumbnail ) {
    fwrite( STDERR, "Expected an empty optional thumbnail without ACF.\n" );
    exit( 1 );
}

echo "hectv admin REST ACF fallback passed\n";
