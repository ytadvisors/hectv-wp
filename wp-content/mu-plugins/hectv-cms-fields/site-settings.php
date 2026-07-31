<?php
/**
 * Site-wide settings (not post custom fields):
 *  - Trending max videos
 *  - For Educators logo image + destination URL
 *
 * Uses the Settings API so it works without ACF Pro options pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defaults.
 */
function hectv_cms_trending_max_videos_default() {
	return 5;
}

function hectv_cms_get_trending_max_videos() {
	$raw = get_option( HECTV_OPT_TRENDING_MAX_VIDEOS, hectv_cms_trending_max_videos_default() );
	$n   = (int) $raw;
	if ( $n < 1 ) {
		$n = hectv_cms_trending_max_videos_default();
	}
	if ( $n > 50 ) {
		$n = 50;
	}
	return $n;
}

function hectv_cms_get_educators_settings() {
	$logo_id = (int) get_option( HECTV_OPT_EDUCATORS_LOGO_ID, 0 );
	$url     = (string) get_option( HECTV_OPT_EDUCATORS_URL, '/spotlight' );
	$label   = (string) get_option( HECTV_OPT_EDUCATORS_LABEL, 'For Educators' );

	if ( $url === '' ) {
		$url = '/spotlight';
	}
	if ( $label === '' ) {
		$label = 'For Educators';
	}

	return array(
		'logo_id' => $logo_id > 0 ? $logo_id : null,
		'url'     => $url,
		'label'   => $label,
	);
}

add_action(
	'admin_menu',
	static function () {
		add_options_page(
			'HEC Site Settings',
			'HEC Site Settings',
			'manage_options',
			'hectv-site-settings',
			'hectv_cms_render_site_settings_page'
		);
	}
);

add_action(
	'admin_init',
	static function () {
		register_setting(
			'hectv_site_settings',
			HECTV_OPT_TRENDING_MAX_VIDEOS,
			array(
				'type'              => 'integer',
				'sanitize_callback' => static function ( $value ) {
					$n = (int) $value;
					if ( $n < 1 ) {
						$n = hectv_cms_trending_max_videos_default();
					}
					if ( $n > 50 ) {
						$n = 50;
					}
					return $n;
				},
				'default'           => hectv_cms_trending_max_videos_default(),
			)
		);

		register_setting(
			'hectv_site_settings',
			HECTV_OPT_EDUCATORS_LOGO_ID,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		register_setting(
			'hectv_site_settings',
			HECTV_OPT_EDUCATORS_URL,
			array(
				'type'              => 'string',
				'sanitize_callback' => static function ( $value ) {
					$value = trim( (string) $value );
					// Allow absolute http(s) URLs or site-relative paths.
					if ( $value === '' ) {
						return '/spotlight';
					}
					if ( isset( $value[0] ) && $value[0] === '/' ) {
						// Relative path — keep as-is after light sanitize.
						return '/' . ltrim( preg_replace( '#\s+#', '', $value ), '/' );
					}
					$abs = esc_url_raw( $value, array( 'http', 'https' ) );
					return $abs ? $abs : '/spotlight';
				},
				'default'           => '/spotlight',
			)
		);

		register_setting(
			'hectv_site_settings',
			HECTV_OPT_EDUCATORS_LABEL,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'For Educators',
			)
		);
	}
);

add_action(
	'admin_enqueue_scripts',
	static function ( $hook ) {
		if ( $hook !== 'settings_page_hectv-site-settings' ) {
			return;
		}
		wp_enqueue_media();
	}
);

/**
 * Admin UI: Settings → HEC Site Settings
 */
function hectv_cms_render_site_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$max     = hectv_cms_get_trending_max_videos();
	$edu     = hectv_cms_get_educators_settings();
	$logo_id = $edu['logo_id'] ? (int) $edu['logo_id'] : 0;
	$preview = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
	?>
	<div class="wrap">
		<h1>HEC Site Settings</h1>
		<p>
			Site-wide options for the headless frontend (not post-level custom fields).
			Field definitions and GraphQL live in
			<code>wp-content/mu-plugins/hectv-cms-fields/</code>.
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'hectv_site_settings' ); ?>

			<h2>Trending Now</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="hectv_trending_max_videos">Max videos to show</label></th>
					<td>
						<input
							name="<?php echo esc_attr( HECTV_OPT_TRENDING_MAX_VIDEOS ); ?>"
							id="hectv_trending_max_videos"
							type="number"
							min="1"
							max="50"
							value="<?php echo esc_attr( (string) $max ); ?>"
							class="small-text"
						/>
						<p class="description">
							Maximum number of posts with <strong>Post Details → Trending</strong> checked
							that the Trending Now rail should return (default 5).
						</p>
					</td>
				</tr>
			</table>

			<h2>For Educators logo</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Logo image</th>
					<td>
						<input
							type="hidden"
							name="<?php echo esc_attr( HECTV_OPT_EDUCATORS_LOGO_ID ); ?>"
							id="hectv_educators_logo_id"
							value="<?php echo esc_attr( (string) $logo_id ); ?>"
						/>
						<div id="hectv-educators-preview" style="margin-bottom:8px;">
							<?php if ( $preview ) : ?>
								<img src="<?php echo esc_url( $preview ); ?>" alt="" style="max-width:240px;height:auto;" />
							<?php else : ?>
								<em>No image selected.</em>
							<?php endif; ?>
						</div>
						<button type="button" class="button" id="hectv-educators-select">Select image</button>
						<button type="button" class="button" id="hectv-educators-clear">Clear</button>
						<p class="description">Media library image used for the For Educators rail card.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hectv_educators_url">Link / source URL</label></th>
					<td>
						<input
							name="<?php echo esc_attr( HECTV_OPT_EDUCATORS_URL ); ?>"
							id="hectv_educators_url"
							type="text"
							class="regular-text"
							value="<?php echo esc_attr( $edu['url'] ); ?>"
							placeholder="/spotlight or https://…"
						/>
						<p class="description">Destination when the For Educators card is clicked.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hectv_educators_label">Label</label></th>
					<td>
						<input
							name="<?php echo esc_attr( HECTV_OPT_EDUCATORS_LABEL ); ?>"
							id="hectv_educators_label"
							type="text"
							class="regular-text"
							value="<?php echo esc_attr( $edu['label'] ); ?>"
						/>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Save site settings' ); ?>
		</form>

		<script>
		(function () {
			var frame;
			var input = document.getElementById('hectv_educators_logo_id');
			var preview = document.getElementById('hectv-educators-preview');
			document.getElementById('hectv-educators-select').addEventListener('click', function (e) {
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = wp.media({ title: 'For Educators logo', button: { text: 'Use image' }, multiple: false });
				frame.on('select', function () {
					var att = frame.state().get('selection').first().toJSON();
					input.value = att.id;
					var url = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
					preview.innerHTML = '<img src="' + url + '" alt="" style="max-width:240px;height:auto;" />';
				});
				frame.open();
			});
			document.getElementById('hectv-educators-clear').addEventListener('click', function (e) {
				e.preventDefault();
				input.value = '0';
				preview.innerHTML = '<em>No image selected.</em>';
			});
		})();
		</script>
	</div>
	<?php
}
