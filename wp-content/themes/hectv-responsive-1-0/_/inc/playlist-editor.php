<?php
	
add_action( 'post_submitbox_misc_actions', function(){

    print '<div class="misc-pub-section curtime misc-pub-curtime" style="position:relative;left:1px;"><input type="checkbox" value="1" name="lb_purge_homepage_cache" id="lb_purge_homepage_cache"> <span title="This is not required when previewing a post"><label for="lb_purge_homepage_cache">Purge Homepage Cache</label></span></div>';

    print '<div class="misc-pub-section curtime misc-pub-curtime" style="position:relative;left:1px;"><input type="checkbox" value="1" name="lb_purge_page_cache" id="lb_purge_page_cache"> <span title="This is not required when previewing a post"><label for="lb_purge_page_cache">Purge Page Cache</label></span></div>';

} );

?>

<?php wp_enqueue_media(); ?>

<?php function lb_generate_segment_html( $id, $parent ){ ?>

		<?php if( !empty( $id ) ){ ?>
			<?php $segment    = get_post( $id ); ?>
			<?php $data       = get_post_custom( $id ); ?>
		<?php } ?>

		<?php $seriesID   = wp_get_post_parent_id( $parent ); ?>
		<?php $seriesData = get_post_custom( $seriesID ); ?>
		<?php $mediaPath  = $seriesData['media_path'][0]; ?>

		<li class="segment clearfix <?php echo $segment->post_status; ?>" id="post-<?php echo $segment->ID; ?>">

			<div class="cover">

				<div class="data">
					<h2><?php echo ( $segment->post_title ) ? $segment->post_title : "New Segment"; ?></h2>
					<h3><?php echo ( $segment->post_status ) ? $segment->post_status : "Draft"; ?></h3>
				</div>

				<?php if( !empty( $data['video_image'][0] ) ){ ?>

					<?php $image_url = wp_get_attachment_image_src( $data['video_image'][0], "full" ); ?>
					<?php if( $image_url ){ ?>

						<img class="thumbnail loaded" src="<?php echo $image_url[0]; ?>">

					<?php } ?>

				<?php }else{ ?>

						<img class="thumbnail" style="display:none;" src="#">

				<?php } ?>

			</div>

			<div class="form">

				<input type="hidden" name="segment_parent" value="<?php echo $parent; ?>">
				<input type="hidden" class="clear" name="segment_child[]" id="segment_id" value="<?php echo $segment->ID; ?>">

				<div class="field medium">

					<label for="post_title">Title:</label>
					<div class="input-wrap">

						<input type="text" class="clear" name="segment_title" id="post_title" value="<?php echo $segment->post_title; ?>">

					</div>

				</div>

				<div class="field limited large">

					<label for="post_content">Description:</label>
					<div class="input-wrap">
						<textarea id="post_content" class="clear" rows="3" name="segment_long_description"><?php echo $segment->post_content; ?></textarea>
						<div class="progress-bar">
							<span class="text"></span>
							<span class="background okay"></span>
						</div>
					</div>

				</div>

				<div class="field limited large" data-limit="160">

					<label for="meta_description">Meta Description:</label>
					<div class="input-wrap">
						<textarea id="meta_description" class="clear" rows="3" name="segment_meta_description"><?php echo $segment->post_excerpt; ?></textarea>
						<div class="progress-bar">
							<span class="text"></span>
							<span class="background okay"></span>
						</div>
					</div>

				</div>

				<div class="field">

					<label for="select-photo">Image:</label>
					<div class="input-wrap" style="border:none;">

						<button type="button" id="select-photo" class="button button-secondary button-large">Select Photo</button>
						<span id="current-photo" <?php echo ( $data['video_image'][0] ) ? 'style="display:inline-block"':''; ?>>View Photo <img class="preview" src="<?php echo $image_url[0]; ?>"></span>
						<input type="hidden" class="clear" name="segment_thumbnail_id" id="thumbnail_id" value="<?php echo $data['video_image'][0]; ?>">

					</div>

				</div>

				<div class="field dynamic media" id="variable_media_list">

					<label for="">File List:</label>
					<ul id="media-list">

						<?php $segment_media_files = get_post_meta( $segment->ID, 'segment_files', true ); ?>

						<?php if( count( $segment_media_files['location'] ) > 0 && is_array( $segment_media_files ) ){ ?>

							<?php foreach ( $segment_media_files['location'] as $index => $encoded_video ){ ?>

								<li class="media">
									<input type="text" class="clear" name="segment_files[location][]" value="<?php echo $encoded_video; ?>" data-path="<?php echo $mediaPath; ?>">
									<?php echo lb_build_media_select( $segment_media_files['bitrate'][$index], "segment_" ); ?>
									<a href="#" class="remove">Remove</a>
									<a href="#" class="add">Add</a>
								</li>

							<?php } ?>

						<?php }else{ ?>

								<li class="media">
									<input type="text" class="clear" name="segment_files[location][]" value="<?php echo $mediaPath; ?>" data-path="<?php echo $mediaPath; ?>">
									<?php echo lb_build_media_select( 0, "segment_" ); ?>
									<a href="#" class="remove">Remove</a>
									<a href="#" class="add">Add</a>
								</li>

						<?php } ?>

					</ul>

					<?php if( $data['smil_file'][0] ){ ?>
						<span id="smil">Smil: <span id="file"><?php echo $data['smil_file'][0]; ?></span></span>
					<?php }else{ ?>
						<span id="smil" style="display:none;">Smil: <span id="file"></span></span>
					<?php } ?>

				</div>
				
				<div class="field medium" style="margin-bottom:30px;">

					<label for="segment_youtube_id">YouTube ID:</label>
					<div class="input-wrap">

						<input type="text" name="segment_youtube_id" id="segment_youtube_id" value="<?php echo $data['youtube_id'][0]; ?>">

					</div>

				</div>
				
				
				<div class="field medium" style="margin-bottom:30px;">

					<label for="segment_vimeo_id">Vimeo ID:</label>
					<div class="input-wrap">

						<input type="text" name="segment_vimeo_id" id="segment_vimeo_id" value="<?php echo $data['vimeo_id'][0]; ?>">

					</div>

				</div>
				

				<div id="segment-embed" style="margin-left:10px;">

					<input type="checkbox" name="segment_embed" id="segment_embed" value="1" <?php echo ( $data['video_embed'][0] ) ? 'checked="checked"' : ''; ?>>

					<label for="segment_embed">Allow this segment to be embedded.</label>

				</div>

				<div class="field micro">

					<label for="segment_duration">Duration:</label>
					<div class="input-wrap">

						<input type="text" name="segment_duration" id="segment_duration" class="duration" value="<?php echo hectv_formatDuration( $data['duration'][0] ); ?>">

					</div>

					<span class="note">HH:MM:SS</span>

				</div>

				<div id="taxonomies" class="clearfix">

					<div class="field" id="keyword-field">

						<label for="keywords">Keywords:</label>
						<div class="keyword-wrap">
							<div class="input-wrap" style="float:left;margin-right:5px;">

								<input type="text" name="keywords" id="keywords" value="">

							</div>
							<button type="button" id="add-keyword" class="button button-secondary button-large">Add</button>
						</div>
						<ul id="keyword-list">

							<?php $keywords = get_post_meta( $segment->ID, 'keywords', true ); ?>

							<?php if( count( $keywords ) > 0 && is_array( $keywords ) ){ ?>

								<?php foreach ( $keywords as $index => $keyword ){ ?>

								<li><?php echo $keyword; ?><input type="hidden" name="keyword[]" value="<?php echo $keyword; ?>"></li>

								<?php } ?>

							<?php } ?>

						</ul>

					</div>

					<div class="field" id="category-field">

						<label for="categories">Categories:</label>

						<?php $segment->categories = wp_get_post_terms( $segment->ID, "topic", array( "fields" => "ids" ) ); ?>

						<?php $segmentCats = get_terms( array( "topic" ) ); ?>

						<select name="segment_categories[]" id="segment_categories" multiple="multiple">
							<?php foreach( $segmentCats as $cat ){ ?>

								<?php $selected = ( in_array( $cat->term_id, $segment->categories ) ) ? 'selected="selected"':''; ?>
								<option value="<?php echo $cat->term_id; ?>" <?php echo $selected; ?>><?php echo $cat->name; ?></option>

							<?php } ?>

						</select>

					</div>

				</div>

				<div class="field publish-settings">

					<?php $segment_status[$segment->post_status] = 'selected="selected"'; ?>

					<label for="categories">Status:</label>

						<select name="segment_status" id="segment_status">

							<option value="publish" <?php echo $segment_status['publish']; ?>>Publish</option>
							<option value="inherit" <?php echo $segment_status['inherit']; ?>>Inherit</option>
							<option value="private" <?php echo $segment_status['private']; ?>>Private</option>
							<option value="pending" <?php echo $segment_status['pending']; ?>>Ready for Review</option>
							<option value="draft" <?php echo $segment_status['draft']; ?>>Draft</option>

						</select>

				</div>

				<div class="field segment-actions">

					<button type="submit" id="save-segment" class="button button-primary button-large"><?php echo ( $status ) ? "Update":"Save"; ?></button>
					<button type="button" id="cancel-segment" class="button button-secondary button-large">Cancel</button>
					<span id="save-prompt" class="segment-notice">This segment must be individually saved for changes to commit.</span>

				</div>

			</div>

		</li>

	<?php } ?>

	<?php
	function lb_build_media_select( $current = 0, $prefix = "video_" ){

		$bitrates = explode( ", ", BITRATES );

		$html = '<select name="' . $prefix . 'files[bitrate][]" id="encoded_bitrate">';

		foreach ($bitrates as $index => $value){

			if( $current == $value ){

				$html .= '<option value="' . $value . '" selected>' . $value . '</option>';

			}else{

				$html .= '<option value="' . $value . '">' . $value . '</option>';

			}

		}

		$html .= '</select>';

		return $html;

	}

	function lb_build_segment_select( $current = 0, $segment_data ){

		$html = '<select name="objective[segment][]">';

		if( is_array( $segment_data ) ){

			foreach ( $segment_data["title"] as $index => $value ){

				if( $current == $value ){

					$html .= '<option value="'.$value.'" selected>'.$value.'</option>';

				}else{

					$html .= '<option value="'.$value.'">'.$value.'</option>';

				}

			}

		}

		$html .= '</select>';

		return $html;

	}

