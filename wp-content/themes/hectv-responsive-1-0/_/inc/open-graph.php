<?php $permalink = ( get_permalink() ) ? get_permalink() : get_bloginfo('url'); ?>
<?php $excerpt   = ( get_the_excerpt() ) ? get_the_excerpt() : get_bloginfo('description'); ?>
<?php $title     = ( get_the_title() ) ? get_the_title() : get_bloginfo('name'); ?>

<meta property="og:site_name" content="<?php bloginfo('name'); ?>"/>
<meta property="og:title" content="<?php bloginfo('name'); ?>: <?php echo $title; ?>"/>
<meta property="og:url" content="<?php echo $permalink; ?>"/>
<meta property="og:type" content="website"/>
<meta property="og:description" content="<?php echo $excerpt; ?>"/>

<?php $post_thumbnail_id  = get_post_thumbnail_id(); ?>
<?php if( $post_thumbnail_id ){ ?>
	<?php $post_thumbnail_url = wp_get_attachment_url( $post_thumbnail_id, 'fullsize' ); ?>
<?php }else{ ?>
	<?php $post_thumbnail_url = wp_get_attachment_url( get_field('video_image'), 'fullsize' ); ?>
<?php } ?>
<?php if( $post_thumbnail_url ){ ?>
<meta property="og:image" content="<?php echo $post_thumbnail_url; ?>"/>
<?php } ?>