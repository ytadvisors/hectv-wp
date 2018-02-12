<?php function hectv_interactive_takeover(){ ?>

	<div class="page-inner clearfix">

		<script type="text/javascript">

			var toggleMessages;
			var pullUpdates;
			var sharesReceived   = new Array();
			var messagesReceived = new Array();
			var validateURL      = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;

			jQuery(document).ajaxStart(function(){

				jQuery("#ajax-activity").show();

			}).ajaxStop(function(){

				jQuery("#ajax-activity").hide();

			});

			jQuery(document).ready(function(){

				do{

					jQuery("div.ask.module").height( "+=1px" );
					jQuery("div.ask.module").find(".flex").css( "marginTop", "+=1px" );

				}while( jQuery("div.takeover.module").height() >= jQuery("div.ask.module").height() )


				jQuery("div#message img.close").click(function(){

					jQuery(this).parents("div#message").hide();
					jQuery("div#composer").fadeTo(500, 1.0);

				});

				jQuery("form#interactive-ask-form").submit(function(e){

					var $form    = jQuery(this);
					var formData = $form.serialize();
					var errors   = 0;

					$form.find("input, select, textarea, email").each(function(){

						if( jQuery(this).hasClass("required") && ( jQuery(this).val() == "" || !jQuery(this).val() ) ){

							jQuery(this).addClass("error-required");
							errors++;

						}

					});

					if( errors > 0 ){

						return false;

					}

					var responseArea = $form.parents("div.module").find("div.response");

					jQuery.ajax({
						url: '/',
						type: 'post',
						dataType: 'json',
						data: formData,
						success: function(data) {

							console.log( data );

							jQuery("textarea.question").val("");

							jQuery("div#toggle-message").find("p#important-message").html( data.message );
							jQuery("div#toggle-message").addClass("important").removeClass("active");

							setTimeout(function(){

								jQuery("div#toggle-message").removeClass("important").removeClass("active");
								jQuery("div#toggle-message").find("p#important-message").html("");

							}, 10000);

						},
						error: function(jqXHR, textStatus, errorThrown){

							responseArea.find("h2").html("Something went wrong...");
							responseArea.find("p").html("It appears that something went wrong. Please try again.<br/><br/>More info: " + textStatus );
							responseArea.show();

						}
					});

					e.preventDefault();
					return false;

				});


				/* Login Form */

				jQuery("form#interactive-join-form").submit(function(e){

					var $form    = jQuery(this);
					var formData = $form.serialize();
					var errors   = 0;

					$form.find("input, select, textarea, email").each(function(){

						if( jQuery(this).hasClass("required") && ( jQuery(this).val() == "" || !jQuery(this).val() ) ){

							jQuery(this).addClass("error-required");
							errors++;

						}else{

							jQuery(this).removeClass("error-required");

						}

					});

					if( errors > 0 ){

						return false;

					}

					var responseArea = $form.parents("div.module").find("div.response");

					jQuery.ajax({
						url: '/',
						type: 'post',
						dataType: 'json',
						data: formData,
						success: function(data) {

							console.log(data);

							if( data.status ){

								jQuery("input#user").val( data.user );
								jQuery("input#key").val( data.security );

								toggleMessages = setInterval(function(){

									if( jQuery("div#toggle-message").hasClass("important") ){

										return;

									}

									jQuery("div#toggle-message").toggleClass("active");

								}, 5000);

								$processShares(data, jQuery("ul.feed") );

								pullUpdates = setInterval( callForUpdates, 10000);

								jQuery("div#interactive-stage").addClass("active");

							}else{

								responseArea.find("h2").html("Something went wrong...");
								responseArea.find("p").html("It appears that something went wrong. Please try again.");
								responseArea.show();

							}

						},
						error: function(jqXHR, textStatus, errorThrown){

							responseArea.find("h2").html("Something went wrong...");
							responseArea.find("p").html("It appears that something went wrong. Please try again.<br/><br/>More info: " + textStatus );
							responseArea.show();

						}
					});

					e.preventDefault();
					return false;

				});

			});

			var callForUpdates = function(){

				jQuery.ajax({
					type: "POST",
					url: "/",
					data: { "action": "interactive", "type": "update", "user": jQuery("input#user").val(), "key": jQuery("input#key").val() },
					cache: false,
					success: function(response){

						console.log("call for updates");
						console.log(response);
						$processShares(response, jQuery("ul.feed") );

					},
					dataType: "json"
				});

			}


			var $processShares = function(response, $destination){

				if( response.shares ){

					jQuery.each( response.shares, function( i, item ){

						if( sharesReceived.indexOf( parseInt( item.id ) ) == -1 ){

							sharesReceived.push( parseInt( item.id ) );

							switch( item.shareType ){

								case "5":
									var type = "question";
								break;

							}

							$destination.find("div.empty").remove();

							if( item.shareType == 5 ){

								$destination.prepend( '<li class="' + type + '"><div class="title"><h3>Question:</h3>' + item.shareTitle + '</div></li>' );

							}else{

								$destination.prepend( '<li class="' + type + '"><div class="contents"><a target="_blank" href="' + item.shareAddress + '">' + item.shareTitle + '</a></div></li>' );

							}


						}

					});

				}

				if( response.messages ){

					jQuery.each( response.messages, function( i, message ){

						if( messagesReceived.indexOf( parseInt( message.id ) ) == -1 ){

							messagesReceived.push( parseInt( message.id ) );

							jQuery("div#composer").fadeTo( 250, 0.3, function(){

								jQuery("div#message").find("p").html( message.shareContent );
								jQuery("div#message").show();

							});

						}

					});

				}

			}

		</script>

		<div id="interactive-stage" class="clearfix">

			<section class="page-left">

				<div class="takeover module">

					<div class="inner clearfix">

						<h2><?php echo get_option( "interactive_series" ); ?></h2>
						<h3><?php echo get_option( "interactive_title" ); ?></h3>
						<p><?php echo get_option( "interactive_description" ); ?></p>

					</div>

					<div class="video-wrap">

						<?php echo stripslashes( get_option( "interactive_embed" ) ); ?>

					</div>

				</div> <!-- End Takeover  -->

				</section>

				<section class="page-right">

		        	<div class="ask module dark">

			        	<img id="ajax-activity" src="/wp-content/plugins/hectv-interactive/ui/ajax-loader.gif">

		        		<div class="inner" id="composer">

		            		<h2>Ask A Question</h2>

							<div id="toggle-message" class="">
			            		<p class="first">Join in the conversation by asking questions using the field below.</p>
			            		<p class="second">If the web producer shares links, audio or video, they will appear via the feed in this window.</p>
			            		<p id="important-message"></p>
							</div>

		            		<form class="ask-form" id="interactive-ask-form" method="post" action="/">

			            		<input type="hidden" id="action" name="action" value="interactive">
								<input type="hidden" id="type" name="type" value="question">

								<input type="hidden" id="user" name="user" value="">
								<input type="hidden" id="key" name="key" value="">

								<div class="field">
			            			<textarea class="question required" name="user-question" placeholder="Type your question or message here..."></textarea>
								</div>
		            			<button class="btn">Submit</button>

		            		</form>

							<h2 style="margin:10% 0 10px 0;font-size:1.4em;">Feed</h2>
		            		<ul class="feed"><div class="empty">No items have been shared yet</div></ul>
		        		</div> <!-- End Composer -->

		        		<div class="inner" id="interactive-join">

		            		<h2>Join The Discussion</h2>

		            		<p style="margin-top:30px;">Join in the live discussion! Ask questions and interact with the show</p>
		            		<p>To start, fill out the information below:</p>

		            		<form id="interactive-join-form" class="flex" method="post" action="/">

								<input type="hidden" name="action" value="interactive">
								<input type="hidden" name="type" value="initialize">

		            			<div class="field">
			            			<input type="text" name="user-name" placeholder="Name" class="required" value="">
		            			</div>

			            		<div class="field">
			            			<input type="email" name="user-email" placeholder="Email" class="required" value="">
			            		</div>

			            		<div class="field">
			            			<input type="location" name="user-location" placeholder="Location" class="required" value="">
			            		</div>

		            			<div class="check-wrap">

		            				<input type="checkbox" id="email-updates" name="email-subscribe" value="1" checked="checked">
		            				<label for="email-updates" style="font-size:0.8em;position:relative;top:3px;">Stay in Touch! Receive e-mail updates from HEC-TV.</label>


		            			</div>

			            		<div class="btn-wrap" style="margin-top:20px;">
			            			<button class="btn">Sign In</button>
			            		</div>

		            		</form>

		        		</div><!-- End Join -->

		        		<div class="inner" id="message">

			        		<img class="close" src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-close-000.png">

			        		<h3>Message from HEC-TV</h3>
			        		<p></p>

		        		</div>

		        	</div>

				</section>

		</div> <!-- End Interactive Stage -->

	</div>

<?php } ?>