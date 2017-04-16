<?php
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
		// Get a list of all "Trails" entries
		$query = new WP_Query(array(
			'post_type' => 'lhgr_trails',
			'post_status' => 'publish',
			'posts_per_page' => -1
		));

		while ($query->have_posts()) {
			$query->the_post();
			$post_id = get_the_ID();

			$gps_track = get_post_meta($post_id, 'gpx_track_url', true);

			if ($gps_track) {
				$content .= 'omnivore.gpx("' . $gps_track . '").addTo(mymap);';
			}
		}
		wp_reset_query();

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
</tr>
EOD;
		$trail_categories = array();

		// Get all the trails and add them to the list
		$query = new WP_Query(array(
			'post_type' => 'lhgr_trails',
			'post_status' => 'publish',
			'posts_per_page' => -1
		));

		while ($query->have_posts()) {
			$query->the_post();
			$post_id = get_the_ID();

			$categories = get_the_category();

			$groomed = get_post_meta($post_id, 'groomer_entry');

			foreach ($categories as $category) {
				$trail_categories[$category->name][] = array(get_the_title(), $post_id, $category->name, $groomed);
			}
		}
		wp_reset_query();

		foreach( $trail_categories as $trail_category) {
			// Sort the array alphabetically
			natcasesort($trail_category);

			$content .= '<tr><th colspan="3">' . $trail_category[0][2] . '</th></tr>';

			foreach( $trail_category as $trail ) {
				$content .= '<tr><td>' . $trail[0] . '</td>';
				// TODO - Make sure we don't have doubled ID values if something is part of multiple categories...
				$content .= '<td><input type="checkbox" name="' . $trail[1] . 'groomed" value="groomed" ></td>';
				$content .= '<td><input type="text" name="' . $trail[1] . 'comment" /></td>';
				// TODO - Determine how $groomed array is formatted, and get the last one that actually has a date...
				$content .= '<td>' . end($trail[3]) . '</td></tr>';
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
