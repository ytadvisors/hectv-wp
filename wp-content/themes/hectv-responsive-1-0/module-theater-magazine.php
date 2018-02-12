<?php function hectv_create_theater_module(){ 
    
    $theater_args = array(

            'post_type'=> array('magazine'),
            'posts_per_page'=> 1,
            'tax_query' => array(
                    array(
                        'taxonomy' => 'type',
                        'field'    => 'slug',
                        'terms'    => array( 'theater-guide' ),
                    ),
                ),
            'post_status' => 'publish',
            );

        global $post;
        $theater_backup_post = $post;
        $theater_loop = new WP_Query( $theater_args );

        if($theater_loop->have_posts()): 

            while( $theater_loop->have_posts()): 
    
                $theater_loop->the_post();
                $theater_magazine_id = $theater_loop->post->ID;
                $theater_magazine_link = get_the_permalink( $theater_magazine_id );
                $theater_magazine_image = get_field('cover_image'); 
                $theater_magazine_image_src = wp_get_attachment_image_src( $theater_magazine_image, 'full' )[0];
                $theater_magazine_title = get_field('module_title'); 
                $theater_magazine_title = empty($theater_magazine_title)?'Most Recent Issue':$theater_magazine_title;
    
    ?>
    
    <div class="module dark clearfix" id="magazine">

        <div class="inner clearfix">

            <div class="media-type">

                <span class="show"><a href="<?php echo site_url('/magazine/type/theater-guide/'); ?>">HEC-TV Theater Guide</a></span>

            </div>

            <div class="content-row clearfix touched">						
    
            <?php if($theater_magazine_image): ?>
                
                <div class="left">
                    <a href="<?php echo $theater_magazine_link; ?>">
                        <img width="144" height="228" src="<?php echo $theater_magazine_image_src; ?>" class="attachment-full" alt="web theater guide cover" />
                    </a>
                </div>

            <?php endif; ?>

                <div class="right">
                    <h3><a href="<?php echo $theater_magazine_link; ?>"><?php echo $theater_magazine_title; ?></a></h3>
                    <p>St. Louis' first comprehensive theater guide with a helpful map charting venues and theater companies, as well as information on the St. Louis theater scene, and much more! </p>
                </div>


            </div>

        </div>

    </div>

<?php 
    
    endwhile;

    endif;
    
}

?>