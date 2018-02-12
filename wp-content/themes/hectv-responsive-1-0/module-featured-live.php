<?php 

//Relies on the fields in the homepage template
function hectv_create_featured_live( $class = array() ){  

    // check if the repeater field has rows of data
    if( have_rows('hectv_live_module', 17893) ):
        // loop through the rows of data
                                                
    ?>

    <div class="trending module clearfix <?php echo implode( $class ); ?>">

        <h2>Hec-TV Live</h2>

        <ul class="video-list clearfix">
            
    <?php 
                                                                            
        while ( have_rows('hectv_live_module', 17893) ) : the_row();
    
            $live_url = get_sub_field('hectv_live_module_url');
            $thumb_url = get_sub_field('hectv_live_module_img');
            $live_title = get_sub_field('hectv_live_module_title');
    

    ?>
            <li>
                <article class="trending-article clearfix">
                    <div class="left">
                        <div class="img-wrap">
                            <a href="<?php echo $live_url; ?>">
                                <img src="<?php echo $thumb_url; ?>">
                            </a>
                        </div>
                    </div>
                    <div class="right clearfix">
                        <div class="trending-inner clearfix">
                            <a href="<?php echo $live_url; ?>">
                                <h3><?php echo $live_title; ?></h3>
                            </a>
                        </div>
                    </div>
                </article>
            </li>

            <span><hr></span>
    
    <?php

        endwhile;
    ?>
            
        </ul>

    </div> <!-- End Featured Live Module -->

<?php

    endif;
} 

?>