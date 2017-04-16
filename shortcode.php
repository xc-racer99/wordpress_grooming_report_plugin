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
