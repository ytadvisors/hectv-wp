<?php
/*
Template Name: Contact
*/
?>

<?php get_header(); ?>

	<!-- main#contact -->

	<main class="page clearfix" id="contact">

    	<?php if (have_posts()): while (have_posts()) : the_post(); ?>
    	<div class="page-inner">
        	<section class="page-left">
        		<div class="page-description module-wide">
        			<div class="inner">
            			<h2>Contact Us</h2>
            			<p><?php the_content(); ?></p>
        			</div>
        		</div>

        		<div class="module-wide" id="map-canvas">
	        		<iframe width="100%" height="532" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/place?q=3221%20McKelvey%20Rd%2C%20Bridgeton%2C%20MO%2C%20United%20States&key=AIzaSyDuI126apnO2RceXGPRKGGjRMLIF8wGT0A"></iframe>
        		</div>
        	</section>

        	<!---- End Page Left ----->

        	<section class="page-right">

        		<div class="contact-form-module dark module">

        			<div class="inner">

	            		<form class="contact-form" method="post" action="/contact/">

		            		<input type="hidden" name="action" value="contact">

		            		<div class="field">
		            			<input type="name" name="name" placeholder="Name" class="required">
		            		</div>

		            		<div class="field">
		            			<input type="email" name="email" placeholder="Email Address" class="required">
		            		</div>

		            		<div class="field">
		            			<input type="text" name="school" placeholder="School">
		            		</div>

		            		<div class="field">
		            			<input type="text" name="address" placeholder="Address">
		            		</div>

		            		<div class="field">
		            			<input type="text" name="city" placeholder="City">
		            		</div>

		            		<div class="field">
		            			<input type="text" name="state" placeholder="State">
		            		</div>

		            		<div class="field">
		            			<input type="text" name="zip" placeholder="Zip Code">
		            		</div>

		            		<div class="field">
			            		<?php if( have_rows('contact_subjects') ): ?>

								<select name="subject" id="subject" style="width:100%;">
									<option selected="selected">I'm writing about...</option>
									<?php $x = 1; ?>
									<?php while ( have_rows('contact_subjects') ) : the_row(); ?>
									<option value="<?php echo $x; ?>"><?php the_sub_field('subject'); ?></option>
									<?php $x++; ?>
									<?php endwhile; ?>
								</select>

								<?php endif; ?>
		            		</div>

		            		<div class="field">
			            		<textarea class="comments required" name="comments" placeholder="Comments"></textarea>
		            		</div>

							<div class="check-wrap">

	            				<input type="checkbox" id="email-updates" name="email-updates" value="email-updates" checked="checked">
	            				<label for="email-updates"><span>Stay In Touch!</span></label>
	            				<span>Receive Email Updates From HEC-TV.</span>

	            			</div>

	            			<div class="btn-wrap">
	            				<button type="submit" class="btn-white">Submit</button>
	            			</div>

	            		</form>

	            		<div class="response" style="display:none;">
				    		<img class="close-response" src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-close-fff.png">
				    		<div class="message">
					    		<h2></h2>
					    		<p></p>
				    		</div>
			    		</div>

        			</div>

        		</div>

        	</section>

        	<!---- End Page Right ----->


			<section class="content-row clearfix">
				<div id="address" class="match-contact contact-module dark module clearfix">
					<div class="inner">
						<address>
							<span class="address"><?php the_field('address'); ?></span>
							<span class="phone">Phone <?php the_field('phone_number'); ?></span>
							<span class="fax">Fax <?php the_field('fax_number'); ?></span>
						</address>
						<h4>Where to Find Us</h4>
						<p><?php the_field('directions'); ?></p>

