<?php

	add_action( 'init', 'lb_create_education_topic_taxonomies', 0 );

	function lb_create_education_topic_taxonomies() {

		$educationTopicLabels = array(
			'name' => _x( 'Education Topics', 'taxonomy general name' ),
			'singular_name' => _x( 'Education Topics', 'taxonomy singular name' ),
			'search_items' =>  __( 'Search Education Topics' ),
			'all_items' => __( 'All Education Topics' ),
			'parent_item' => __( 'Parent Education Topic' ),
			'parent_item_colon' => __( 'Parent Education Topic:' ),
			'edit_item' => __( 'Edit Education Topic' ),
			'update_item' => __( 'Update Education Topic' ),
			'add_new_item' => __( 'Add New Education Topic' ),
			'new_item_name' => __( 'New Topic Education Topic' ),
		);

		register_taxonomy( 'education-topic', array( 'page' ), array(
			'hierarchical' => true,
			'labels' => $educationTopicLabels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'teachers/topic', 'with_front' => false ),
		));

	}

?>