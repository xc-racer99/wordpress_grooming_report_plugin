<?php
/*Plugin Name: Grooming Report Plugin
Description: This plugin registers the 'lhgr_trails' post type.
Version: 1.0
License: GPLv2
*/

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

function lhgr_add_meta_boxes()
{
    $screens = ['lhgr_trails'];
    foreach ($screens as $screen) {
        add_meta_box(
            'wporg_box_id',           // Unique ID
            'GPS Track',  // Box title
            'gps_track_html',  // Content callback, must be of type callable
            $screen                   // Post type
        );
    }
}
add_action('add_meta_boxes', 'lhgr_add_meta_boxes');

function gps_track_html($post)
{
// TODO: Create a mini-leaflet map if we already have a file uploaded...
    ?>
    <label for="gpx_upload">Upload GPX Track</label>
    <input name="gpx_upload" id="gpx_upload" type="file" accept="gpx" />
    <?php
}

function lhgr_save_postdata($post_id)
{

//TODO: Do this properly...
    if (array_key_exists('gpx_upload', $_POST)) {
        update_post_meta(
            $post_id,
            'gpx_track_file',
            $_POST['gpx_upload'],
            true
        );
    }
}
add_action('save_post', 'lhgr_save_postdata');
?>
