<?php
/*
   Plugin Name: Grooming Report Plugin
   Description: This plugin registers the 'lhgr_trails' post type.
   Version: 1.0
   License: GPLv2
 */

/* Include our shortcode generation file */
require_once( 'shortcode.php' );

/* Include our POST handler for the groomer's entry page */
require_once( 'groomer-report-handler.php' );

/* Include our settings page */
require_once( 'settings.php' );

/* Include the file that optionally downloads the inReach KML feed */
require_once( 'inreach-cron.php' );

/* Sets some sane defaults */
function set_default_options() {
    if(!get_option('map_lat'))
	update_option('map_lat', "0");
    if(!get_option('map_lng'))
	update_option('map_lng', "0");
    if(!get_option('map_default_zoom'))
	update_option('map_default_zoom', "2");
    if(!get_option('map_tiles'))
	update_option('map_tiles', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
    if(!get_option('map_max_zoom'))
	update_option('map_max_zoom', "19");
    if(!get_option('map_attribute'))
	update_option('map_attribute', '&copy; OpenStreetMap');
}

/* Activation function */
function lhgr_plugin_activation() {
    set_default_options();

    schedule_inreach_fetch();
}

/* Register custom post type to work with */
function lhgr_create_post_type() {
    /* Set up labels */
    $labels = array(
	'name' => 'Trails',
	'singular_name' => 'Trail',
	'add_new' => 'Add New Trail',
	'add_new_item' => 'Add New Trail',
	'edit_item' => 'Edit Trail',
	'new_item' => 'New Trail',
	'all_items' => 'All Trails',
	'view_item' => 'View Trail',
	'search_items' => 'Search Trails',
	'not_found' =>  'No Trails Found',
	'parent_item_colon' => '',
	'menu_name' => 'Trails',
    );
    /* Register post type */
    register_post_type( 'lhgr_trails', array(
	'labels' => $labels,
	'has_archive' => false,
 	'public' => true,
	'supports' => array( 'title' ),
	'taxonomies' => array( 'category' ),
	'exclude_from_search' => false,
	'capability_type' => 'post',
    )
    );
}
add_action( 'init', 'lhgr_create_post_type' );

/* Remove the GPX track file when we permanently remove the trail */
function delete_gps_track_on_trail_removal($postid)
{
    $filename = get_post_meta($postid, 'gpx_track_file', true);

    if( file_exists($filename) ) {
	unlink($filename);
    }
}
add_action('before_delete_post', 'delete_gps_track_on_trail_removal', 10, 1);

function lhgr_add_meta_boxes()
{
    $screens = ['lhgr_trails'];
    foreach ($screens as $screen) {
	add_meta_box(
	    'manage_groomer_entry',
	    'Manage Entries',
	    'groomer_entry_html',
	    $screen
	);
    }

    foreach ($screens as $screen) {
	add_meta_box(
	    'gps_track_box',	// Unique ID
	    'GPS Track',	   // Box title
	    'gps_track_html',  // Content callback, must be of type callable
	    $screen			// Post type
	);
    }
}
add_action('add_meta_boxes', 'lhgr_add_meta_boxes');

function gps_track_html($post)
{
    wp_nonce_field( basename( __FILE__ ), 'lhgr_gps_track_nonce' );
    $gpx_url = get_post_meta($post->ID, 'gpx_track_url', true);

    if ($gpx_url) {
	wp_enqueue_style('leaflet-base-css');
	wp_enqueue_script('lhgr_leaflet_helper');

	$file_ext = substr(strrchr($gpx_url, "."), 1);
	
	if ($file_ext == 'gpx')
	    $cmd = 'omnivore.gpx';
	else if ($file_ext == 'kml')
	$cmd = 'omnivore.kml';
?>
    <div id="lhgr_map" style="width: 200px; height: 200px;"></div>
    <script>
     function initializeMap() {
	 var mymap = L.map('lhgr_map');

	 var track = <?php echo esc_js($cmd) . "('" . esc_js($gpx_url);?>').addTo(mymap);
	 track.on('ready', function() {
	     mymap.fitBounds(track.getBounds());
	 });

	 var tiles = L.tileLayer('<?php echo  $file_ext . esc_js(get_option('map_tiles')); ?>', {
	     maxZoom: <?php echo esc_js(get_option('map_max_zoom')); ?>,
	     attribution: '<?php echo esc_js(get_option('map_attribute')); ?>'
	 }).addTo(mymap);
     }
    </script>
<?php
} else {
    echo '<p>No current GPS track uploaded</p>';
}
?>

<label for="gpx_upload">Upload GPS Track</label>
<input name="gpx_upload" id="gpx_upload" type="file" />
	<?php
	}

	function groomer_entry_html($post) {
	    $groomer_entries = get_post_meta($post->ID, 'groomer_entry');

	    if (count($groomer_entries) == 0) {
		echo '<p>No entries for this trail</p>';
	    } else {
	?>

	    <table>
		<tr>
		    <th>Ignore</th>
		    <th>Remove</th>
		    <th>Update</th>
		    <th>Date</th>
		    <th>Comment</th>
		</tr>

		<?php
		/* Loop through each of the entries, creating a list */
		foreach ($groomer_entries as $key => $groomer_entry) {
		?>
		    <tr>
			<td><input type="radio" name="update_status[<?php echo esc_attr($key);?>]" value="ignore" checked="checked" /></td>
			<td><input type="radio" name="update_status[<?php echo esc_attr($key);?>]" value="remove" /></td>
			<td><input type="radio" name="update_status[<?php echo esc_attr($key);?>]" value="update" /></td>
			<td><input type="text" name="new_date[<?php echo esc_attr($key);?>]" value="<?php echo esc_attr($groomer_entry[0]);?>" /></td>
			<td><input type="text" name="new_comment[<?php echo esc_attr($key);?>]" value="<?php echo esc_attr($groomer_entry[1]);?>" /></td>
		    </tr>
		<?php
		}
		echo '</table>';
		}
		}

		function lhgr_save_postdata($post_id, $post)
		{
		    /* Verify the nonce before proceeding. */
		    if ( !isset( $_POST['lhgr_gps_track_nonce'] ) || !wp_verify_nonce( $_POST['lhgr_gps_track_nonce'], basename( __FILE__ ) ) )
			return $post_id;
		    
		    /* Only try to handle our post type, this code is run for all post types */
		    if ($_POST['post_type'] == 'lhgr_trails') {
			/* If the upload field has a file in it */
			if(isset($_FILES['gpx_upload']) && ($_FILES['gpx_upload']['size'] > 0)) {
			    /* Options array for the wp_handle_upload function. */
			    $upload_overrides = array( 'action' => 'editpost', 'mimes' => array('gpx' => 'application/xml', 'kml' => 'application/xml') );

			    /* Store the current uploaded file name so we can delete it */
			    $old_track = get_post_meta($post_id, 'gpx_track_file', true);

			    /* Handle the upload using WP's wp_handle_upload function. Takes the posted file and an options array */
			    $uploaded_file = wp_handle_upload($_FILES['gpx_upload'], $upload_overrides);

			    /* If the wp_handle_upload call returned a url for the gpx */
			    if(isset($uploaded_file['url'])) {
				/* Update the post meta with the URL of the file */
				update_post_meta($post_id, 'gpx_track_url', $uploaded_file['url']);
				update_post_meta($post_id, 'gpx_track_file', $uploaded_file['file']);

				/* Remove the old track, if it exists */
				if ($old_track) {
				    unlink($old_track);
				}
			    } else {
				/* wp_handle_upload returned some kind of error */
				wp_die("Failed to upload the file " . $_FILES['gpx_upload']['name'] . ", the error was <br />" . $uploaded_file['error']);
			    }
			}

			/* Handle each of the potential changes to the groomer entry info */
			if (isset($_POST['update_status'])) {

			    /* Store the original values */
			    $orig_entries = get_post_meta($post_id, 'groomer_entry');
			    
			    foreach ($_POST['update_status'] as $index => $status) {
				if ($status == 'remove') {
				    delete_post_meta($post_id, 'groomer_entry', $orig_entries[$index]);
				} else if ($status == 'update') {
				    /* Sanitize the fields, creating a date */
				    $date = date("Y-m-d", strtotime(sanitize_text_field($_POST['new_date'][$index])));
				    update_post_meta($post_id, 'groomer_entry', array($date, sanitize_text_field($_POST['new_comment'][$index])), $orig_entries[$index]);
				}
			    }
			}
		    }

		    return;
		}
		add_action('save_post', 'lhgr_save_postdata', 1, 2);

		/* To upload files, we need to change the form encoding type */
		function lhgr_add_edit_form_multipart_encoding() {

		    echo ' enctype="multipart/form-data"';

		}
		add_action('post_edit_form_tag', 'lhgr_add_edit_form_multipart_encoding');

		/* Register leaflet resources */
		function lhgr_register_resources()
		{
		    /* The main leaflet resources */
		    wp_register_script('leaflet-base-js', '//unpkg.com/leaflet@1.0.3/dist/leaflet.js', null, '1.0.3');
		    wp_register_style('leaflet-base-css', '//unpkg.com/leaflet@1.0.3/dist/leaflet.css', null, '1.0.3');

		    /* Leaflet omnivore */
		    wp_register_script('leaflet-omnivore', '//api.tiles.mapbox.com/mapbox.js/plugins/leaflet-omnivore/v0.3.1/leaflet-omnivore.min.js', array('leaflet-base-js'), '0.3.1');

		    /* Helper JS file to delay initializing the map until its ready */
		    wp_register_script('lhgr_leaflet_helper', plugins_url('initializeMap.js', __FILE__), array('leaflet-base-js', 'leaflet-omnivore'));
		}
		add_action( 'wp_enqueue_scripts', 'lhgr_register_resources');
		add_action( 'admin_enqueue_scripts', 'lhgr_register_resources');
		
		// Activation hooks
		register_activation_hook( __FILE__, 'lhgr_plugin_activation' );

		// Deactivation hooks
		register_deactivation_hook( __FILE__, 'inreach_deactivate' );
