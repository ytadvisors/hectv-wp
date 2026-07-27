<?php

putenv('HECTV_ENVIRONMENT=staging');

$registered_actions = array();

function add_action($hook, $callback)
{
    global $registered_actions;
    $registered_actions[] = $hook;
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

echo "HEC staging content controls tests passed.\n";
