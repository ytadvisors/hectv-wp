<?php
/*
Template Name: User Settings Template
*/
?>

<?php get_header(); ?>

<main class="page clearfix" id="user-settings">

	<?php if (have_posts()): while (have_posts()) : the_post(); ?>
	
	<div class="page-inner clearfix">
		
		<div class="copy clearfix">
			<a class="back" href="/user-profile"><i class="fa fa-arrow-left clearfix"></i>Back to Profile</a>
			<h2>User Settings</h2>
			<p>Edit your details using the form below.</p>
			
			<?php if( $_GET['updated'] == 'true' ){ ?>
				<span class="success">Profile Updated</span>
			<?php } ?>
		</div>
		
		<div class="form-wrap">
			
			<form id="user-settings-form" method="post" action="/<?php echo $post->post_name; ?>/">
						
				<input type="hidden" name="action" value="update-user-settings">
				
				<div class="row clearfix clearfix">
					<div class="field">
						<label for="first-name">First Name</label>
						<input type="name" name="first_name" id="first-name" placeholder="First Name" value="<?php the_author_meta( 'first_name', $user_ID );?>" class="required outline">
					</div>
					
					<div class="field">
						<label for="last-name">Last Name</label>
						<input type="name" name="last_name" id="last-name" placeholder="Last Name" value="<?php the_author_meta( 'last_name', $user_ID );?>" class="required outline">
					</div>
				</div>
				
				<div class="row clearfix clearfix">
					<div class="field">
						<label for="username">Username</label>
						<input type="name" name="username" id="username" placeholder="Username" class="outline" value="<?php the_author_meta( 'user_login', $user_ID );?>"disabled="disabled">
					</div>
					
					<div class="field">
						<label for="username">Email</label>
						<input type="name" name="email" id="email" placeholder="Email" value="<?php the_author_meta( 'email', $user_ID );?>" class="required outline">
					</div>
				</div>
				
				<div class="row clearfix">
					
					<div class="field">
						<label for="address-1">Street Address 1</label>
						<input type="name" name="address_line_1" id="address-line-1" placeholder="Street Address 1" value="<?php the_author_meta( 'address_line_1', $user_ID );?>" class="required outline">
					</div>
					<div class="field">
						<label for="address-1">Street Address 2</label>
						<input type="name" name="address_line_2" id="address-line-2" placeholder="Street Address 2" value="<?php the_author_meta( 'address_line_2', $user_ID );?>"class="required outline">
					</div>
					
				</div>
				
				<div class="row clearfix">
					<div class="field">
						<label for="city">City</label>
						<input type="name" name="city" id="city" placeholder="City" value="<?php the_author_meta( 'city', $user_ID );?>" class="outline">
					</div>
					
					<?php $state_names_with_codes = Array('AL' => 'Alabama','AK' => 'Alaska','AZ' => 'Arizona','AR' => 'Arkansas','CA' => 'California','CO' => 'Colorado','CT' => 'Connecticut','DE' => 'Delaware','DC' => 'District Of Columbia','FL' => 'Florida','GA' => 'Georgia','HI' => 'Hawaii','ID' => 'Idaho','IL' => 'Illinois','IN' => 'Indiana','IA' => 'Iowa','KS' => 'Kansas','KY' => 'Kentucky','LA' => 'Louisiana','ME' => 'Maine','MD' => 'Maryland','MA' => 'Massachusetts','MI' => 'Michigan','MN' => 'Minnesota','MS' => 'Mississippi','MO' => 'Missouri','MT' => 'Montana','NE' => 'Nebraska','NV' => 'Nevada','NH' => 'New Hampshire','NJ' => 'New Jersey','NM' => 'New Mexico','NY' => 'New York','NC' => 'North Carolina','ND' => 'North Dakota','OH' => 'Ohio','OK' => 'Oklahoma','OR' => 'Oregon','PW' => 'Palau','PA' => 'Pennsylvania','PR' => 'Puerto Rico','RI' => 'Rhode Island','SC' => 'South Carolina','SD' => 'South Dakota','TN' => 'Tennessee','TX' => 'Texas','UT' => 'Utah','VT' => 'Vermont','VA' => 'Virginia','WA' => 'Washington','WV' => 'West Virginia','WI' => 'Wisconsin','WY' => 'Wyoming'); ?>
					
					<div class="field state">
						<select name="state">
							<?php $current_state = get_the_author_meta( 'state', $user_ID ); ?>
							
							<?php foreach( $state_names_with_codes as $index => $state ){ ?>
							
								<?php if( $current_state == $index ){ ?>
								
									<option value="<?php echo $index; ?>" selected><?php echo $state; ?></option>
								
								<?php }else{ ?>
								
									<option value="<?php echo $index; ?>"><?php echo $state; ?></option>
								
								<?php } ?>
							<?php } ?>
						</select>				
										
					</div>
					
					<div class="field zip-code">
						<label for="zip_code">Zip Code</label>
						<input type="number" name="zip_code" placeholder="Zip Code" value="<?php the_author_meta( 'zip_code', $user_ID );?>" class="outline">
						
					</div>
					
					
				</div>
				
				<div class="row clearfix">
					<h4>Change Password</h4>
					<div class="field">
						<label for="new_password">New Password</label>
						<input type="password" name="new_password" placeholder="New Password" class="outline">
					</div>
				</div>
				
				<div class="row clearfix">
					<div class="field">
						<label for="confirm-password">Re-enter Password</label>
						<input type="password" name="confirm_new_password" placeholder="Re-enter New Password" class="outline">
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
