<?php

	add_filter('query_vars', 'lb_queryvars_add' );

	function lb_queryvars_add( $queryvars ){

		$queryvars[] = "segmentquery";
		$queryvars[] = "seriesquery";
		$queryvars[] = "playlistquery";
		$queryvars[] = "parent_id";
		return $queryvars;

	}

	add_action('pre_get_posts', 'lb_queryvars_process' );

	function lb_queryvars_process( $wp_query ) {

		global $wp_query;

		if( isset( $wp_query->query_vars['segmentquery'] ) &&
			isset( $wp_query->query_vars['parent_id'] ) &&
			isset( $wp_query->query_vars['p'] ) ) {

		   set_query_var('post_parent', $wp_query->query_vars['parent_id'] );

		}


		if( isset( $wp_query->query_vars['playlistquery'] ) && isset( $wp_query->query_vars['p'] ) ) {

			//$segments = get_children( array( 'post_parent' => $wp_query->query_vars['p'], 'posts_per_page' => 1, 'post_status' => 'publish' ) );

			//print_r($segments);

	// 	   set_query_var('post_parent', $wp_query->query_vars['parent_id'] );

		}

	}

?>