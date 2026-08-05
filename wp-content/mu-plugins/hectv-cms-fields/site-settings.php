<?php
/**
 * Site-wide settings (not post custom fields):
 *  - Trending / Spotlight headings and mobile rail placement
 *  - Trending max videos
 *  - Newsletter CAPTCHA toggle
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

function hectv_cms_mobile_display_default() {
	return 'menu-content';
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

/**
 * Return a non-empty, plain-text option or its editorial default.
 *
 * @param string $option_name Option key.
 * @param string $default     Display fallback.
 * @return string
 */
function hectv_cms_get_heading( $option_name, $default ) {
	$value = trim( (string) get_option( $option_name, $default ) );
	return $value !== '' ? $value : $default;
}

function hectv_cms_get_trending_title() {
	return hectv_cms_get_heading( HECTV_OPT_TRENDING_TITLE, 'Trending Now' );
}

function hectv_cms_get_spotlight_title() {
	return hectv_cms_get_heading( HECTV_OPT_SPOTLIGHT_TITLE, 'Spotlight STL' );
}

function hectv_cms_get_mobile_display() {
	$value = (string) get_option( HECTV_OPT_MOBILE_DISPLAY, hectv_cms_mobile_display_default() );
	return in_array( $value, array( 'content-menu', 'menu-content' ), true )
		? $value
		: hectv_cms_mobile_display_default();
}

/**
 * CAPTCHA is enabled unless the saved option is explicitly false-like.
 *
 * @return bool
 */
function hectv_cms_newsletter_captcha_enabled() {
	$value = get_option( HECTV_OPT_NEWSLETTER_CAPTCHA_ENABLED, '1' );
	if ( is_bool( $value ) ) {
		return $value;
	}
	return ! in_array( strtolower( trim( (string) $value ) ), array( '0', 'false', 'off', 'no' ), true );
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
			HECTV_OPT_TRENDING_TITLE,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'Trending Now',
			)
		);

		register_setting(
			'hectv_site_settings',
			HECTV_OPT_SPOTLIGHT_TITLE,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'Spotlight STL',
			)
		);

		register_setting(
			'hectv_site_settings',
			HECTV_OPT_MOBILE_DISPLAY,
			array(
				'type'              => 'string',
				'sanitize_callback' => static function ( $value ) {
					$value = (string) $value;
					return in_array( $value, array( 'content-menu', 'menu-content' ), true )
						? $value
						: hectv_cms_mobile_display_default();
				},
				'default'           => hectv_cms_mobile_display_default(),
			)
		);

		register_setting(
			'hectv_site_settings',
			HECTV_OPT_NEWSLETTER_CAPTCHA_ENABLED,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => static function ( $value ) {
					return in_array( $value, array( true, 1, '1', 'true', 'on' ), true );
				},
				'default'           => true,
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

	$max             = hectv_cms_get_trending_max_videos();
	$trending_title  = hectv_cms_get_trending_title();
	$spotlight_title = hectv_cms_get_spotlight_title();
	$mobile_display  = hectv_cms_get_mobile_display();
	$captcha_enabled = hectv_cms_newsletter_captcha_enabled();
	$edu             = hectv_cms_get_educators_settings();
	$logo_id         = $edu['logo_id'] ? (int) $edu['logo_id'] : 0;
	$preview         = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
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
					<th scope="row"><label for="hectv_trending_title">Trending heading</label></th>
					<td>
						<input
							name="<?php echo esc_attr( HECTV_OPT_TRENDING_TITLE ); ?>"
							id="hectv_trending_title"
							type="text"
							class="regular-text"
							value="<?php echo esc_attr( $trending_title ); ?>"
						/>
						<p class="description">Heading displayed above the Trending Now list.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hectv_spotlight_title">Spotlight heading</label></th>
					<td>
						<input
							name="<?php echo esc_attr( HECTV_OPT_SPOTLIGHT_TITLE ); ?>"
							id="hectv_spotlight_title"
							type="text"
							class="regular-text"
							value="<?php echo esc_attr( $spotlight_title ); ?>"
						/>
						<p class="description">Heading displayed above the Spotlight STL list.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hectv_mobile_display">Mobile display</label></th>
					<td>
						<select
							name="<?php echo esc_attr( HECTV_OPT_MOBILE_DISPLAY ); ?>"
							id="hectv_mobile_display"
						>
							<option value="content-menu" <?php selected( $mobile_display, 'content-menu' ); ?>>Content, then menu</option>
							<option value="menu-content" <?php selected( $mobile_display, 'menu-content' ); ?>>Menu, then content</option>
						</select>
						<p class="description">Controls whether the main page content or the right-hand menu appears first on mobile.</p>
					</td>
				</tr>
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

			<h2>Newsletter</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">CAPTCHA</th>
					<td>
						<input
							type="hidden"
							name="<?php echo esc_attr( HECTV_OPT_NEWSLETTER_CAPTCHA_ENABLED ); ?>"
							value="0"
						/>
						<label>
							<input
								type="checkbox"
								name="<?php echo esc_attr( HECTV_OPT_NEWSLETTER_CAPTCHA_ENABLED ); ?>"
								value="1"
								<?php checked( $captcha_enabled ); ?>
							/>
							Require CAPTCHA for newsletter signup
						</label>
						<p class="description">Enabled by default. Turn this off only for controlled testing; public signups will have less spam protection.</p>
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
					var image = document.createElement('img');
					image.src = url;
					image.alt = '';
					image.style.maxWidth = '240px';
					image.style.height = 'auto';
					preview.replaceChildren(image);
				});
				frame.open();
			});
			document.getElementById('hectv-educators-clear').addEventListener('click', function (e) {
				e.preventDefault();
				input.value = '0';
				var empty = document.createElement('em');
				empty.textContent = 'No image selected.';
				preview.replaceChildren(empty);
			});
		})();
		</script>
	</div>
	<?php
}
