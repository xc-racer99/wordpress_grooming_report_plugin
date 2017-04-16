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
var mymap = L.map('lhgr_map').setView([51.505, -0.09], 13);
var OpenStreetMap_Mapnik = L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
	maxZoom: 19,
	attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(mymap);
}
</script>
EOD;

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

	// Helper JS file to delay initializing the map until its ready
	wp_register_script('lhgr_leaflet_helper', plugins_url('initializeMap.js', __FILE__), array('leaflet-base-js'));
}
add_action( 'wp_enqueue_scripts', 'lhgr_register_resources');