?>



<!-- Start Playlist Editor -->


<link rel="stylesheet" href="<?php bloginfo('template_directory'); ?>/_/inc/editor-assets/style.css">
<script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/_/inc/editor-assets/jquery-ui-min.js"></script>
<script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/_/inc/editor-assets/functions.js?v=<?php echo time(); ?>"></script>

<?php global $post; ?>
<?php $data = get_post_meta( $post->ID ); ?>

<div id="playlist-editor">

	<section>

	<div class="field limited large">

		<label for="long_description">Playlist Description:</label>
		<div class="input-wrap">
			<textarea id="long_description" rows="5" name="post_content"><?php echo $post->post_content; ?></textarea>
			<div class="progress-bar">
				<span class="text"></span>
				<span class="background okay"></span>
			</div>
		</div>

	</div>

	<div class="admin-notice">
		<p>This is the descriptive copy that appears on the page that search engines will "match" with your description of the content. It should contain the same keywords used in the meta description but elaborate on the description of the episode. This is the place where you are convincing the visitor to watch the video but also assuring the search engines so do not use "teaser" copy rather, use recognizable keyword copy.</p>
	</div>

	<div class="field limited large" data-limit="160">

		<label for="meta_description">Meta Description:</label>
		<div class="input-wrap">
			<textarea id="meta_description" rows="3" name="post_excerpt"><?php echo $post->post_excerpt; ?></textarea>
			<div class="progress-bar">
				<span class="text"></span>
				<span class="background okay"></span>
			</div>
		</div>

	</div>

	<div class="admin-notice">
		<p>These descriptions typically appear in search results below the title and should be considered a 160-character description of the episode that is front-loaded with keywords. It should be unique to the episode and be considered "sell" copy that encourages the person searching to click on the link.</p>
	</div>

	<div class="field">

		<label for="meta_description">Playlist Thumbnail:</label>
		<input type="hidden" name="video_image" id="video_image" value="<?php echo $data['video_image'][0]; ?>">

		<?php $image_url = wp_get_attachment_image_src( $data['video_image'][0], "full" ); ?>

		<div id="thumb-image" style="max-width:<?php echo ( isset( $image_url[1] ) ) ? $image_url[1] . "px" : 'auto'; ?>;" class="<?php echo ( isset( $image_url[1] ) ) ? "active" : ""; ?>">
			<div class="inner clearfix">
				<img src="<?php echo $image_url[0]; ?>">
				<button id="add-video-thumb" class="button button-secondary button-large">Select an Image</button>
			</div>
		</div>
		<a href="#" id="remove-video-thumb">Remove Image</a>


	</div>

	<div class="admin-notice">

		<p>Your image should be: <b>point, point, point</b></p>

	</div>

	</section>

	<section>


		<div id="playlist-tabs">

			<nav>

				<ul>
					<li class="tab"><a href="#page-options">Page Options</a></li>
					<li class="tab"><a href="#meta">Meta</a></li>
					<li class="tab"><a href="#segments">Segments</a></li>
					<li class="tab"><a href="#education">Education</a></li>
					<li class="tab"><a href="#stats">Stats</a></li>
				</ul>

			</nav>

			<div id="playlist-tabs-area">

				<div id="page-options" class="tab-section">

					<fieldset>

						<div class="field checkbox">

							<input type="checkbox" name="series_title_disable" id="series_title_disable" value="1" <?php echo ( $data['series_title_disable'][0] ) ? 'checked="checked"':''; ?>>
							<label for="series_title_disable">Hide the series title when displaying this media assets (schedules, video detail, etc)</label>

						</div>

						<div class="field checkbox">

							<input type="checkbox" name="video_embed" id="video_embed" value="1" <?php echo ( $data['video_embed'][0] ) ? 'checked="checked"':''; ?>>
							<label for="video_embed">Allow this video to be embedded on other websites <a href="#" id="toggle_embed_state" style="margin-left:10px;">View Embed Code</a></label>

							<div id="toggle_embed">

								<textarea id="embed-html"><iframe style="margin-top:15px;" src="http://www.hectv.org/embed/<?php echo $post->ID; ?>/" width="630" height="378" frameborder="0"></iframe></textarea>

							</div>

						</div>

						<div class="field medium">

							<label for="overlay_url">Overlay URL:</label>
							<div class="input-wrap">
								<input type="text" name="overlay_url" id="overlay_url" value="http://www.hectv.org/embed/<?php echo $post->ID; ?>/">
							</div>

						</div>

						<div class="admin-notice">
							<p>Use this url (seen in the text box above) in blog posts, education pages, etc. and the site will automatically pop the video in an overlay.</p>
						</div>

					</fieldset>

					<fieldset>

						<div class="field dynamic large">

							<?php $link_data = unserialize( $data['links'][0] ); ?>

							<label for="related-links">Related Links:</label>
							<ul id="related-links">

								<?php if( count( $link_data["title"] ) > 0 && is_array( $link_data ) ){ ?>

									<?php foreach ( $link_data["title"] as $index => $value ){ ?>

										<li class="link">
											<label class="title-label" style="margin-right:5px;">Link Copy: </label>
											<input type="text" class="text link-title" name="link[title][]" value="<?php echo trim( $link_data["title"][$index] ); ?>">
											<label class="url-label">Address: </label>
											<input type="text" class="text link-url " name="link[url][]" value="<?php echo trim( $link_data["url"][$index] ); ?>">
											<a href="#" class="remove">Remove</a>
											<a href="#" class="add">Add</a>
										</li>

									<?php } ?>

								<?php }else{ ?>

									<li class="link">
										<label class="title-label" style="margin-right:5px;">Link Copy: </label>
										<input type="text" class="text link-title" name="link[title][]" value="">
										<label class="url-label">Address: </label>
										<input type="text" class="text link-url " name="link[url][]" value="">
										<a href="#" class="remove">Remove</a>
										<a href="#" class="add">Add</a>
									</li>

								<?php } ?>



							</ul>

						</div>

					</fieldset>

				</div>

				<div id="meta" class="tab-section">

					<fieldset>

						<div class="field medium">

							<label for="broadcast_location">Broadcast File Location:</label>
							<div class="input-wrap">

								<input type="text" name="broadcast_location" id="broadcast_location" class="required" value="<?php echo $data['broadcast_location'][0]; ?>">

							</div>

						</div>

						<div class="field medium">

							<label for="internal_id">Internal ID:</label>
							<div class="input-wrap">

								<input type="text" name="internal_id" id="internal_id" class="required" value="<?php echo $data['internal_id'][0]; ?>">

							</div>

						</div>

						<div class="field micro">

							<label for="legacy_media_id">Legacy ID:</label>
							<div class="input-wrap">

								<input type="text" name="legacy_media_id" id="legacy_media_id" value="<?php echo $data['legacy_media_id'][0]; ?>">

							</div>

						</div>
						
						<div class="field micro">

							<label for="ga_adjust">Google Analytics Adjust:</label>
							<div class="input-wrap">

								<input type="text" name="ga_adjust" id="ga_adjust" value="<?php echo $data['ga_adjust'][0]; ?>">

							</div>

						</div>
						
						<div class="field micro">

							<label for="ga_adjust">Old Site Page View:</label>
							<div class="input-wrap">

								<input type="text" name="views" id="views" value="<?php echo $data['views'][0]; ?>" disabled="disabled">

							</div>

						</div>

						<div class="field micro">

							<label for="duration">Duration:</label>
							<div class="input-wrap">

								<input type="text" name="duration" id="duration" class="duration" value="<?php echo hectv_formatDuration( $data['duration'][0] ); ?>">

							</div>

							<span class="note">HH:MM:SS</span>


						</div>

					</fieldset>

				</div>

				<div id="segments" class="tab-section">

					<fieldset>

						<div id="playlist-type">

							<?php $playlist_type = ( empty( $data['playlist_type'][0] ) ) ? 1 : $data['playlist_type'][0]; ?>

							<div class="option">

								<input type="radio" name="playlist_type" id="playlist_type_2" class="playlist-type" value="2" <?php echo ( $playlist_type == 2 ) ? 'checked="checked"':''; ?>>
								<label for="playlist_type_2">New Segment Format</label>

							</div>

							<div class="option">

								<input type="radio" name="playlist_type" id="playlist_type_1" class="playlist-type"  value="1" <?php echo ( $playlist_type == 1 ) ? 'checked="checked"':''; ?>>
								<label for="playlist_type_1">Legacy Segment Format</label>

							</div>

						</div>

					</fieldset>

					<fieldset class="legacy <?php echo ( $playlist_type == 1 ) ? 'active':''; ?>">

						<?php $encoded_location_sbr = get_post_meta( $post->ID, 'sbr_file', true ); ?>
						<?php $encoded_location_vbr = get_post_meta( $post->ID, 'video_files', true ); ?>

						<div class="field">

							<?php $media_type = ( empty( $data['media_type'][0] ) ) ? 1 : $data['media_type'][0]; ?>

							<label for="media_type">Video Media:</label>
							<select name="media_type" id="media_type" style="position:relative;left:-4px;">
								<option value="sbr" <?php echo ( $data['media_type'][0] == "sbr" ) ? 'selected':''; ?>>Single Bit Rate</option>
								<option value="vbr" <?php echo ( $data['media_type'][0] == "vbr" || empty( $data['media_type'][0] ) ) ? 'selected':''; ?>>Variable Bit Rate</option>
							</select>

						</div>

						<div class="field dynamic media <?php echo ( $data['media_type'][0] == "vbr" ) ? 'active':''; ?>" id="variable_media_list">

							<label for="">File List:</label>
							<ul id="media-list">
								
								<?php if( is_array( $encoded_location_vbr ) ) { ?>
								
									<?php if( count( $encoded_location_vbr['location'] ) > 0 && is_array( $encoded_location_vbr ) ){ ?>
	
										<?php foreach ( $encoded_location_vbr['location'] as $index => $encoded_video ){ ?>
	
											<li class="media">
												<input type="text" name="video_files[location][]" value="<?php echo $encoded_video; ?>">
												<?php echo lb_build_media_select( $encoded_location_vbr['bitrate'][$index] ); ?>
												<a href="#" class="remove">Remove</a>
												<a href="#" class="add">Add</a>
											</li>
	
										<?php } ?>
	
									<?php }else{ ?>
	
											<li class="media">
												<input type="text" name="video_files[location][]" value="<?php echo $mediaPath; ?>">
												<?php echo lb_build_media_select(); ?>
												<a href="#" class="remove">Remove</a>
												<a href="#" class="add">Add</a>
											</li>
	
									<?php } ?>
								
								<?php }else{ ?>
								
									<li class="media">
										<input type="text" name="video_files[location][]" value="<?php echo $mediaPath; ?>">
										<?php echo lb_build_media_select(); ?>
										<a href="#" class="remove">Remove</a>
										<a href="#" class="add">Add</a>
									</li>
								
								<?php } ?>

							</ul>
							
							<div class="field medium" style="margin-bottom:30px;padding-left:0px;">

								<label for="youtube_id">YouTube ID:</label>
								<div class="input-wrap">
			
									<input type="text" name="youtube_id" id="youtube_id" value="<?php echo $data['youtube_id'][0]; ?>">
			
								</div>
			
							</div>
							
							<div class="field medium" style="margin-bottom:30px;padding-left:0px;">

								<label for="vimeo_id">Vimeo ID:</label>
								<div class="input-wrap">
			
									<input type="text" name="vimeo_id" id="vimeo_id" value="<?php echo $data['vimeo_id'][0]; ?>">
			
								</div>
			
							</div>

						</div>

						<div class="field medium media <?php echo ( $data['media_type'][0] == "sbr" ) ? 'active':''; ?>" id="single_media_list">

							<label for="sbr_file">Video File:</label>
							<div class="input-wrap">

								<input type="text" name="sbr_file" id="sbr_file" value="<?php echo $encoded_location_sbr; ?>">

							</div>

						</div>


						<div class="field dynamic">

							<?php $segment_data = unserialize( $data["segments"][0] ); ?>

							<label for="">Segments:</label>
							<ul id="segment-list">

								<?php if( count( $segment_data["title"] ) > 0 && is_array( $segment_data ) ){ ?>

									<?php foreach ( $segment_data["title"] as $index => $encoded_video ){ ?>

										<li class="segment limited" data-limit="300">

											<div class="top">
												<label>Title:</label>
												<input type="text" name="segment[title][]" class="title" value="<?php echo htmlspecialchars( $segment_data["title"][$index] ); ?>">
												<label>In-Point:</label>
												<input type="text" name="segment[in-point][]" class="duration" value="<?php echo hectv_formatDuration( $segment_data["inpoint"][$index] ); ?>">
												<a href="#" class="remove">Remove</a>
												<a href="#" class="add">Add</a>
											</div>

											<div class="input-wrap">
												<textarea id="long_description" rows="2" name="segment[description][]"><?php echo $segment_data["description"][$index]; ?></textarea>
												<div class="progress-bar">
													<span class="text"></span>
													<span class="background okay"></span>
												</div>
											</div>

										</li>

									<?php } ?>

								<?php }else{ ?>

									<li class="segment limited" data-limit="300">

										<div class="top">
											<label>Title:</label>
											<input type="text" name="segment[title][]" class="title" value="">
											<label>In-Point:</label>
											<input type="text" name="segment[in-point][]" class="duration" value="<?php echo hectv_formatDuration( 0 ); ?>">
											<a href="#" class="remove">Remove</a>
											<a href="#" class="add">Add</a>
										</div>

										<div class="input-wrap">
											<textarea id="long_description" rows="2" name="segment[description][]"></textarea>
											<div class="progress-bar">
												<span class="text"></span>
												<span class="background okay"></span>
											</div>
										</div>

									</li>

								<?php } ?>

							</ul>

						</div>


					</fieldset>

					<?php $segments = unserialize( $data['segment_child'][0] ); ?>

					<fieldset class="new <?php echo ( $playlist_type == 2 ) ? 'active':''; ?>">

						<ul id="new-segment-list">

							<?php if( is_array( $segments ) ){ ?>

								<?php $loaded = array(); ?>
								<?php foreach( $segments as $segment ){ ?>

									<?php lb_generate_segment_html( $segment, $post->ID ); ?>
									<?php $loaded[] = $segment; ?>

								<?php } ?>

							<?php }else{ ?>

								<?php lb_generate_segment_html( "", $post->ID ); ?>

							<?php } ?>

							<?php $lost_segments = get_posts( array( "post_parent" => $post->ID, "exclude" => $loaded, "post_type" => "lb_video", "post_status" => "any" ) ); ?>

							<?php if( is_array( $lost_segments ) ){ ?>

								<?php foreach( $lost_segments as $lost_segment ){ ?>

									<?php lb_generate_segment_html( $lost_segment, $post->ID ); ?>

								<?php } ?>

							<?php } ?>


							<li id="create-segment">Create New Segment</li>

						</ul>


					</fieldset>

				</div>

				<div id="education" class="tab-section">

					<div class="field dynamic">

						<?php $objectives = unserialize( $data["objectives"][0] ); ?>

						<label for="">Education Objectives:</label>
						<ul id="education-objectives">

							<?php if( count( $objectives["title"] ) > 0 && is_array( $objectives ) ){ ?>

								<?php foreach ( $objectives["title"] as $index => $encoded_video ){ ?>

									<li>
										<input type="text" name="objective[title][]" value="<?php echo $objectives["title"][$index]; ?>">
										<?php echo lb_build_segment_select( $objectives["segment"][$index], $segment_data ); ?>
										<a href="#" class="remove">Remove</a>
										<a href="#" class="add">Add</a>
									</li>

								<?php } ?>

							<?php }else{ ?>

									<li>
										<input type="text" name="video_files[title][]" value="">
										<?php echo lb_build_segment_select( "", $segment_data ); ?>
										<a href="#" class="remove">Remove</a>
										<a href="#" class="add">Add</a>
									</li>

							<?php } ?>

						</ul>

					</div>

					<div class="field">

						<label for="">Education Page:</label>
						<?php $pages = wp_dropdown_pages( array( "child_of" => $post->post_parent, "name" => "education_page_id", "echo" => false, "selected" => $data["education_page_id"][0] ) ); ?>
						<?php if( $pages ){ ?>
							<?php echo $pages; ?>
						<?php }else{ ?>
							<?php echo "No education pages have been parented to " . get_the_title( $post->post_parent ) . ". Create a page and parent it to this series."; ?>
						<?php } ?>

					</div>

				</div>

				<div id="stats" class="tab-section">

					<fieldset>

						<p>Forthcoming...</p>

					</fieldset>

				</div>

			</div>

		</div>


	</section>

</div>


<!-- End Playlist Editor -->