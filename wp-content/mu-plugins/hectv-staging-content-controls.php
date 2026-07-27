<?php
/**
 * Plugin Name: HEC staging content controls
 * Description: Staging-only controls for article header-image sizing and header action links.
 */

if (getenv('HECTV_ENVIRONMENT') !== 'staging') {
    return;
}

define('HECTV_HEADER_IMAGE_SIZE_META', 'hectv_header_image_size');
define('HECTV_TOPBAR_CTAS_OPTION', 'hectv_topbar_ctas');

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

function hectv_staging_get_topbar_ctas()
{
    $ctas = get_option(HECTV_TOPBAR_CTAS_OPTION, array());

    return is_array($ctas) ? array_values($ctas) : array();
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

    register_graphql_field(
        'RootQuery',
        'topbarCtas',
        array(
            'type' => array('list_of' => 'HectvTopbarCta'),
            'resolve' => function () {
                return hectv_staging_get_topbar_ctas();
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
        echo '<div class="notice notice-success"><p>Header actions saved.</p></div>';
    }

    $ctas = hectv_staging_get_topbar_ctas();
    ?>
    <div class="wrap">
        <h1>HEC Header Actions</h1>
        <p>Add up to five linked buttons displayed beside the social icons.</p>
        <form method="post">
            <?php wp_nonce_field('hectv_header_actions_save', 'hectv_header_actions_nonce'); ?>
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
