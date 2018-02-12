<?php function hectv_create_media_slat_by_post($post){ ?>

	<?php $thumbID = get_post_custom_values( "video_image", $post->ID ); ?>
	<?php $thumb   = wp_get_attachment_image_src( $thumbID[0], 'media-medium' ); ?>

    
	<?php $html ='<div class="module recent-clip clearfix">'; ?>

		<?php $html .='<div class="inner">'; ?>

			<?php $html .='<div class="media-type">'; ?>

	    		<?php $html .='<span class="recent">' . $media_type . '</span>'; ?>
	    		<?php $html .='<span class="divider">|</span>'; ?>
	    		<?php $html .='<span class="show"><a href="' . get_permalink($post->post_parent) . '">' . get_the_title($post->post_parent) .'</a></span>'; ?>

			<?php $html .='</div>'; ?>

				<?php $html .='<a href="' . get_permalink( $post->ID ) . '">'; ?>
		    		<?php $html .='<h3>' . get_the_title( $post->ID ) . '</h3>'; ?>
				<?php $html .='</a>'; ?>
	    		<?php $html .='<p>' . hectv_get_excerpt_by_id( $post->ID ) . '</p>'; ?>

		<?php $html .='</div>'; ?>

		<?php $html .='<div class="img-wrap flex">'; ?>
		<?php $html .='<a href="' . get_permalink( $post->ID ) . '">'; ?>
				<?php $html .='<img class="play" src="' . get_bloginfo('template_directory') . '/_/graphics/play-button.png">'; ?>
		    	<?php $html .='<img class="thumb" src="' . $thumb[0] . '">'; ?>
			<?php $html .='</a>'; ?>
		<?php $html .='</div>'; ?>

	<?php $html .='</div> <!-- End Module -->';?>

	<?php return $html; ?>

<?php } ?>