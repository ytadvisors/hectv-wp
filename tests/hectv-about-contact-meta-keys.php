<?php
/**
 * Behavioral contract for git-canonical About/Contact ACF + GraphQL ownership.
 *
 * Run: php tests/hectv-about-contact-meta-keys.php
 */

define( 'ABSPATH', __DIR__ );

$actions        = array();
$filters        = array();
$graphql_types  = array();
$graphql_fields = array();

function add_action( $hook, $callback, $priority = 10 ) {
	global $actions;
	$actions[ $hook ][ $priority ][] = $callback;
}

function add_filter( $hook, $callback, $priority = 10 ) {
	global $filters;
	$filters[ $hook ][ $priority ][] = $callback;
}

function register_graphql_object_type( $name, $config ) {
	global $graphql_types;
	$graphql_types[ $name ] = $config;
}

function register_graphql_field( $type, $name, $config ) {
	global $graphql_fields;
	$graphql_fields[ $type ][ $name ] = $config;
}

function get_post_meta( $post_id, $key, $single = true ) {
	return '';
}

function get_field( $key, $post_id = false ) {
	if ( (int) $post_id !== 42 ) {
		return null;
	}
	$values = array(
		'address'                   => '3221 McKelvey Rd.',
		'phone_number'              => '314.531.4455',
		'fax_number'                => '314.531.0750',
		'video_id'                  => '300584603',
		'team'                      => array(
			array(
				'name'     => 'Jayne Ballew',
				'email'    => 'jayne@hectv.org',
				'position' => 'Director of Programming',
				'photo'    => array( 'ID' => 99 ),
			),
		),
		'tv_providers'              => array(
			array( 'provider' => 'Channel 11', 'channel' => '11' ),
		),
		'partner_logos'             => array(
			array( 'partner_logo' => array( 'ID' => 99 ), 'partner_link' => 'https://example.test' ),
		),
		'public_school_partners'    => array( array( 'partner' => 'Public School' ) ),
		'higher_education_partners' => array( array( 'partner' => 'University' ) ),
		'board_of_directors'        => array(
			array( 'name' => 'Director', 'position' => 'Chair', 'school' => 'HEC' ),
		),
		'directions'                => 'Turn left at McKelvey Rd.',
		'opportunities'             => 'Partner with HEC-TV.',
		'contact_subjects'          => array(
			array( 'subject' => 'Programming', 'e-mail' => 'info@hectv.org' ),
		),
		// ACF image return_format=array must remain supported by canonical media helpers.
		'post_header'               => array( 'ID' => 99, 'url' => 'https://example.test/header.jpg' ),
	);
	return array_key_exists( $key, $values ) ? $values[ $key ] : null;
}

class WP_Post {
	public $ID;
	public $post_type;
	public $post_status;
	public function __construct( $id, $post_type = 'page' ) {
		$this->ID          = (int) $id;
		$this->post_type   = $post_type;
		$this->post_status = 'publish';
	}
}

function get_post( $post_id ) {
	return (int) $post_id === 99 ? new WP_Post( 99, 'attachment' ) : null;
}

if ( ! class_exists( '\\WPGraphQL\\Model\\Post', false ) ) {
	eval( 'namespace WPGraphQL\\Model; class Post { public $ID; public function __construct( $post ) { $this->ID = (int) $post->ID; } }' );
}

function expect_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, "$message\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

function expect_true( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "$message\n" );
		exit( 1 );
	}
}

require dirname( __DIR__ ) . '/wp-content/mu-plugins/hectv-cms-fields/graphql.php';

foreach ( $actions['graphql_register_types'] as $callbacks ) {
	foreach ( $callbacks as $callback ) {
		$callback();
	}
}

expect_true( isset( $graphql_fields['Page']['about'] ), 'canonical plugin registers Page.about' );
expect_true( isset( $graphql_fields['Page']['contact'] ), 'canonical plugin registers Page.contact' );

foreach ( array( 'partnerLogos', 'publicSchoolPartners', 'higherEducationPartners', 'boardOfDirectors' ) as $field ) {
	expect_true( isset( $graphql_types['HecAbout']['fields'][ $field ] ), "HecAbout includes production field $field" );
}
expect_true( isset( $graphql_types['HecContact']['fields']['contactSubjects'] ), 'HecContact includes production contactSubjects' );
expect_true( isset( $graphql_types['HecTeamMember']['fields']['photo'] ), 'HecTeamMember includes production photo field' );

$source  = (object) array( 'databaseId' => 42 );
$about   = $graphql_fields['Page']['about']['resolve']( $source );
$contact = $graphql_fields['Page']['contact']['resolve']( $source );

expect_same( '314.531.4455', $about['phoneNumber'], 'About phone uses canonical phone_number ACF key' );
expect_same( '314.531.0750', $about['faxNumber'], 'About fax uses canonical fax_number ACF key' );
expect_same( '300584603', $about['videoId'], 'About video uses canonical video_id ACF key' );
expect_same( 'Jayne Ballew', $about['team'][0]['name'], 'About team repeater resolves formatted ACF rows' );
expect_same( 99, $about['team'][0]['photo']->ID, 'About team photo resolves ACF image arrays' );
expect_same( 'Channel 11', $about['tvProviders'][0]['provider'], 'About TV providers resolve' );
expect_same( 'https://example.test', $about['partnerLogos'][0]['partnerLink'], 'About partner logos resolve' );
expect_same( 'University', $about['higherEducationPartners'][0]['partner'], 'About higher education partners resolve' );
expect_same( 'Director', $about['boardOfDirectors'][0]['name'], 'About board rows resolve' );

expect_same( '3221 McKelvey Rd.', $contact['address'], 'Contact address uses canonical ACF key' );
expect_same( 'Partner with HEC-TV.', $contact['opportunities'], 'Contact opportunities resolve' );
expect_same( 'info@hectv.org', $contact['contactSubjects'][0]['eMail'], 'Contact subject e-mail maps to production camelCase field' );

$media = hectv_cms_gql_media_model( hectv_cms_gql_meta( 42, 'post_header', null ) );
expect_same( 99, $media->ID, 'shared canonical media helper accepts ACF image-array return values' );
expect_same( array( array( 'name' => 'Legacy' ) ), hectv_cms_gql_rows( '[{"name":"Legacy"}]' ), 'legacy JSON repeater rows remain readable' );

echo "About/Contact canonical ACF + GraphQL contracts passed.\n";
