<?php
/**
 * Plugin Name: HEC staging content controls
 * Description: Staging-only controls for HEC site content and article editing.
 */

if (getenv('HECTV_ENVIRONMENT') !== 'staging') {
    return;
}

define('HECTV_HEADER_IMAGE_SIZE_META', 'hectv_header_image_size');
define('HECTV_TOPBAR_CTAS_OPTION', 'hectv_topbar_ctas');
define('HECTV_SITE_CONTENT_OPTION', 'hectv_site_content');
define(
    'HECTV_FOR_EDUCATORS_APPROVED_IMAGE',
    'https://asset.ytadvisors.com/client-documents/hecmedia/media-library/3ca97ec68430409a-For-Educators.jpg'
);

function hectv_staging_header_image_sizes()
{
    return array('small', 'medium', 'large', 'full');
}

function hectv_staging_topbar_cta_styles()
{
    return array('primary', 'secondary', 'tertiary');
}

function hectv_staging_sanitize_header_image_size($value)
{
    $value = sanitize_key($value);

    return in_array($value, hectv_staging_header_image_sizes(), true)
        ? $value
        : 'full';
}

function hectv_staging_sanitize_topbar_ctas($value)
{
    if (!is_array($value)) {
        return array();
    }

    $ctas = array();
    foreach ($value as $row) {
        if (!is_array($row)) {
            continue;
        }

        $label = isset($row['label']) ? sanitize_text_field($row['label']) : '';
        $url = isset($row['url']) ? esc_url_raw($row['url']) : '';
        $style = isset($row['style']) ? sanitize_key($row['style']) : '';

        if (
            $label === ''
            || $url === ''
            || !in_array($style, hectv_staging_topbar_cta_styles(), true)
        ) {
            continue;
        }

        $ctas[] = array(
            'label' => $label,
            'url' => $url,
            'style' => $style,
        );
    }

    return array_slice($ctas, 0, 5);
}

function hectv_staging_default_topbar_ctas()
{
    return array(
        array(
            'label' => 'Subscribe',
            'url' => '/newsletter',
            'style' => 'primary',
        ),
        array(
            'label' => 'Support',
            'url' => '/support',
            'style' => 'secondary',
        ),
    );
}

function hectv_staging_get_topbar_ctas()
{
    $ctas = get_option(
        HECTV_TOPBAR_CTAS_OPTION,
        array()
    );

    return is_array($ctas) ? array_values($ctas) : array();
}

function hectv_staging_default_site_content()
{
    return array(
        'forEducators' => array(
            'imageUrl' => HECTV_FOR_EDUCATORS_APPROVED_IMAGE,
            'destinationUrl' => '/category/education',
        ),
        'trendingPostIds' => array(),
        'spotlightTitle' => 'Spotlight STL',
        'footerLinks' => array(
            array('label' => 'Arts', 'url' => '/category/arts'),
            array('label' => 'Education', 'url' => '/category/education'),
            array('label' => 'Business', 'url' => '/category/business'),
        ),
        'mobileRailFirst' => true,
    );
}

function hectv_staging_sanitize_link($value, $fallback)
{
    $value = is_array($value) ? $value : array();
    $label = isset($value['label'])
        ? sanitize_text_field($value['label'])
        : $fallback['label'];
    $url = isset($value['url']) ? esc_url_raw($value['url']) : $fallback['url'];

    return array(
        'label' => $label !== '' ? $label : $fallback['label'],
        'url' => $url !== '' ? $url : $fallback['url'],
    );
}

function hectv_staging_sanitize_site_content($value)
{
    $defaults = hectv_staging_default_site_content();
    $value = is_array($value) ? $value : array();
    $for_educators = isset($value['forEducators'])
        && is_array($value['forEducators'])
        ? $value['forEducators']
        : array();

    $image_url = isset($for_educators['imageUrl'])
        ? esc_url_raw($for_educators['imageUrl'])
        : '';
    $destination_url = isset($for_educators['destinationUrl'])
        ? esc_url_raw($for_educators['destinationUrl'])
        : '';

    $trending_ids = array();
    $raw_trending = isset($value['trendingPostIds'])
        ? $value['trendingPostIds']
        : array();
    if (is_string($raw_trending)) {
        $raw_trending = preg_split('/[\s,]+/', $raw_trending);
    }
    foreach ((array) $raw_trending as $post_id) {
        $post_id = absint($post_id);
        if ($post_id > 0 && !in_array($post_id, $trending_ids, true)) {
            $trending_ids[] = $post_id;
        }
    }

    $footer_links = array();
    $raw_footer_links = isset($value['footerLinks'])
        ? (array) $value['footerLinks']
        : array();
    foreach ($defaults['footerLinks'] as $index => $fallback) {
        $footer_links[] = hectv_staging_sanitize_link(
            isset($raw_footer_links[$index])
                ? $raw_footer_links[$index]
                : array(),
            $fallback
        );
    }

    $spotlight_title = isset($value['spotlightTitle'])
        ? sanitize_text_field($value['spotlightTitle'])
        : '';

    return array(
        'forEducators' => array(
            'imageUrl' => $image_url !== ''
                ? $image_url
                : $defaults['forEducators']['imageUrl'],
            'destinationUrl' => $destination_url !== ''
                ? $destination_url
                : $defaults['forEducators']['destinationUrl'],
        ),
        'trendingPostIds' => array_slice($trending_ids, 0, 4),
        'spotlightTitle' => $spotlight_title !== ''
            ? $spotlight_title
            : $defaults['spotlightTitle'],
        'footerLinks' => $footer_links,
        'mobileRailFirst' => !empty($value['mobileRailFirst']),
    );
}

