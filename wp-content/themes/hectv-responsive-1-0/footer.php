			<div class="clearfix"></div>

			<!-- footer -->
			<footer id="footer">

				<div class="inner clearfix">

					<div class="left">
						<a href="<?php echo esc_url( home_url() ); ?>">
							<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-footer-logo_white.png">
						</a>
					</div>

					<div class="right clearfix">

						<div class="footer-wrap">

							<?php wp_nav_menu( array( 'theme_location' => 'footer-menu', 'menu_class' => 'clearfix', 'menu_id' => 'primary' ) ); ?>
						</div>

					</div> <!-- End Footer Right -->

					<span class="copyright">&copy; 2015 Higher Education Channel</span>

				</div> <!-- End Inner -->

            </footer>
			<!-- /footer -->
			
			<div id="newsletter-modal" class="modal">
				
				<div class="inner">
					
					<button class="close-modal" title="Close" style="background-image: url('http://hectv.staging.wpengine.com/wp-content/themes/hectv-responsive-1-0/_/graphics/ui-close-dark.png');"></button>
					
					<div class="sign-up">
						<h2>Sign Up For Our Newsletter</h2>
						<p>Enter your name and email address to receive updates from Higher Education Channel</p>
						
						<form class="mailing-list-form" method="post" action="/">
							<input type="hidden" name="action" value="email-subscribe">
			        		<div class="field">
			    				<input type="name" name="name" placeholder="Name" class="required">
							</div>
			
							<div class="field">
			    				<input type="email" name="email" placeholder="Email" class="required">
							</div>
			
							<div class="btn-wrap">
								<button class="btn-blue btn" type="submit">Submit</button>
							</div>
			    		</form>
					</div>
		    		
		    		<div class="response" style="display:none;">
			    		<div class="message">
				    		<h2>E-Mail Added</h2>
				    		<p>Looks like we already have an email on file for you. <a href=\"mailto:info@hectv.org\">Contact us</a> if this is in error.</p>
			    		</div>
		    		</div>
					
				</div>
				
			</div>

		</div>
		<!-- /wrapper -->

		<?php require_once('_/inc/modals.php'); ?>

		<?php wp_footer(); ?>

		<!-- analytics -->
		<script>
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
		
		  ga('create', 'UA-13018774-2', 'auto');
		  ga('send', 'pageview');
		</script>

	</body>
</html>
