<?php function hectv_right_flex(){ ?>
	
	<?php if( have_rows('promos', 16917) ): ?>
	
	<?php while ( have_rows('promos', 16917) ) : the_row(); ?>
	
	<div class="module dark clearfix" id="magazine">
	
	    <div class="inner clearfix">
	
	        <div class="media-type">
	            <span class="show"><a target="_blank" href="<?php echo get_sub_field('link'); ?>"><?php echo get_sub_field('headline'); ?></a></span>
	        </div>
	
	        <div class="content-row clearfix touched">						
	
	            <div class="left">
	                <a target="_blank" href="<?php echo get_sub_field('link'); ?>">
	                    <img width="144" height="228" src="<?php echo get_sub_field('image'); ?>" class="attachment-full"/>
	                </a>
	            </div>
	
	            <div class="right">
	                <h3><a target="_blank" href="<?php echo get_sub_field('link'); ?>"><?php echo get_sub_field('headline'); ?></a></h3>
	                <p><?php echo get_sub_field('description'); ?></p>
	            </div>
	
	        </div>
	
	    </div>
	
	</div>
	
	<?php endwhile; ?>
	
	<?php endif; ?>

<?php } ?>