function hectv_staging_get_site_content()
{
    return hectv_staging_sanitize_site_content(
        get_option(HECTV_SITE_CONTENT_OPTION, hectv_staging_default_site_content())
    );
}

add_action('init', function () {
    register_setting(
        'hectv_staging_content_controls',
        HECTV_TOPBAR_CTAS_OPTION,
        array(
            'type' => 'array',
            'sanitize_callback' => 'hectv_staging_sanitize_topbar_ctas',
            'default' => array(),
            'show_in_rest' => false,
        )
    );

    register_setting(
        'hectv_staging_content_controls',
        HECTV_SITE_CONTENT_OPTION,
        array(
            'type' => 'array',
            'sanitize_callback' => 'hectv_staging_sanitize_site_content',
            'default' => hectv_staging_default_site_content(),
            'show_in_rest' => false,
        )
    );

    register_post_meta(
        'post',
        HECTV_HEADER_IMAGE_SIZE_META,
        array(
            'type' => 'string',
            'single' => true,
            'default' => 'full',
            'show_in_rest' => true,
            'sanitize_callback' => 'hectv_staging_sanitize_header_image_size',
            'auth_callback' => function ($allowed, $meta_key, $post_id) {
                return current_user_can('edit_post', $post_id);
            },
        )
    );
});

add_action('graphql_register_types', function () {
    if (!function_exists('register_graphql_object_type')) {
        return;
    }

    register_graphql_object_type(
        'HectvTopbarCta',
        array(
            'description' => 'A customizable action link displayed beside the social icons.',
            'fields' => array(
                'label' => array('type' => 'String'),
                'url' => array('type' => 'String'),
                'style' => array('type' => 'String'),
            ),
        )
    );

    register_graphql_object_type(
        'HectvContentLink',
        array(
            'fields' => array(
                'label' => array('type' => 'String'),
                'url' => array('type' => 'String'),
            ),
        )
    );

    register_graphql_object_type(
        'HectvForEducators',
        array(
            'fields' => array(
                'imageUrl' => array('type' => 'String'),
                'destinationUrl' => array('type' => 'String'),
            ),
        )
    );

    register_graphql_object_type(
        'HectvSiteContent',
        array(
            'description' => 'Staging-only client-editable HEC site presentation.',
            'fields' => array(
                'forEducators' => array('type' => 'HectvForEducators'),
                'trendingPostIds' => array('type' => array('list_of' => 'Int')),
                'spotlightTitle' => array('type' => 'String'),
                'footerLinks' => array(
                    'type' => array('list_of' => 'HectvContentLink'),
                ),
                'mobileRailFirst' => array('type' => 'Boolean'),
            ),
        )
    );

    register_graphql_field(
        'RootQuery',
        'topbarCtas',
        array(
            'type' => array('list_of' => 'HectvTopbarCta'),
            'resolve' => function () {
                // Prefer Appearance → Menus → Header Actions (may include external
                // PayPal Support). Option table is a fallback only when the menu
                // is empty / unassigned.
                if (function_exists('hectv_cms_get_header_action_items')) {
                    $menu_rows = hectv_cms_get_header_action_items();
                    if (is_array($menu_rows) && count($menu_rows) > 0) {
                        return $menu_rows;
                    }
                }
                $option_rows = hectv_staging_get_topbar_ctas();
                if (is_array($option_rows) && count($option_rows) > 0) {
                    return $option_rows;
                }
                return hectv_staging_default_topbar_ctas();
            },
        )
    );

    register_graphql_field(
        'RootQuery',
        'hectvSiteContent',
        array(
            'type' => 'HectvSiteContent',
            'resolve' => function () {
                return hectv_staging_get_site_content();
            },
        )
    );

    register_graphql_field(
        'Post',
        'headerImageSize',
        array(
            'type' => 'String',
            'resolve' => function ($post) {
                $post_id = isset($post->ID)
                    ? $post->ID
                    : (isset($post->databaseId) ? $post->databaseId : 0);
                $value = $post_id
                    ? get_post_meta($post_id, HECTV_HEADER_IMAGE_SIZE_META, true)
                    : '';

                return $value ?: 'full';
            },
        )
    );
});

