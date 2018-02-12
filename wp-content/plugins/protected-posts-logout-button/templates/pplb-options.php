<?php
	
	$new_options = get_option('pplb_options');
	if ( isset( $_GET['message'] ) && $_GET['message'] == 1 ){
        	?>
        	<div class="message updated">
                	<p>Settings updated</p>
        	</div>
        	<?php
	}
	
	if ( is_array($new_options ) ) {
		
		$pplb_debug = isset( $new_options['pplb_debug'] ) ? $new_options['pplb_debug'] : 0;
		$pplb_alert = isset( $new_options['pplb_alert'] ) ? $new_options['pplb_alert'] : 0;
		$pplb_message = isset( $new_options['pplb_message'] ) ? $new_options['pplb_message'] : '';
		$pplb_button_class = isset( $new_options['pplb_button_class'] ) ? $new_options['pplb_button_class'] : '';
		$pplb_button_text = ( isset( $new_options['pplb_button_text'] ) && !empty($new_options['pplb_button_text']) ) ? $new_options['pplb_button_text'] : 'logout';
		
	}
	?>
		<div class="wrap">
			<h2>Protected Posts Logout Settings</h2>
			<p>Thanks for using this plugin.  If you encounter any errors please report them to <a href="https://wordpress.org/support/plugin/protected-posts-logout-button" target="_blank">the support forum.</a></p>
			<form action="" method="post">
					<input type="hidden" name="pplb_action" value="update" />
			<table class='form-table'>
				<tbody>
					<tr>
						<th><label>Alert user log out was successful?</label></th><td><input type="checkbox" name="pplb_alert" value="yes" <?php checked($pplb_alert, 'yes'); ?> /></td>
					</tr>
					<tr>
						<th><label>Turn on console debugging in Javascript? ( for developers )</label></th><td><input type="checkbox" name="pplb_debug" value="1" <?php checked($pplb_debug, 1); ?> /></td>
					</tr>
					<tr>
						<th><label>Logout Message:</label></th><td> <input type="text" name="pplb_message" value="<?php echo stripslashes($pplb_message); ?>" /></td>
					</tr>
					<tr>
						<th><label>Button CSS class:</label></th><td> <input type="text" name="pplb_button_class" value="<?php echo stripslashes($pplb_button_class); ?>" /></td>
					</tr>
					
					<tr>
						<th><label>Button Text:</label></th><td> <input type="text" name="pplb_button_text" value="<?php echo stripslashes($pplb_button_text); ?>" /></td>
					</tr>
					
					<tr>
						<th><label>Automatically add button to protected pages:</label></th><td>
						<select name="pplb_button_filter">
							<option value='yes' <?php selected( get_option('pplb_button_filter'), 'yes'); ?>>Yes</option>
							<option value='no' <?php selected( get_option('pplb_button_filter'), 'no'); ?>>No</option>
						</select>
						</td>
					</tr>
					<tr>
						<?php $expire = get_option('pplb_pass_expires'); ?>
						<th>
							<label>Change the default cookie expire time for WordPress Protected Posts:</label>
							<br />
							<span class="description">In seconds, leave blank for default</span>
						</th>
						<td>
							<input type="number" min="1" name="pplb_pass_expires" value="<?php echo $expire; ?>"> seconds = <span id="expire-human"></span>
							<script type="text/javascript">
								jQuery( document ).ready(function($){
									$( 'input[name="pplb_pass_expires"]' ).change( function(){
										if( $(this).val().length == 0 ){
											$('span#expire-human').text( '10 days (default value)' )
										}
										else if ( $(this).val() == 0 ) {
											$(this).val( '' );
										}
										else{
											var word = 'minutes';
											var v = $( this ).val();
											if( v > ( 24 * 60 * 60 ) ){
												word = 'days';
											}
											else if( v > ( 60 * 60 ) ){
												word = 'hours';
											}
											
											switch( word ){
												case 'minutes':
													conversion = v / 60;
													break;
												case 'hours':
													conversion = v / 60 / 60;
													break;
												case 'days':
													conversion = v / 60 / 60 / 24;
													break;
												default:
													conversion = v;
													break;
											}
											
											var humanReadable = conversion + ' ' + word;
										
											$('span#expire-human').text( humanReadable );
										}
									} );
									$( 'input[name="pplb_pass_expires"]' ).trigger( 'change' );
								});
							</script>
						</td>
					</tr>
				</tbody>
			</table>
			<br />
			<p><input type="submit" value="Update" class="button-primary" /></p>
			</form>
			<h2>Usage</h2>
			<p>This plugin is meant to add a logout button to a <b>single</b> protected post or page automatically.</p>
			<p>It does so by attaching a button to the beginning of the content using a <b>filter</b> hooked to <code>the_content</code> when it is outputting that post's content. This means it inherently will not work for archives, where Wordpress is actually running <code>the_content</code> for other posts.</p>
			<p>Also, if other plugins, or theme code, are manipulating the protected posts content using a <b>filter</b> as well, they may remove the button inadvertently.</p>
			<p>To solve this, you can now place the button via a shortcode or <code>php</code> function:</p>
			<p>Shortcode:</p>
			<style type='text/css'>
				.pplb-pre{ display:block; white-space: normal; padding:20px; width:300px; background:#eee; border:1px solid #ccc; }
			</style>
			<pre class='pplb-pre'>[logout_btn]</pre>
			<p>PHP:</p>
			<pre class='pplb-pre'>&lt;?php echo pplb_logout_button(); ?&gt;</pre>
		</div><!-- .wrap pplb -->
		