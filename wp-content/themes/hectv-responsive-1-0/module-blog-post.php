<?php function hectv_blog_post_html($post){ ?>

	<?php $html = '<div class="blog-post clearfix"> <!--Start Blog Post-->'; ?>

		<?php $html .= '<div class="post-info">'; ?>

			<?php $html .= '<span class="date">' . get_the_time( 'F j, Y', $post ) . '</span>'; ?>
			<?php $html .= '<span class="divider">|</span>'; ?>
			<?php $html .= '<span class="author">Posted By <a href="' . get_author_posts_url( get_the_author_meta( 'ID' ) ) . '">' . get_the_author_meta( 'user_nicename', $post->post_author ) . ' </a></span>'; ?>

		<?php $html .= '</div>'; ?>

		<?php $html .= '<div class="left">'; ?>

			<?php $html .= '<div class="img-wrap clearfix">'; ?>
				<?php $html .= '<a href="' . get_the_permalink( $post ) . '">'; ?>
				<?php if( has_post_thumbnail( $post->ID ) ){ ?>

					<?php $thumbnail = get_the_post_thumbnail( $post->ID, 'event-thumb' ); ?>

	                <?php $html .= $thumbnail; ?>

	            <?php }else{ ?>


	            	<?php if ( preg_match( '/<img[^>]+>/is', $post->post_content, $images ) ) { ?>

		            	<?php $html .= preg_replace( '/(width|height)="\d*"\s/', "", $images[0] ); ?>

					<?php }else{ ?>

						<?php $html .= '<span class="no-image">No Image</span>'; ?>

					<?php } ?>

	            <?php } ?>
				<?php $html .= '</a>'; ?>
			<?php $html .= '</div>'; ?>

		<?php $html .= '</div>'; ?>

		<?php $html .= '<div class="right">'; ?>

			<?php $html .= '<div class="post-info-mobile">'; ?>

				<?php $html .= '<span class="date">' . get_the_time( 'F j, Y', $post ) . '</span>'; ?>
				<?php $html .= '<span class="divider">|</span>'; ?>
				<?php $html .= '<span class="author">Posted By ' . get_author_posts_url( get_the_author_meta( 'ID' ) ) . '</span>'; ?>

			<?php $html .= '</div>'; ?>

			<?php $html .= '<h3><a href="' . get_the_permalink($post) . '">' . get_the_title($post) . '</a></h3>'; ?>

			<?php $html .= hectv_get_excerpt_by_id( $post->ID ); ?>

		<?php $html .= '</div>'; ?>

	<?php $html .= '</div> <!-- End Blog Post -->'; ?>

	<?php return $html; ?>

<?php } ?>