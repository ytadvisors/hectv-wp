<?php function hectv_create_ad_slat(){ ?>
    <?php 
                                      
        $image_url = get_field('ad_image', 'options')['url'];
        $ad_url = get_field('ad_url', 'options');
    
    ?>
    <?php if( !empty($image_url) ){ ?>
			        	
		<div class="module ad-unit">
			
			<?php if( !empty($ad_url) ) { ?>
				<a  target="_blank" href="<?php echo $ad_url; ?>">
			<?php } ?>
					<div class="img-wrap"><img src="<?php echo $image_url; ?>"></div>
			<?php if( !empty($ad_url) ) { ?>
				</a>
			<?php } ?>
						
		</div>
	
	<?php } ?>
	
<?php } ?>