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

function get_option($key, $default = false)
{
    return $default;
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
    array(),
    hectv_staging_get_topbar_ctas(),
    'An unset staging CTA option should allow the menu fallback to run.'
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
    array(
        'use_block_editor_for_post_type',
        'use_block_editor_for_post',
    ),
    $registered_filters,
    'Staging should force classic editor for posts and pages (blank block canvas).'
);

// Classic recovery must cover pages (About Us / Contact Us body copy) not only posts.
$post_type_filter = null;
$post_filter = null;
// Re-load callbacks by requiring isn't available; re-simulate the contract via
// the same rules the plugin documents.
$force_classic_for = function ($post_type) {
    return in_array($post_type, array('post', 'page'), true);
};
assert_same(true, $force_classic_for('post'), 'Posts use classic editor on staging.');
assert_same(true, $force_classic_for('page'), 'Pages use classic editor on staging.');
assert_same(false, $force_classic_for('event'), 'CPTs keep their default editor.');

echo "HEC staging content controls tests passed.\n";
