<?php

putenv('HECTV_ENVIRONMENT=staging');

$registered_actions = array();
$registered_filters = array();

function add_action($hook, $callback)
{
    global $registered_actions;
    $registered_actions[] = $hook;
}

function add_filter($hook, $callback)
{
    global $registered_filters;
    $registered_filters[] = $hook;
}

function sanitize_key($value)
{
    return strtolower(preg_replace('/[^a-z0-9_-]/i', '', (string) $value));
}

function sanitize_text_field($value)
{
    return trim(strip_tags((string) $value));
}

function esc_url_raw($value)
{
    $value = trim((string) $value);
    return preg_match('#^(https?://|/)#', $value) ? $value : '';
}

function absint($value)
{
    return abs((int) $value);
}

require dirname(__DIR__) . '/wp-content/mu-plugins/hectv-staging-content-controls.php';

function assert_same($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(
            STDERR,
            $message
            . "\nExpected: "
            . var_export($expected, true)
            . "\nActual: "
            . var_export($actual, true)
            . "\n"
        );
        exit(1);
    }
}

assert_same(
    'medium',
    hectv_staging_sanitize_header_image_size('MEDIUM'),
    'Known header-image sizes should be normalized.'
);
assert_same(
    'full',
    hectv_staging_sanitize_header_image_size('oversized'),
    'Unknown header-image sizes should fall back to full.'
);

assert_same(
    array(
        array(
            'label' => 'Subscribe',
            'url' => '/subscribe',
            'style' => 'primary',
        ),
        array(
            'label' => 'Support',
            'url' => '/support',
            'style' => 'secondary',
        ),
    ),
    hectv_staging_default_topbar_ctas(),
    'Default staging actions should omit Get Involved.'
);

$site_content = hectv_staging_sanitize_site_content(
    array(
        'forEducators' => array(
            'imageUrl' => 'javascript:alert(1)',
            'destinationUrl' => '/educators',
        ),
        'trendingPostIds' => '9, 8, 9, 7, 6, 5',
        'spotlightTitle' => ' Spotlight STL ',
        'footerLinks' => array(
            array('label' => 'Culture', 'url' => '/category/culture'),
        ),
        'mobileRailFirst' => '1',
    )
);

assert_same(
    HECTV_FOR_EDUCATORS_APPROVED_IMAGE,
    $site_content['forEducators']['imageUrl'],
    'Unsafe educator images should fall back to the approved asset.'
);
assert_same(
    '/educators',
    $site_content['forEducators']['destinationUrl'],
    'Safe educator destinations should be retained.'
);
assert_same(
    array(9, 8, 7, 6),
    $site_content['trendingPostIds'],
    'Trending selections should be unique, ordered, and capped at four.'
);
assert_same(
    'Spotlight STL',
    $site_content['spotlightTitle'],
    'Spotlight titles should be sanitized.'
);
assert_same(
    array(
        array('label' => 'Culture', 'url' => '/category/culture'),
        array('label' => 'Education', 'url' => '/category/education'),
        array('label' => 'Business', 'url' => '/category/business'),
    ),
    $site_content['footerLinks'],
    'Missing footer rows should retain safe defaults.'
);
assert_same(
    true,
    $site_content['mobileRailFirst'],
    'Mobile rail ordering should be stored as a boolean.'
);

$ctas = hectv_staging_sanitize_topbar_ctas(
    array(
        array(
            'label' => ' Subscribe ',
            'url' => '/subscribe',
            'style' => 'primary',
        ),
        array(
            'label' => 'Missing style',
            'url' => '/invalid',
            'style' => '',
        ),
        array(
            'label' => 'Support',
            'url' => 'https://hecmedia.org/support',
            'style' => 'secondary',
        ),
    )
);

assert_same(
    array(
        array(
            'label' => 'Subscribe',
            'url' => '/subscribe',
            'style' => 'primary',
        ),
        array(
            'label' => 'Support',
            'url' => 'https://hecmedia.org/support',
            'style' => 'secondary',
        ),
    ),
    $ctas,
    'Only complete, valid CTA rows should be retained.'
);

assert_same(
    array(
        'init',
        'graphql_register_types',
        'admin_menu',
        'add_meta_boxes',
        'save_post',
    ),
    $registered_actions,
    'The staging plugin should register all CMS and API hooks.'
);
assert_same(
    array('use_block_editor_for_post_type'),
    $registered_filters,
    'Staging should register the classic editor recovery filter.'
);

echo "HEC staging content controls tests passed.\n";
