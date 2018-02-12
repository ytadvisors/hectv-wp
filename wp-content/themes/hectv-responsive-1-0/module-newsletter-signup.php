<?php function hectv_newsletter_signup(){ ?>

	<div class="module dark mailing-list clearfix">
		<div class="inner">
    		<div class="media-type">
    			<span class="current">Sign Up</span>
    			<span class="divider">|</span>
    			<span class="show">HEC-TV Newsletter</span>
    		</div>

    		<form class="mailing-list-form" method="post" action="/">
				<input type="hidden" name="action" value="email-subscribe">
        		<div class="field">
    				<input type="name" name="name" placeholder="Name" class="required">
				</div>

				<div class="field">
    				<input type="email" name="email" placeholder="Email" class="required">
				</div>

				<div class="btn-wrap">
					<button class="btn-white" type="submit">Subscribe</button>
				</div>
    		</form>

    		<div class="response" style="display:none;">
	    		<img class="close-response" src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-close-fff.png">
	    		<div class="message">
		    		<h2>E-Mail Added</h2>
		    		<p>Looks like we already have an email on file for you. <a href=\"mailto:info@hectv.org\">Contact us</a> if this is in error.</p>
	    		</div>
    		</div>

    	</div>

	</div> <!-- End Mailing List Module -->

<?php } ?>