add_action('admin_menu', function () {
    add_options_page(
        'HEC Header Actions',
        'HEC Header Actions',
        'manage_options',
        'hectv-header-actions',
        'hectv_staging_render_header_actions_page'
    );
});

function hectv_staging_render_header_actions_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (
        isset($_POST['hectv_header_actions_nonce'])
        && check_admin_referer(
            'hectv_header_actions_save',
            'hectv_header_actions_nonce'
        )
    ) {
        $rows = array();
        $labels = isset($_POST['hectv_topbar_ctas_label'])
            ? (array) $_POST['hectv_topbar_ctas_label']
            : array();

        foreach ($labels as $index => $label) {
            $rows[] = array(
                'label' => $label,
                'url' => isset($_POST['hectv_topbar_ctas_url'][$index])
                    ? $_POST['hectv_topbar_ctas_url'][$index]
                    : '',
                'style' => isset($_POST['hectv_topbar_ctas_style'][$index])
                    ? $_POST['hectv_topbar_ctas_style'][$index]
                    : '',
            );
        }

        update_option(
            HECTV_TOPBAR_CTAS_OPTION,
            hectv_staging_sanitize_topbar_ctas($rows)
        );

        update_option(
            HECTV_SITE_CONTENT_OPTION,
            hectv_staging_sanitize_site_content(
                isset($_POST['hectv_site_content'])
                    ? $_POST['hectv_site_content']
                    : array()
            )
        );
        echo '<div class="notice notice-success"><p>Header actions saved.</p></div>';
    }

    $ctas = hectv_staging_get_topbar_ctas();
    $site_content = hectv_staging_get_site_content();
    ?>
    <div class="wrap">
        <h1>HEC Header Actions</h1>
        <p>Add up to five linked buttons displayed beside the social icons.</p>
        <form method="post">
            <?php wp_nonce_field('hectv_header_actions_save', 'hectv_header_actions_nonce'); ?>
            <h2>For Educators</h2>
            <table class="form-table">
                <tr>
                    <th><label for="hectv_for_educators_image">Image URL</label></th>
                    <td>
                        <input
                            class="large-text"
                            id="hectv_for_educators_image"
                            name="hectv_site_content[forEducators][imageUrl]"
                            value="<?php echo esc_attr($site_content['forEducators']['imageUrl']); ?>"
                        >
                    </td>
                </tr>
                <tr>
                    <th><label for="hectv_for_educators_destination">Destination</label></th>
                    <td>
                        <input
                            class="large-text"
                            id="hectv_for_educators_destination"
                            name="hectv_site_content[forEducators][destinationUrl]"
                            value="<?php echo esc_attr($site_content['forEducators']['destinationUrl']); ?>"
                        >
                    </td>
                </tr>
            </table>

            <h2>Homepage sections</h2>
            <table class="form-table">
                <tr>
                    <th><label for="hectv_trending_ids">Trending Now post IDs</label></th>
                    <td>
                        <input
                            class="large-text"
                            id="hectv_trending_ids"
                            name="hectv_site_content[trendingPostIds]"
                            value="<?php echo esc_attr(implode(', ', $site_content['trendingPostIds'])); ?>"
                        >
                        <p class="description">Optional ordered list; the site displays at most four.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="hectv_spotlight_title">Spotlight heading</label></th>
                    <td>
                        <input
                            class="regular-text"
                            id="hectv_spotlight_title"
                            name="hectv_site_content[spotlightTitle]"
                            value="<?php echo esc_attr($site_content['spotlightTitle']); ?>"
                        >
                    </td>
                </tr>
                <tr>
                    <th>Mobile order</th>
                    <td>
                        <label>
                            <input
                                type="checkbox"
                                name="hectv_site_content[mobileRailFirst]"
                                value="1"
                                <?php checked($site_content['mobileRailFirst']); ?>
                            >
                            Show right-rail content before the remaining feed on mobile
                        </label>
                    </td>
                </tr>
            </table>

            <h2>Footer links</h2>
            <table class="widefat striped">
                <thead><tr><th>Label</th><th>Link</th></tr></thead>
                <tbody>
                    <?php foreach ($site_content['footerLinks'] as $index => $link) : ?>
                        <tr>
                            <td>
                                <input
                                    name="hectv_site_content[footerLinks][<?php echo esc_attr($index); ?>][label]"
                                    value="<?php echo esc_attr($link['label']); ?>"
                                >
                            </td>
                            <td>
                                <input
                                    class="large-text"
                                    name="hectv_site_content[footerLinks][<?php echo esc_attr($index); ?>][url]"
                                    value="<?php echo esc_attr($link['url']); ?>"
                                >
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Header actions</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Label</th>
                        <th>Link</th>
                        <th>Style</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($index = 0; $index < 5; $index++) :
                        $cta = isset($ctas[$index])
                            ? $ctas[$index]
                            : array('label' => '', 'url' => '', 'style' => '');
                        ?>
                        <tr>
                            <td>
                                <input
                                    type="text"
                                    name="hectv_topbar_ctas_label[]"
                                    value="<?php echo esc_attr($cta['label']); ?>"
                                >
                            </td>
                            <td>
                                <input
                                    class="regular-text"
                                    type="text"
                                    name="hectv_topbar_ctas_url[]"
                                    placeholder="/subscribe or https://example.org"
                                    value="<?php echo esc_attr($cta['url']); ?>"
                                >
                            </td>
                            <td>
                                <select name="hectv_topbar_ctas_style[]">
                                    <option value="">Select a style</option>
                                    <?php foreach (hectv_staging_topbar_cta_styles() as $style) : ?>
                                        <option
                                            value="<?php echo esc_attr($style); ?>"
                                            <?php selected($cta['style'], $style); ?>
                                        >
                                            <?php echo esc_html(ucfirst($style)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <?php submit_button('Save Header Actions'); ?>
        </form>
    </div>
    <?php
}

