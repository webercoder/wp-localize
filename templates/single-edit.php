<?php wp_nonce_field( $data['nonce_file'], WP_Localize::$NONCE_KEY ); ?>
<div id="map_controls" style="margin-bottom:10px;">
	<ul>
		<li>
			<label style="width:75px;display:inline-block;margin-right:5px;" 
				for="<?php echo WP_Localize::$KEY_LOCATION_ADDRESS; ?>"
				><?php _e( 'Location' ); ?></label>
			<input type="text" 
				name="<?php echo WP_Localize::$KEY_LOCATION_ADDRESS; ?>-user" 
				id="<?php echo WP_Localize::$KEY_LOCATION_ADDRESS; ?>-user"
				value="<?php echo $data[WP_Localize::$KEY_LOCATION_ADDRESS]; ?>"/>
			<input type="button" id="update-map" value="Set"/>
		</li>
		<li>
			<label style="width:75px;display:inline-block;margin-right:5px;">Computed</label>
			<span id="<?php echo WP_Localize::$KEY_LOCATION_ADDRESS; ?>-computed"
				><?php echo $data[WP_Localize::$KEY_LOCATION_ADDRESS]; ?></span>

		</li>
	</ul>
</div>

<div id="map_canvas" style="width: 300px; height: 200px;"></div>

<input type="hidden"
		name="<?php echo WP_Localize::$KEY_LOCATION_ADDRESS; ?>" 
		id="<?php echo WP_Localize::$KEY_LOCATION_ADDRESS; ?>"
		value="<?php echo $data[WP_Localize::$KEY_LOCATION_ADDRESS]; ?>"/>
<input type="hidden"
		name="<?php echo WP_Localize::$KEY_LOCATION_LAT; ?>" 
		id="<?php echo WP_Localize::$KEY_LOCATION_LAT; ?>"
		value="<?php echo $data[WP_Localize::$KEY_LOCATION_LAT]; ?>"/>
<input type="hidden"
		name="<?php echo WP_Localize::$KEY_LOCATION_LNG; ?>" 
		id="<?php echo WP_Localize::$KEY_LOCATION_LNG; ?>"
		value="<?php echo $data[WP_Localize::$KEY_LOCATION_LNG]; ?>"/>
<input type="hidden"
		name="<?php echo WP_Localize::$KEY_LOCATION_STATE; ?>" 
		id="<?php echo WP_Localize::$KEY_LOCATION_STATE; ?>"
		value="<?php echo $data[WP_Localize::$KEY_LOCATION_STATE]; ?>"/>
<input type="hidden"
		name="<?php echo WP_Localize::$KEY_LOCATION_COUNTRY; ?>" 
		id="<?php echo WP_Localize::$KEY_LOCATION_COUNTRY; ?>"
		value="<?php echo $data[WP_Localize::$KEY_LOCATION_COUNTRY]; ?>"/>
