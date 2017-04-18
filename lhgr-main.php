<?php
/*
Plugin Name: Grooming Report Plugin
Description: This plugin registers the 'lhgr_trails' post type.
Version: 1.0
License: GPLv2
*/

// Include our shortcode generation file
require_once( 'shortcode.php' );

// Include our POST handler for the groomer's entry page
require_once( 'groomer-report-handler.php' );

// Include our settings page
require_once( 'settings.php' );

// register custom post type to work with
function lhgr_create_post_type() {
	// set up labels
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
	//register post type
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

// Remove the GPX track file when we permanently remove the trail
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
			'wporg_box_id',		   // Unique ID
			'GPS Track',  // Box title
			'gps_track_html',  // Content callback, must be of type callable
			$screen				   // Post type
		);
	}
}
add_action('add_meta_boxes', 'lhgr_add_meta_boxes');

function gps_track_html($post)
{
// TODO: Create a mini-leaflet map if we already have a file uploaded...
	?>
	<p>Current GPX URL: <?php echo get_post_meta($post->ID, 'gpx_track_url', true); ?></p>
	<p>Current GPX File: <?php echo get_post_meta($post->ID, 'gpx_track_file', true); ?></p>
	<label for="gpx_upload">Upload GPX Track</label>
	<input name="gpx_upload" id="gpx_upload" type="file" />
	<?php
}

function lhgr_save_postdata($post_id, $post)
{
	// Get the post type. Since this function will run for ALL post saves (no matter what post type), we need to know this.
	$post_type = $post->post_type;

	// Logic to handle specific post types
	switch($post_type) {
		// If this is a trail, handle it
		case 'lhgr_trails':
			// Create acceptable MIME types for GPX files
			$mimes = array();
			$mimes['gpx|gpx1'] = 'text/xml';
			$mimes['gpx|gpx2'] = 'application/xml';
			$mimes['gpx|gpx3'] = 'application/gpx';
			$mimes['gpx|gpx4'] = 'application/gpx+xml';

			// If the upload field has a file in it
			if(isset($_FILES['gpx_upload']) && ($_FILES['gpx_upload']['size'] > 0)) {
				// If the uploaded file is the right format
				$arr_file_type = wp_check_filetype(basename($_FILES['gpx_upload']['name']), $mimes);
				$uploaded_file_type = $arr_file_type['ext'];

				// Set an array containing a list of acceptable formats
				$allowed_file_types = array('gpx');

				// If the uploaded file is the right format
				if(in_array($uploaded_file_type, $allowed_file_types)) {
					// Options array for the wp_handle_upload function.
					// FIXME This doesn't work for some reason, but we're already doing a bit of validation up above
//					$upload_overrides = array( 'test_form' => false, 'mimes' => $mimes );
					$upload_overrides = array( 'test_form' => false, 'test_type' => false, 'ext' => $arr_file_type['ext'], 'type' => $arr_file_type['type'] );

					// Store the current uploaded file name so we can delete it
					$old_track = get_post_meta($post_id, 'gpx_track_file', true);

					// Handle the upload using WP's wp_handle_upload function. Takes the posted file and an options array
					$uploaded_file = wp_handle_upload($_FILES['gpx_upload'], $upload_overrides);

					// If the wp_handle_upload call returned a url for the gpx
					if(isset($uploaded_file['url'])) {
						// Update the post meta with the URL of the file
						update_post_meta($post_id, 'gpx_track_url', $uploaded_file['url']);
						update_post_meta($post_id, 'gpx_track_file', $uploaded_file['file']);

						// Remove the old track, if it exists
						if ($old_track) {
							unlink($old_track);
						}
					} else { // wp_handle_upload returned some kind of error.
						  // TODO
					}
				} else { // wrong file type
					// TODO
				}
		   } else {
			   // No file was passed
		   }
		break;

		// Not a trail, ignore
		default:
	} // End switch

	return;
}
add_action('save_post', 'lhgr_save_postdata', 1, 2);

// To upload files, we need to change the form encoding type
function lhgr_add_edit_form_multipart_encoding() {

	echo ' enctype="multipart/form-data"';

}
add_action('post_edit_form_tag', 'lhgr_add_edit_form_multipart_encoding');


// Register leaflet resources
function lhgr_register_resources()
{
	// The main leaflet resources
	wp_register_script('leaflet-base-js', '//unpkg.com/leaflet@1.0.3/dist/leaflet.js', null, '1.0.3');
	wp_register_style('leaflet-base-css', '//unpkg.com/leaflet@1.0.3/dist/leaflet.css', null, '1.0.3');

	// Leaflet omnivore
	wp_register_script('leaflet-omnivore', '//api.tiles.mapbox.com/mapbox.js/plugins/leaflet-omnivore/v0.3.1/leaflet-omnivore.min.js', array('leaflet-base-js'), '0.3.1');

	// Helper JS file to delay initializing the map until its ready
	wp_register_script('lhgr_leaflet_helper', plugins_url('initializeMap.js', __FILE__), array('leaflet-base-js', 'leaflet-omnivore'));
}
add_action( 'wp_enqueue_scripts', 'lhgr_register_resources');
