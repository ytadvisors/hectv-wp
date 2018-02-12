<?php 
	
	
add_action( 'show_user_profile', 'add_user_meta_fields' );
add_action( 'edit_user_profile', 'add_user_meta_fields' );
add_action( 'personal_options_update', 'save_user_meta_fields' );
add_action( 'init', 'save_front_end_user_meta_fields', 10 );
// add_action( 'init', 'save_user_meta_fields' );

function add_user_meta_fields( $user ){ ?>

		<?php $user_meta = get_user_meta( $user->ID ); ?>

        <h3>User Street Address</h3>
        
        <table class="form-table">
	        
	        <tr>
                <th><label for="saved_videos">Saved Videos</label></th>
                <td><input type="text" name="saved_videos" value="<?php echo esc_attr($user_meta['saved_videos'][0]); ?>"/></td>
            </tr>
	       
            <tr>
                <th><label for="address_line_1">Address Line 1</label></th>
                <td><input type="text" name="address_line_1" value="<?php echo esc_attr($user_meta['address_line_1'][0]); ?>"/></td>
            </tr>

            <tr>
                <th><label for="address_line_2">Address Line 2</label></th>
                <td><input type="text" name="address_line_2" value="<?php echo esc_attr($user_meta['address_line_2'][0]); ?>"  /></td>
            </tr>

            <tr>
                <th><label for="city">City</label></th>
                <td><input type="text" name="city" value="<?php echo esc_attr($user_meta['city'][0]); ?>"/></td>
            </tr>
            <tr>
                <th><label for="city">State</label></th>
                <td><?php $state_names_with_codes = Array('AL' => 'Alabama','AK' => 'Alaska','AZ' => 'Arizona','AR' => 'Arkansas','CA' => 'California','CO' => 'Colorado','CT' => 'Connecticut','DE' => 'Delaware','DC' => 'District Of Columbia','FL' => 'Florida','GA' => 'Georgia','HI' => 'Hawaii','ID' => 'Idaho','IL' => 'Illinois','IN' => 'Indiana','IA' => 'Iowa','KS' => 'Kansas','KY' => 'Kentucky','LA' => 'Louisiana','ME' => 'Maine','MD' => 'Maryland','MA' => 'Massachusetts','MI' => 'Michigan','MN' => 'Minnesota','MS' => 'Mississippi','MO' => 'Missouri','MT' => 'Montana','NE' => 'Nebraska','NV' => 'Nevada','NH' => 'New Hampshire','NJ' => 'New Jersey','NM' => 'New Mexico','NY' => 'New York','NC' => 'North Carolina','ND' => 'North Dakota','OH' => 'Ohio','OK' => 'Oklahoma','OR' => 'Oregon','PW' => 'Palau','PA' => 'Pennsylvania','PR' => 'Puerto Rico','RI' => 'Rhode Island','SC' => 'South Carolina','SD' => 'South Dakota','TN' => 'Tennessee','TX' => 'Texas','UT' => 'Utah','VT' => 'Vermont','VA' => 'Virginia','WA' => 'Washington','WV' => 'West Virginia','WI' => 'Wisconsin','WY' => 'Wyoming'); ?>
					
					<div class="field state">
						<select name="state">
							<?php $current_state = esc_attr($user_meta['state'][0]); ?>
							
							<?php foreach( $state_names_with_codes as $index => $state ){ ?>
							
								<?php if( $current_state == $index ){ ?>
								
									<option value="<?php echo $index; ?>" selected><?php echo $state; ?></option>
								
								<?php }else{ ?>
								
									<option value="<?php echo $index; ?>"><?php echo $state; ?></option>
								
								<?php } ?>
							<?php } ?>
						</select>				
										
					</div></td>
            </tr>
            <tr>
                <th><label for="city">Zip Code</label></th>
                <td><input type="text" name="zip_code" value="<?php echo esc_attr($user_meta['zip_code'][0]); ?>"/></td>
            </tr>
            
        </table>
           		     
<?php } 
	
	function save_user_meta_fields( $user_id ){
		
		$user_update = array(
			
		    'ID'         => $user_id,
		    'first_name' => esc_attr( $_POST['first_name'] ),
		    'last_name'  => esc_attr( $_POST['last_name'] )
		    
		);
		
		wp_update_user( $user_update );
		
	    update_user_meta( $user_id, 'address_line_1', sanitize_text_field( $_POST['address_line_1'] ) );
	    update_user_meta( $user_id, 'address_line_2', sanitize_text_field( $_POST['address_line_2'] ) );
	    update_user_meta( $user_id, 'city', sanitize_text_field( $_POST['city'] ) );
	    update_user_meta( $user_id, 'state', sanitize_text_field( $_POST['state'] ) );
	    update_user_meta( $user_id, 'zip_code', sanitize_text_field( $_POST['zip_code'] ) );
		update_user_meta( $user_id, 'saved_videos', sanitize_text_field( $_POST['saved_videos'] ) );
			    
	}

	
	function save_front_end_user_meta_fields(){

		$user = wp_get_current_user();
		
		if( empty( $user->ID ) || $_POST['action'] != 'update-user-settings' ){
			
			return;
			
		}
		
		if( isset( $_POST['new_password'] ) ){
			
			$password = sanitize_text_field( $_POST['new_password'] );
			
			wp_set_password( $password, $user->ID );	
			
		}
			
		$user_update = array(
			
		    'ID'         => $user_id,
		    'first_name' => esc_attr( $_POST['first_name'] ),
		    'last_name'  => esc_attr( $_POST['last_name'] )
		    
		);
				
		wp_update_user( $user_update );
		
		error_log('fire 2!');
		
	    update_user_meta( $user->ID,'address_line_1', sanitize_text_field( $_POST['address_line_1'] ) );
	    update_user_meta( $user->ID,'address_line_2', sanitize_text_field( $_POST['address_line_2'] ) );
	    update_user_meta( $user->ID,'city', sanitize_text_field( $_POST['city'] ) );
	    update_user_meta( $user->ID,'state', sanitize_text_field( $_POST['state'] ) );
	    update_user_meta( $user->ID,'zip_code', sanitize_text_field( $_POST['zip_code'] ) );
	    
	}


?>