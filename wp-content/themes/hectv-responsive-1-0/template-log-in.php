<?php
/*
Template Name: Login Template
*/
?>

<?php get_header(); ?>


<main class="page clearfix" id="log-in">

	<?php if (have_posts()): while (have_posts()) : the_post(); ?>
	
	<div class="page-inner clearfix">
		
		<div class="form-wrap">
			<h3>Already a Member?</h3>
			
			<?php if( $_GET['logged-in'] == 'false' ){ ?>
				<span class="error">You must be logged in to access profile page.</span>
			<?php } ?>
			
			<?php if( $_GET['log-in'] == 'failed' ){ ?>
				<span class="error">Please enter a valid username / password.</span>
			<?php } ?>
			
			<form id="login-in-form" method="post" action="/">
						
				<input type="hidden" name="action" value="user-login">		
				
				<div class="field">
					<input type="name" name="username" placeholder="Username or Email Address" class="required outline">
				</div>
				<div class="field">
					<input type="password" name="password" placeholder="Password" class="required outline">
				</div>
				
				<?php $redirect = '/user-log-in'; ?>
				
				<a class="lost-password" href="<?php echo wp_lostpassword_url( $redirect ); ?>">Lost Password?</a>
				
				<div class="btn-wrap">
					<button class="btn" type="submit">Login</button>
				</div>
				
				
			</form>
		</div>
		
		<div class="form-wrap">
			<h3>Become a Member!</h3>
			<form id="sign-up-form" method="post" action="/">
						
				<input type="hidden" name="action" value="sign-up">		
				
				<div class="btn-wrap">
					<a class="btn" href="/user-sign-up">Sign Up</a>
				</div>
			</form>
		</div>

		
	</div>
	
	
	<?php endwhile; ?>
	<?php endif; ?>


<?php get_footer(); ?>
