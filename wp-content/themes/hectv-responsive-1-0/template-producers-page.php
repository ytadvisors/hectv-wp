<?php
/*
Template Name: Producers Page
*/
?>

<?php get_header(); ?>

<main class="page clearfix" id="producers">

	<div class="page-inner clearfix">

    	<div class="module-wide">

        	<div class="inner">
        		<h2>Producers</h2>
        		<p>This area is password protected. Please <a href="/contact/">contact us</a> if you're having problems logging in.</p>
				<p>Please sign in using your account credentials in the form below.</p>

	        	<div class="form-wrap">

	            	<form>

	            		<div class="field">
	        				<input type="name" name="user-name" placeholder="Username">
						</div>

						<div class="field">
	        				<input type="password" name="user-password" placeholder="Password">
						</div>

						<div class="check-wrap">
							<input type="checkbox" id="remember-me"><label for="remember-me">Remember Me</label>
						</div>

						<div class="btn-wrap"><button class="btn">Sign In</button></div>

					</form>

	        	</div>
        	</div>
    	</div> <!-- End Producer Module-->

	</div>


</main>

<?php get_footer(); ?>
