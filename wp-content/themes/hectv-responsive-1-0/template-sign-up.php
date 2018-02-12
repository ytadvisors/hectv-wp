<?php
/*
Template Name: Sign Up Template
*/
?>

<?php get_header(); ?>


<main class="page clearfix" id="sign-up">

	<?php if (have_posts()): while (have_posts()) : the_post(); ?>
	
	<div class="page-inner clearfix">
		
		<div class="copy">
			<h3>Sign up to become a member</h3>
			<p>Copy</p>
			
			<?php if( isset($_GET['errors']) ){ ?>
			
			<p class="errors"><?php echo $_GET['errors']; ?></p>
			
			<?php } ?>
			
		</div>
		
		<div class="form-wrap">
			
			<form id="sign-up-form" method="post" action="/">
						
				<input type="hidden" name="action" value="user-sign-up">
				
				<div class="row clearfix">
					<div class="field">
						<label for="first-name">First Name</label>
						<input type="name" name="first-name" id="first-name" placeholder="First Name" value="" class="required outline">
					</div>
					
					<div class="field">
						<label for="last-name">Last Name</label>
						<input type="name" name="last-name" id="last-name" placeholder="Last Name" value="" class="required outline">
					</div>
				</div>
				
				<div class="row clearfix">
					<div class="field username clearfix">
						<label for="username">Username</label><span class="response"></span>
						<input type="name" name="username" id="username" placeholder="Username" value="" class="required outline">
					</div>
					
					<div class="field">
						<label for="username">Email</label>
						<input type="name" name="email" id="email" placeholder="Email" value="" class="required outline">
					</div>
				</div>
				
				<div class="row">
					<div class="field">
						<label for="password">Password</label>
						<input type="password" name="password" placeholder="Password" value="" class="required outline">
					</div>
				</div>
				
				<div class="row">
					<div class="field">
						<label for="confirm-password">Re-enter Password</label>
						<input type="password" name="confirm-password" placeholder="Re-enter Password" value="" class="required outline">
					</div>
				</div>
				
				
				<div class="btn-wrap">
					<button class="btn" type="submit">Submit</button>
				</div>
			</form>
		</div>
	</div>
	
	
	<?php endwhile; ?>
	<?php endif; ?>


<?php get_footer(); ?>
