<?php function hectv_recent_blog( $display = 3 ){ ?>
<div class="module clearfix">

	<?php $recentBlogPostsQueryParameters = array( 'posts_per_page' => $display,
												   'orderby' => 'post_date',
												   'order' => 'DESC',
												   'post_type' => 'post' ); ?>

	<?php $recentBlogPosts = get_posts( $recentBlogPostsQueryParameters ); ?>

	<div class="recent-blog-posts inner">

		<h2>Recent Blog<br />Posts</h2>

		<?php foreach ( $recentBlogPosts as $post ) : setup_postdata( $post ); ?>

    		<article>
	    		<a href="<?php echo get_the_permalink( $post->ID ); ?>">
	        		<h3><?php echo $post->post_title; ?></h3>
	    		</a>
        		<div class="post-info">Posted On: <?php echo get_the_time( 'F j, Y', $post->ID ); ?> By <?php echo get_the_author_meta( "user_nicename", $post->post_author ); ?></div>
        		<?php echo hectv_get_excerpt_by_id( $post->ID, 20 ); ?>
    		</article>

		<?php endforeach; ?>

	</div>

	<div class="flex"></div>

</div> <!-- Recent Blog Posts -->
<?php } ?>