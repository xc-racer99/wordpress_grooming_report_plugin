<?php
function lhgr_get_all_trails()
{
	$all_trail_ids = array();

	// Get a list of all "Trails" entries
	$query = new WP_Query(array(
		'post_type' => 'lhgr_trails',
		'post_status' => 'publish',
		'posts_per_page' => -1
	));

	while ($query->have_posts()) {
		$query->the_post();
		$all_trail_ids[] = get_the_ID();
	}
	wp_reset_query();

	return $all_trail_ids;
}

function lhgr_get_all_trail_info($all_categories = true)
{
	$trail_ids = lhgr_get_all_trails();

	$trail_info = array();

	foreach ( $trail_ids as $trail_id ) {
		$categories = get_the_category($trail_id);
		$groomer_entries = get_post_meta($trail_id, 'groomer_entry');
		$gpx_url = get_post_meta($trail_id, 'gpx_track_url', true);
		$title = get_the_title($trail_id);

		// Use the last comment, regardless of if it is empty or not
		$last_comment = end($groomer_entries)[1];

		// Find the last entry that has a date
		$last_date = end($groomer_entries)[0];
		while ( empty($last_date) && !is_null($key = key($groomer_entries)) ) {
			$last_date = prev($groomer_entries)[0];
		}

		if ( $all_categories) {
			// Add the trails to all of the categories they belong to
			foreach ( $categories as $category ) {
				$trail_info[$category->name][] = array($trail_id, $title, $gpx_url, $last_date, $last_comment);
			}
		} else {
			// Only add the trail to the first category it belongs to
			$trail_info[$categories[0]->name][] = array($trail_id, $title, $gpx_url, $last_date, $last_comment);
		}
	}

	return $trail_info;
}

function lhgr_shortcode_init()
{
	function lhgr_map_shortcode($atts = [], $content = null)
	{
		// Enqueue our resources
		wp_enqueue_script('lhgr_leaflet_helper');
		wp_enqueue_style('leaflet-base-css');
		$content = '<div id="lhgr_map" style="height: 400px;"></div>';

		$content .= <<<EOD
<script>
function initializeMap() {
	var mymap = L.map('lhgr_map').setView([50.745711, -119.136533], 13);
	var OpenStreetMap_Mapnik = L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		maxZoom: 19,
		attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
	}).addTo(mymap);
EOD;

		$trails_categories = lhgr_get_all_trail_info(false);

		// Loop through all the categories and add all the trails
		foreach ( $trails_categories as $trails_info) {
			foreach ( $trails_info as $trail ) {
				if ($trail[2]) {
					$content .= 'omnivore.gpx("' . $trail[2] . '").addTo(mymap);';
				}
			}
		}

		$content .= '}</script>';

		return $content;
	}
	add_shortcode('lhgr_map', 'lhgr_map_shortcode');

	function lhgr_groomers_entry($atts = [], $content = null)
	{
		$action = esc_url( admin_url('admin-post.php') );

		$content = <<<EOD
<form action="$action" method="post" accept-charset="UTF-8" autocomplete="off" >
<input type="hidden" name="action" value="lhgr_groomer_entry">
<fieldset>
	<legend>Date of Grooming</legend>
	<input type="radio" name="date" id="today" value="today" checked>
	<label for="today" style="display: inline;" >Today</label><br />
	<input type="radio" name="date" id="yesterday" value="yesterday">
	<label for="yesterday" style="display: inline;" >Yesterday</label><br />
	<input type="radio" name="date" id="two_days_ago" value="two_days_ago">
	<label for="two_days_ago" style="display: inline;" >Two Days Ago</label>
</fieldset>

<table>
<tr>
	<th>Trail Name</th>
	<th>Groomed?</th>
	<th>Comments</th>
	<th>Current Date</th>
	<th>Current Comment</th>
</tr>
EOD;
		$trail_categories = lhgr_get_all_trail_info(false);

		foreach( $trail_categories as $key => $trail_category ) {
			$content .= '<tr><th colspan="4">' . $key . '</th></tr>';

			// Sort the array alphabetically
			natcasesort($trail_category);

			foreach( $trail_category as $trail ) {
				$content .= '<tr><td>' . $trail[1] . '</td>';
				$content .= '<td><input type="checkbox" name="groomed[' . $trail[0] . ']" value="groomed" ></td>';
				$content .= '<td><input type="text" name="comment[' . $trail[0] . ']"/></td>';
				$content .= '<td>' . $trail[3] . '</td>';
				$content .= '<td>' . $trail[4] . '</td></tr>';
			}
		}

		$content .= '</table>';

		$content .= '<input type="submit" value="Submit" /></form>';
		return $content;
	}
	add_shortcode('lhgr_groomer_entry', 'lhgr_groomers_entry');
}
add_action('init', 'lhgr_shortcode_init');

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
