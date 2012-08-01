<?php
/*
Plugin Name: WP Localize
Plugin URI: http://github.com/weberwithoneb/wp-localize
Description: Allows posts to be tagged using a location.
Version: 1.0
Author: Mike Weber
License: All Rights Reserved
*/
class WP_Localize {

	// Constants
	public static $KEY_LOCATION_ADDRESS   = 'geo-loc-address'; // FULL address, including state and country
	public static $KEY_LOCATION_LAT       = 'geo-loc-lat';
	public static $KEY_LOCATION_LNG       = 'geo-loc-lng';
	public static $KEY_LOCATION_STATE     = 'geo-loc-state';
	public static $KEY_LOCATION_COUNTRY   = 'geo-loc-country';
	public static $NONCE_KEY              = 'geo-nonce';
	public static $MAPS_KEY               = 'insert key here';
	public static $DB_SUFFIX              = 'localize_geo';
	public static $SEARCH_RADIUS          = 50; // Miles
	
	// Static vars
	public static $localized;
	public static $current_lat;
	public static $current_lng;

	public static function install() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$DB_SUFFIX;
		
		// There are all sorts of ridiculous restrictions on the formatting of this query.
		//http://codex.wordpress.org/Creating_Tables_withs#Creating_or_Updating_the_Table
		$sql = "CREATE TABLE ${table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			lat int(11) NOT NULL,
			lng int(11) NOT NULL,
			state varchar(50) NOT NULL,
			country varchar(50) NOT NULL,
			address varchar(255) NOT NULL,
			PRIMARY KEY  (id),
			KEY state (state),
			KEY country (country)
		);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	
	public static function init_meta_box() {
		add_action( 'add_meta_boxes', 'WP_Localize::meta_box_add' );	
	}

	public static function meta_box_add() {
		add_meta_box( 
			'geo-meta-box',
			esc_html__( 'Geolocation' ),
			'WP_Localize::meta_box_add_content',
			'post',
			'normal',
			'default'
		);
	}

	public static function meta_box_add_content( $object, $box ) {
		global $post, $wpdb;
		
		// Set the data in the view's data array
		$data = array(
			'object'                        => $object,
			'box'                           => $box,
			self::$KEY_LOCATION_ADDRESS     => '',
			self::$KEY_LOCATION_LAT         => '',
			self::$KEY_LOCATION_LNG         => '',
			self::$KEY_LOCATION_STATE       => '',
			self::$KEY_LOCATION_COUNTRY     => '',
			'nonce_file'                    => basename( __FILE__ )
		);
		
		// Get current location data
		$sql = "SELECT lat, lng, state, country, address 
		        FROM   " . $wpdb->prefix . self::$DB_SUFFIX . "
		        WHERE  post_id = " . $post->ID;
		$row = $wpdb->get_row( $sql );
		if ( ! is_null( $row ) ) {
			$data[self::$KEY_LOCATION_ADDRESS]  = $row->address;
			$data[self::$KEY_LOCATION_LAT]      = $row->lat;
			$data[self::$KEY_LOCATION_LNG]      = $row->lng;
			$data[self::$KEY_LOCATION_STATE]    = $row->state;
			$data[self::$KEY_LOCATION_COUNTRY]  = $row->country;
		}
		
		// get the options current value from the database and assign it in $data
		self::render_template( 'single-edit', $data );
	}

	public static function save( $post_id ){
		
		global $post, $wpdb;
		
		// Verify nonce.
		if ( ! isset( $_POST[self::$NONCE_KEY] ) || 
			 !wp_verify_nonce( $_POST[self::$NONCE_KEY], basename( __FILE__ ) ) ) {
			return $post_id;
		}
		
		// Verify that user has the permissions to do this.
		$post_type = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type->cap->edit_others_posts, $post_id ) ) {
			return $post_id;
		}
		
		if ( !wp_is_post_revision($post_id) ) {

			// Get new location values.
			$address = $_POST[self::$KEY_LOCATION_ADDRESS];
			$lat = $_POST[self::$KEY_LOCATION_LAT];
			$lng = $_POST[self::$KEY_LOCATION_LNG];
			$state = $_POST[self::$KEY_LOCATION_STATE];
			$country = $_POST[self::$KEY_LOCATION_COUNTRY];

			// Clear old values
			$sql_delete = "DELETE FROM " . $wpdb->prefix . self::$DB_SUFFIX . " 
		                   WHERE post_id = %d";
			$result = $wpdb->query( $wpdb->prepare( $sql_delete, $post_id ) );

			// Save new ones
			$sql_insert = "INSERT INTO " . $wpdb->prefix . self::$DB_SUFFIX . "
						(post_id, lat, lng, state, country, address)
						VALUES (%d, %d, %d, %s, %s, %s)";
			$result = $wpdb->query( $wpdb->prepare( 
				$sql_insert, $post_id, $lat, $lng, $state, $country, $address
			) );
		
		}

	}

	public static function include_scripts() {

		wp_enqueue_script(
			'google-maps',
			'http://maps.googleapis.com/maps/api/js?key=' . self::$MAPS_KEY . '&sensor=false',
			array(),
			false,
			true
		);

		wp_enqueue_script(
			'google-loader',
			'http://www.google.com/jsapi',
			array(),
			false,
			true
		);

		$script_name = plugins_url( 'resources/maps.js', __FILE__ );
		wp_enqueue_script(
			'admin-maps',
			$script_name,
			array( 'google-maps', 'jquery' ),
			false,
			true
		);

	}

	public static function retrieve_coordinates_for_location() {
		
		self::$localized = false;
		
		if ( ! is_single() && ! is_author() ) {
			
			$location = sanitize_text_field($_GET['location']);
			$request_url = 'http://maps.google.com/maps/geo?output=xml&key=' . self::$MAPS_KEY . 
						'&q=' . urlencode($location);
			$xml = simplexml_load_file($request_url);

			$status = $xml->Response->Status->code;
			if (strcmp($status, "200") == 0) {
			
				$coordinates = $xml->Response->Placemark->Point->coordinates;
				$coordinatesSplit = split(",", $coordinates);
				// Format: Longitude, Latitude, Altitude
				self::$localized = true;
				self::$current_lat = $coordinatesSplit[1];
				self::$current_lng = $coordinatesSplit[0];
				
			}
			
		}
		
	}
	
	public static function localize_fields( $fields = '' ) {
		global $wpdb;	
		if ( self::$localized ) {
			$table_name = $wpdb->prefix . self::$DB_SUFFIX;				
			// Equation from Google Store Locator demo https://developers.google.com/maps/articles/phpsqlsearch_v3
			$fields .= ', ( 3959 * acos( cos( radians(' . self::$current_lat . ') ) * cos( radians( ' . $table_name . '.lat ) ) * cos( radians( ' . $table_name . '.lng ) - radians(' . self::$current_lng . ') ) + sin( radians(' . self::$current_lat . ') ) * sin( radians( ' . $table_name . '.lat ) ) ) ) AS distance';
		}
		return $fields;
	}
	
	public static function localize_join( $join = '' ) {
		global $wpdb;
		if ( self::$localized ) {
			$table_name = $wpdb->prefix . self::$DB_SUFFIX;
			$join .= ' JOIN ' . $table_name . 
					 ' ON ' . $wpdb->posts . '.ID = ' . $table_name . '.post_id ';
		}
		return $join;
	}
	
	public static function localize_groupby( $clause = '' ) {
		global $wpdb;
		if ( self::$localized ) {
			$clause .= $wpdb->posts . '.ID'; // This is unnecessary for query but 
			                                 // required b/c WP prefixes having with GROUP BY.
			$clause .= ' HAVING distance <= ' . self::$SEARCH_RADIUS;
		}
		return $clause;
	}

	private static function render_template( $name, $data ) {
		$path = join( DIRECTORY_SEPARATOR, array(
			dirname(__FILE__), 'templates', $name . '.php' 
		) );
		include( $path );
	}

}

if ( ! is_admin() ) {
	
	add_action( 'init', 'WP_Localize::retrieve_coordinates_for_location' );
	add_filter( 'posts_fields', 'WP_Localize::localize_fields' );
	add_filter( 'posts_join', 'WP_Localize::localize_join' );
	add_filter( 'posts_groupby', 'WP_Localize::localize_groupby' );
	
} else {
	
	// Single edit
	add_action( 'load-post.php', 'WP_Localize::init_meta_box' );
	add_action( 'load-post-new.php', 'WP_Localize::init_meta_box' );

	// Enable maps
	add_action( 'admin_enqueue_scripts', 'WP_Localize::include_scripts' );

	// Save post for both quick and single edit
	add_action( 'save_post', 'WP_Localize::save' );

	// Register activation installation hook
	register_activation_hook( __FILE__, 'WP_Localize::install' );
	
}
