<?php
/**
 * Enforce application-level safety for the public staging read surface.
 */

function hectv_public_read_only_enabled()
{
    return getenv('HECTV_PUBLIC_READ_ONLY') === '1';
}

function hectv_public_read_only_reject_graphql_mutations()
{
    if (!hectv_public_read_only_enabled()) {
        return;
    }

    $path = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH);
    if (rtrim($path, '/') !== '/graphql') {
        return;
    }

    $queries = array();
    if (strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET') === 'GET') {
        $queries[] = isset($_GET['query']) ? $_GET['query'] : '';
    } else {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (isset($payload['query'])) {
            $queries[] = $payload['query'];
        } elseif (is_array($payload)) {
            foreach ($payload as $operation) {
                $queries[] = isset($operation['query']) ? $operation['query'] : '';
            }
        }
    }

    foreach ($queries as $query) {
        if (!is_string($query) || preg_match('/(^|[\s,{])mutation([\s({]|$)/i', $query)) {
            status_header(403);
            exit('GraphQL mutations are disabled on public staging.');
        }
    }
}
add_action('parse_request', 'hectv_public_read_only_reject_graphql_mutations', 0);

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
