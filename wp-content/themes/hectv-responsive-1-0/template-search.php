<?php
/*
	Template Name: Search
*/
?>
<?php get_header(); ?>

	<script type="text/javascript">
		jQuery(document).ready(function(){

			var loadedItems = new Array();
			jQuery("ul#search-results").find("li.result").each(function(){

	        	loadedItems.push( jQuery(this).attr("rel") );

        	});

		});
	</script>

	<main class="page clearfix endless" id="search">

	<?php if (have_posts()): while (have_posts()) : the_post(); ?>

	<div class="page-inner clearfix">

    	<section id="search-stage" class="endless-media page-left" data-elm="li.result" data-tgt="ul#search-results" rel="1" data-itm="results">

	    	<div class="module-wide">

    			<div class="inner">
                    
                    
                    <?php 
                    
                        $user_query = urldecode( $_GET['query'] );  
                        $search_paged = 0;
                        $results_per_page = 10;
                    
                        if( isset( $_GET['spage'] ) && is_numeric( $_GET['spage'] ) ){
        				    $search_paged   = $_GET['spage'];
                        }
                    ?>
        
                    <h2>Search for “<?php echo $user_query; ?>”</h2>
                    <!-- test -->
					
                    <?php $total_found = lb_count_search_results($user_query); ?>
        			<span id="total"><?php echo ( $total_found ) ? $total_found : 'No'; ?> Episodes Found</span>
                    
        
                    <?php
                    
                        lb_search_output( $user_query, $results_per_page, $search_paged ); 
                    
                    ?>
                

                    <?php if( $total_found >= ($search_paged + 1)*$results_per_page ){ ?>
                        <?php $return_query          = array(); ?>
                        <?php $return_query['query'] = $user_query; ?>
                        <?php $return_query['spage'] = ( empty( $search_paged ) ) ? 1 : $search_paged; ?>
                        <?php $return_query['spage'] = $return_query['spage'] + 1; ?>
                        <a href="?<?php echo http_build_query( $return_query ); ?>" class="pager load-more" style="display:inline-block;">Load More</a>
                    <?php } ?>


    			</div>

    	</section>

    	<section class="page-right">

	    	<?php hectv_create_trending(3); ?>

			<?php hectv_create_partners_list(7); ?>

    	</section>

		<?php endwhile; ?>

		<section class="page-left">

    	</section>

    	<?php else: ?>

		<!-- article -->
		<article>
			<h2><?php _e( 'Sorry, nothing to display.', 'html5blank' ); ?></h2>
		</article>
		<!-- /article -->

		<?php endif; ?>

		</div>

	</main>

<?php get_footer(); ?>
