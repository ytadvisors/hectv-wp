<?php
/**
 * Enforce application-level safety for the public staging read surface.
 */

function hectv_public_read_only_enabled()
{
    return getenv('HECTV_PUBLIC_READ_ONLY') === '1';
}

add_filter('authenticate', function ($user) {
    if (!hectv_public_read_only_enabled()) {
        return $user;
    }

    return new WP_Error(
        'hectv_staging_auth_disabled',
        'Authentication is disabled on public staging.',
        array('status' => 403)
    );
}, PHP_INT_MAX);

add_filter('rest_pre_dispatch', function ($result, $server, $request) {
    if (!hectv_public_read_only_enabled()) {
        return $result;
    }

    $method = strtoupper($request->get_method());
    if (in_array($method, array('GET', 'HEAD', 'OPTIONS'), true)) {
        return $result;
    }

    return new WP_Error(
        'hectv_staging_read_only',
        'Write operations are disabled on public staging.',
        array('status' => 403)
    );
}, 1, 3);
