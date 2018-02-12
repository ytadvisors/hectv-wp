<!doctype html>
<!-- via github let's build -->
<html <?php language_attributes(); ?> class="no-js">
	<head>
		<meta charset="<?php bloginfo('charset'); ?>">
		<title><?php wp_title(''); ?><?php if(wp_title('', false)) { echo ' :'; } ?> <?php bloginfo('name'); ?></title>

		<link href="//www.google-analytics.com" rel="dns-prefetch">
        <link href="<?php echo get_template_directory_uri(); ?>/img/icons/favicon.ico" rel="shortcut icon">
        <link href="<?php echo get_template_directory_uri(); ?>/img/icons/touch.png" rel="apple-touch-icon-precomposed">
        <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Roboto:900,500,400italic,100,300,500italic,100italic,400" type="text/css">

		<meta name="google-site-verification" content="E4GPTRfMQAVWAFZtUL3dbq1c97TkYC-v-3HCd0Z7no4" />

		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="<?php bloginfo('description'); ?>">
		
		<?php if( is_tax() ){ ?>
		
		<meta name="robots" content="noindex, nofollow">
		
		<?php } ?>
		
		
		<?php require_once('_/inc/open-graph.php'); ?>

		<?php wp_head(); ?>
		<script type="text/javascript">
        conditionizr.config({
            assets: '<?php echo get_template_directory_uri(); ?>',
            tests: {}
        });
        </script>

		<script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/_/js/player/jwplayer.js"></script>
		<script type="text/javascript">jwplayer.key="MxVMVlCE58XzB/wuMbVnduQ6Bkov/H9JQnx9mw==";</script>
		
		<!-- 	Vimeo	 -->
		<script src="//f.vimeocdn.com/js/froogaloop2.min.js"></script>

	</head>
	<body <?php body_class(); ?> rel="1">

		<div id="fb-root"></div>
		<script>
		(function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0];
		  if (d.getElementById(id)) return;
		  js = d.createElement(s); js.id = id;
		  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&appId=123460507732411&version=v2.3";
		  fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
		</script>

		<!-- wrapper -->

		<div id="page-wrap">

			<input type="checkbox" id="nav-trigger" class="nav-trigger">

			<div id="menu-icon">

				<label id="mobile-menu" for="nav-trigger">
					<span class="menu fa fa-bars"></span>
				</label>

			</div>

			<nav id="mobile-navigation">
				<div class="inner">
					<?php wp_nav_menu( array(
											'theme_location' => 'mobile-menu',
											'menu_class' => 'clearfix',
											'depth' => 1,
											'menu_id' => 'mobile' ) ); ?>

					<?php $social_items = wp_get_nav_menu_items( 'social' ); ?>

					<div class="social">
					<?php if( is_array( $social_items ) ){ ?>
						<?php foreach( $social_items as $social_item) { ?>
							<?php $social[$social_item->post_name] = $social_item->url; ?>
						<?php } ?>
						<a href="<?php echo $social['facebook']; ?>"><i class="fa fa-facebook"></i></a>
						<a href="<?php echo $social['twitter']; ?>"><i class="fa fa-twitter"></i></a>
						<a href="<?php echo $social['youtube']; ?>"><i class="fa fa-youtube-square"></i></a>
						<a href="<?php echo $social['instagram']; ?>"><i class="fa fa-instagram"></i></a>
					<?php } ?>
					</div>

					<div class="apple-badge-wrap">
						<img src="<?php bloginfo('template_directory'); ?>/_/graphics/apple-app-store-badge.svg">
						<img src="<?php bloginfo('template_directory'); ?>/_/graphics/apple-itunes-badge.svg">
					</div>

				</div> <!-- End Inner -->

			</nav>

			<div id="site">
				<nav id="nav-mobile-top" class="no-select" role="navigation">
					<div class="inner clearfix no-select">

						<h1 class="logo-mobile">
							<div class="img-wrap">
								<a href="<?php echo esc_url( home_url() ); ?>">
									<img src="<?php bloginfo('template_directory'); ?>/_/graphics/hec-logo-head-mobile.png">
								</a>
							</div>
						</h1>

						<span class="search-mobile fa fa-search <?php echo ( empty( $_GET['query'] ) ) ? 'active':''; ?>"></span>

						<div id="mobile-search" class="<?php echo ( !empty( $_GET['query'] ) ) ? 'active':''; ?>">
							<div id="mobile-search-form">
								<form method="GET" class="searchform" action="/search/">
									<input type="text" name="query" id="search-field" value="<?php echo $_GET['query']; ?>" maxlength="120">
									<input type="submit" value=" ">
								</form>
								<button class="close-mobile <?php echo ( !empty( $_GET['query'] ) ) ? 'active':''; ?>" title="Close"><img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-close.png"></button>
							</div>
						</div>
					</div>
				</nav> <!-- End Nav Mobile -->

				<!-- header -->
	            <header style="background-image: url('<?php bloginfo('template_directory'); ?>/_/graphics/ui-header.jpg');">
					<div class="full-wrap">
						<div class="inner clearfix">
							<nav class="nav <?php echo ( !empty( $_GET['query'] ) ) ? 'active':''; ?>" role="navigation">
								<ul id="primary" class="clearfix">
								<?php wp_nav_menu( array( 'theme_location' => 'header-menu', 'menu_class' => 'clearfix', 'menu_id' => 'primary', 'items_wrap' => '%3$s', ) ); ?>
								<li><span class="search fa fa-search <?php echo ( !empty( $_GET['query'] ) ) ? 'active':''; ?>"></span></li>
								
								<?php if( !is_user_logged_in() ){ ?>
								
								<li><a href="/user-log-in">Login</a></li>
								
								<?php } ?>
								
								<?php if( is_user_logged_in() ){ ?>
								
								<?php $current_user = wp_get_current_user(); ?>
								
								<li class="logged-in"><a href="/user-profile"><?php echo $current_user->user_login; ?></a><a href="<?php echo wp_logout_url( '/' ); ?>">Logout</a></li>
								
								<?php } ?>
								
								</ul>

								<div id="search-form" class="<?php echo ( !empty( $_GET['query'] ) ) ? 'active':''; ?>">
									<span class="search fa fa-search"></span>
									<form method="GET" class="searchform <?php echo ( !empty( $_GET['query'] ) ) ? 'active':''; ?>" action="/search/">
										<input type="text" name="query" id="search-field" value="<?php echo $_GET['query']; ?>" maxlength="120">
										<input type="submit" value="">
									</form>
									<button class="close <?php echo ( !empty( $_GET['query'] ) ) ? 'active':''; ?>" title="Close" style="background-image: url('<?php bloginfo('template_directory'); ?>/_/graphics/ui-close.png');"></button>
								</div>
							</nav>

							<!-- search -->

							<!-- /search -->

							<div class="header-wrap clearfix">
								<h1>
									<div class="img-wrap">
										<a href="<?php echo esc_url( home_url() ); ?>">
											<img src="<?php bloginfo('template_directory'); ?>/_/graphics/hec-logo-head.png">
										</a>
									</div>
								</h1>

								<div class="right">
									<h2>St. Louis' Home of Education,<br /> Arts, and Culture</h2>
									<div class="social">
										<?php $social_items = wp_get_nav_menu_items( 'social' ); ?>
										<?php if( is_array( $social_items ) ){ ?>
											<?php foreach( $social_items as $social_item) { ?>
												<?php $social[$social_item->post_name] = $social_item->url; ?>
											<?php } ?>
											<a href="<?php echo $social['facebook']; ?>"><i class="fa fa-facebook"></i></a>
											<a href="<?php echo $social['twitter']; ?>"><i class="fa fa-twitter"></i></a>
											<a href="<?php echo $social['youtube']; ?>"><i class="fa fa-youtube-square"></i></a>
											<a href="<?php echo $social['instagram']; ?>"><i class="fa fa-instagram"></i></a>
										<?php } ?>
									</div>
								</div>
							</div> <!-- End Header Wrap -->
						</div> <!-- End Inner -->
					</div> <!-- End Full Wrap -->


				</header>
	            <!-- /header -->