<!-- 						<div class="btn-wrap"><a href="http://google.com" class="btn">Contact Us</a></div> -->
					</div>
				</div>

				<div id="opportunities" class="match-contact opportunities module">
					<div class="inner">
						<h2>Opportunities</h2>
						<p><?php the_field('opportunities'); ?></p>
					</div>
				</div>
			</section>


			<div class="mobile clearfix">
				<div class="left clearfix">
					<div class="contact-module dark module clearfix">
						<div class="inner">
							<address>
								<span class="address"><?php the_field('address'); ?></span>
								<span class="phone">Phone <?php the_field('phone_number'); ?></span>
								<span class="fax">Fax <?php the_field('fax_number'); ?></span>
							</address>
							<h4>Where to Find Us</h4>
							<p><?php the_field('directions'); ?></p>
						</div>
					</div>

					<div class="opportunities module">
						<div class="inner">
							<h2>Opportunities</h2>
							<p><?php the_field('opportunities'); ?></p>
						</div>
					</div>

				</div> <!-- End Left -->

				<div class="right clearfix">
				<!-- Contact Form Mobile -->

					<div class="contact-form-module-mobile dark module">
	        			<div class="inner">

		            		<form class="contact-form" method="post" action="/contact/">

		            		<input type="hidden" name="action" value="contact">

		            		<div class="field">
		            			<input type="name" name="name" placeholder="Name" class="required">
		            		</div>

		            		<div class="field">
		            			<input type="email" name="email" placeholder="Email Address" class="required">
		            		</div>

		            		<div class="field">
		            			<input type="text" name="school" placeholder="School">
		            		</div>

		            		<div class="field">
		            			<input type="text" name="address" placeholder="Address">
		            		</div>

		            		<div class="field">
		            			<input type="text" name="city" placeholder="City">
		            		</div>

		            		<div class="field">
		            			<input type="text" name="state" placeholder="State">
		            		</div>

		            		<div class="field">
		            			<input type="text" name="zip" placeholder="Zip Code">
		            		</div>

		            		<div class="field">
			            		<?php if( have_rows('contact_subjects') ): ?>

								<select name="subject" id="subject" style="width:100%;">
									<option selected="selected">I'm writing about...</option>
									<?php $x = 1; ?>
									<?php while ( have_rows('contact_subjects') ) : the_row(); ?>
									<option value="<?php echo $x; ?>"><?php the_sub_field('subject'); ?></option>
									<?php $x++; ?>
									<?php endwhile; ?>
								</select>

								<?php endif; ?>
		            		</div>

		            		<div class="field">
			            		<textarea class="comments required" name="comments" placeholder="Comments"></textarea>
		            		</div>

							<div class="check-wrap">

	            				<input type="checkbox" id="email-updates" name="email-updates" value="email-updates" checked="checked">
	            				<label for="email-updates"><span>Stay In Touch! Receive Email Updates From HEC-TV.</span></label>

	            			</div>

	            			<div class="btn-wrap">
	            				<button type="submit" class="btn-white">Submit</button>
	            			</div>

	            		</form>

	            		<div class="response" style="display:none;">
				    		<img class="close-response" src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-close-fff.png">
				    		<div class="message">
					    		<h2></h2>
					    		<p></p>
				    		</div>
			    		</div>

	        		</div>

					</div>

				</div> <!-- End Right -->
			</div> <!-- End Mobile -->


    	</div>

		<?php endwhile; ?>
		<?php endif; ?>

    </main>

    <!-- /main#contact -->

    <script type="text/javascript">
		jQuery(document).ready(function(){

			if(window.location.hash) {
				var hash      = window.location.hash.substring(1);
				if( hash == "request" ){

					jQuery("select#subject option").eq(1).attr("selected", "selected");

				}
			}

			var contactPageTopHeight = jQuery("div#contact-us-top").height();
			var contactModuleHeight  = jQuery("div#contact-module-form").height();
			var contactMapHeight     = jQuery("div#map-canvas").height();

			if( ( contactPageTopHeight + contactMapHeight ) < contactModuleHeight ){

				var delta = ( ( contactModuleHeight - contactPageTopHeight ) - 28 );

				jQuery("div#map-canvas").height(delta);

			}


		});
    </script>



<?php get_footer(); ?>