add_action('add_meta_boxes', function () {
    add_meta_box(
        'hectv_header_image_size',
        'Header image size',
        'hectv_staging_render_header_image_size_metabox',
        'post',
        'side'
    );
});

// The legacy staging install can render a blank block editor when its REST
// dependencies are unavailable. That blank canvas hides the page body (About
// Us / Contact Us content still lives in post_content, but editors cannot
// reach it — only ACF meta boxes remain). Production still uses the block
// editor successfully; staging forces the classic content editor for posts
// and pages so admin matches production's ability to edit body copy.
//
// Scope is intentionally limited to post + page. Custom post types that rely
// on the block editor keep the default behavior.
add_filter('use_block_editor_for_post_type', function ($use_block_editor, $post_type) {
    if (in_array($post_type, array('post', 'page'), true)) {
        return false;
    }
    return $use_block_editor;
}, 100, 2);

// Per-post check used by WP admin screens (post.php) — keep in lockstep with
// the post_type filter so neither path re-enables a blank block canvas.
add_filter('use_block_editor_for_post', function ($use_block_editor, $post) {
    $type = is_object($post) && isset($post->post_type)
        ? $post->post_type
        : '';
    if (in_array($type, array('post', 'page'), true)) {
        return false;
    }
    return $use_block_editor;
}, 100, 2);

function hectv_staging_render_header_image_size_metabox($post)
{
    wp_nonce_field(
        'hectv_header_image_size_save',
        'hectv_header_image_size_nonce'
    );
    $value = get_post_meta($post->ID, HECTV_HEADER_IMAGE_SIZE_META, true);
    $value = $value ?: 'full';
    ?>
    <p>
        <label for="hectv_header_image_size">Article header image width</label>
        <select
            id="hectv_header_image_size"
            name="hectv_header_image_size"
            style="width:100%"
        >
            <?php foreach (hectv_staging_header_image_sizes() as $size) : ?>
                <option
                    value="<?php echo esc_attr($size); ?>"
                    <?php selected($value, $size); ?>
                >
                    <?php echo esc_html(ucfirst($size)); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}

add_action('save_post', function ($post_id) {
    if (
        !isset($_POST['hectv_header_image_size_nonce'])
        || !wp_verify_nonce(
            $_POST['hectv_header_image_size_nonce'],
            'hectv_header_image_size_save'
        )
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['hectv_header_image_size'])) {
        update_post_meta(
            $post_id,
            HECTV_HEADER_IMAGE_SIZE_META,
            hectv_staging_sanitize_header_image_size(
                $_POST['hectv_header_image_size']
            )
        );
    }
});
