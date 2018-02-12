<div class="dark module" id="register-now-modal">

	<div class="inner">
		<h2>Register Now</h2>
		<p id="rsvp-display-instructions">Attend a live taping of this episode by filling out the form below.</p>

		<div class="info-wrap">
			<span>Date: <span id="rsvp-display-date" class="date">April 18, 2015</span></span>
			<span>Time: <span id="rsvp-display-time" class="time">10:00am</span></span>
		</div>

		<form id="rsvp-request" method="GET" action="/">

			<input type="hidden" id="action" name="action" value="start_rsvp">
			<input type="hidden" id="rsvp-episodeID" name="episodeID" value="">
			<input type="hidden" id="rsvp-seriesID" name="seriesID" value="">
			<input type="hidden" id="rsvp-date" name="rsvp-date" value="">

			<div class="field">
				<input type="text" name="rsvp-name" placeholder="Name" id="rsvp-name" class="required">
			</div>

    		<div class="field">
    			<input type="email" name="rsvp-email" placeholder="Email" id="rsvp-email" class="required">
    		</div>

			<div class="field">
    			<input type="text" name="rsvp-school" placeholder="School" id="rsvp-school" class="required">
    		</div>

    		<div class="info-wrap">

        		<div class="time field">

        			<label for="time">Time:</label>
        			<select name="rsvp-time" id="rsvp-time" class="select-box required">
        				<option value="1">10:00am</option>
        			</select>

        		</div>

    		</div>

    		<div class="check-wrap">
				<input type="checkbox" id="rsvp-email-updates" name="email-updates" value="email-updates" checked="checked">
				<label for="rsvp-email-updates"><span style="font-size:0.8em;position:relative;top:3px;">Stay in Touch! Receive e-mail updates from HEC-TV.</span></label>
			</div>

			<div class="btn-wrap">
				<button type="submit" class="btn">Submit</button>
				<button type="button" class="btn close-modal">Cancel</button>
			</div>
		</form>

		<div class="response" style="display:none;">
			<div class="message">
	    		<h2></h2>
	    		<p></p>
	    		<div style="text-align:center;margin-top:120px;">
		    		<button type="button" class="btn close-modal">Okay</button>
	    		</div>
			</div>
		</div>

	</div>

</div> <!-- End Register Now Modal -->