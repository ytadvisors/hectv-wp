<?php 

    function lb_get_preferred_episodes_first( $query_args ) {
        
        global $post;
        
        $preferred_recent_episode_meta = get_post_meta( $post->ID, 'preferred_recent_episodes', true);
        
        $preferred_recent_episode_ids = array();

        if( is_array( $preferred_recent_episode_meta ) ) {

            foreach( $preferred_recent_episode_meta as $preferred_recent_episode_id ) {

                array_push( $preferred_recent_episode_ids, $preferred_recent_episode_id['id'] );

            }

        }

        $preferred_recent_items = array();

        if( count($preferred_recent_episode_ids) > 0 ) {

            $preferred_recent_items = get_posts( 
                array(
                    'posts_per_page' => -1,
                    'post_type' => 'lb_playlist',
                    'orderby' => 'post__in',
                    'include' => $preferred_recent_episode_ids,
                    'post_status' => 'publish' 
                )
            );

        }
        
        
        //Get the normal posts, excluding the preferred
        if( is_array( $query_args['exclude'] ) ){
           
            $query_args['exclude'] = array_merge($query_args['exclude'], $preferred_recent_episode_ids);
        
        } else {
            
            $query_args['exclude'] = $preferred_recent_episode_ids;
            
        }

        $non_preferred_recent_items = get_posts( $query_args );
        
        $recent_items = array_merge( $preferred_recent_items, $non_preferred_recent_items);
        
        return $recent_items;
        
    }

?>