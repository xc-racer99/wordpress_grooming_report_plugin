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
		$content = '<div id="lhgr_map" style="height: 500px;"></div>';

		// Add the table containing the legend
		$content .= <<<EOT
<table>
<tr>
	<th colspan="2">Legend</th>
</tr>
<tr>
	<td><img src="https://unpkg.com/leaflet@1.0.3/dist/images/marker-icon.png" /></td>
	<td>Points from today's grooming</td>
</tr>
<tr>
	<td style="background-color: green;"></td>
	<td>Groomed today or yesterday</td>
</tr>
<tr>
	<td style="background-color: yellow;"></td>
	<td>Groomed two or three days ago</td>
</tr>
<tr>
	<td style="background-color: red;"></td>
	<td>Groomed four or more days ago</td>
</tr>
</table>
EOT;

		// Get our options for the map setup
		$lat = esc_js(get_option('map_lat'));
		$long = esc_js(get_option('map_lng'));
		$defaultZoom = esc_js(get_option('map_default_zoom'));
		$tiles = esc_js(get_option('map_tiles'));
		$maxZoom = esc_js(get_option('map_max_zoom'));
		$attrib = esc_js(get_option('map_attribute'));

		// Setup our map
		$content .= <<<EOD
<script type="text/javascript">
function initializeMap() {
	/* Layer groups */
	var todaysGrooming = new L.LayerGroup();
	var recentGrooming = new L.LayerGroup();

	/* Define nice names for the layers */
	var overlays = {
		"Today's": todaysGrooming,
		"Recent": recentGrooming,
	};

	var mymap = L.map('lhgr_map', {
		center: [$lat, $long],
		zoom: [$defaultZoom],
		layers: [recentGrooming, todaysGrooming]
	});

	var OpenStreetMap_Mapnik = L.tileLayer('$tiles', {
		maxZoom: $maxZoom,
		attribution: '$attrib'
	}).addTo(mymap);

	var greenOverlay = L.geoJson(null, {
		// http://leafletjs.com/reference.html#geojson-style
		style: function(feature) {
		return { color: 'green', weight: 3, opacity: 1 };
		}
	});

	var yellowOverlay = L.geoJson(null, {
		// http://leafletjs.com/reference.html#geojson-style
		style: function(feature) {
		return { color: 'yellow', weight: 3, opacity: 1 };
		}
	});

	var redOverlay = L.geoJson(null, {
		// http://leafletjs.com/reference.html#geojson-style
		style: function(feature) {
		return { color: 'red', weight: 3, opacity: 1 };
		}
	});
EOD;

		$trails_categories = lhgr_get_all_trail_info(false);

		// Loop through all the categories and add all the trails
		foreach ( $trails_categories as $trails_info) {
			foreach ( $trails_info as $trail ) {
				if ($trail[2]) {
					// We have a GPX track, check the date and add the popup
					$popupData = '<h5>' . esc_html($trail[1]) . '</h5>';

					if (empty($trail[3])) {
						// Never groomed
						$popupData .= '<p>Never Groomed';
					} else {
						$popupData .= '<p>' . esc_html(date( "M j\, Y", strtotime( $trail[3] )));
					}

					if ( !empty($trail[4]) ) {
						$popupData .= '<br />' . esc_html($trail[4]);
					}

					$popupData .= "</p>";

					// Default to a red track
					$overlay = 'redOverlay';

					if ($trail[3] == current_time("Y-m-d") || $trail[3] == date("Y-m-d", strtotime("-1 day", current_time("timestamp")))) {
						$overlay = 'greenOverlay';
					} else if ($trail[3] == date("Y-m-d", strtotime("-2 days", current_time("timestamp"))) || $trail[3] == date("Y-m-d", strtotime("-3 days", current_time("timestamp")))) {
						$overlay = 'yellowOverlay';
					}

                    $overlay = esc_js($overlay);

                    $trail_name = esc_js($trail[2]);
                    
					$content .= <<<EOT

omnivore.gpx("$trail_name", null, $overlay)
.on('ready', function() {
	this.eachLayer(function(layer) {
		layer.bindPopup("$popupData");
	});
})
.addTo(recentGrooming);

EOT;
				}
			}
		}

		// Add the inReach KML feed
		$content .= 'omnivore.kml("https://xc-racer2.duckdns.org/plugins/wp-content/plugins/test/track.kml").addTo(todaysGrooming);';

		$content .= "L.control.layers(null, overlays).addTo(mymap);\n";
		$content .= '}</script>';

		return $content;
	}
	add_shortcode('lhgr_map', 'lhgr_map_shortcode');

	function lhgr_list_shortcode($atts = [], $content = null)
	{

		$table_header = <<<EOT
<table>
	<tr>
		<th>Trail Name</th>
		<th>Last Groomed</th>
		<th>Comment</th>
	</tr>
EOT;

		$trail_categories = lhgr_get_all_trail_info();

		foreach( $trail_categories as $key => $trail_category ) {
			$content .= '<h4>' . esc_html($key) . '</h4>';

			$content .= $table_header;

			// Sort the array alphabetically
			natcasesort($trail_category);

			foreach( $trail_category as $trail ) {
				$content .= '<tr><td>' . esc_html($trail[1]) . '</td>';
				$content .= '<td>' . esc_html($trail[3]) . '</td>';
				$content .= '<td>' . esc_html($trail[4]) . '</td></tr>';
			}

			$content .= '</table>';
		}

		return $content;
	}
	add_shortcode('lhgr_list', 'lhgr_list_shortcode');

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
			$content .= '<tr><th colspan="4">' . esc_html($key) . '</th></tr>';

			// Sort the array alphabetically
			natcasesort($trail_category);

			foreach( $trail_category as $trail ) {
				$content .= '<tr><td>' . esc_html($trail[1]) . '</td>';
				$content .= '<td><input type="checkbox" name="groomed[' . esc_html($trail[0]) . ']" value="groomed" ></td>';
				$content .= '<td><input type="text" name="comment[' . esc_html($trail[0]) . ']"/></td>';
				$content .= '<td>' . esc_html($trail[3]) . '</td>';
				$content .= '<td>' . esc_html($trail[4]) . '</td></tr>';
			}
		}

		$content .= '</table>';

		$content .= '<input type="submit" value="Submit" /></form>';
		return $content;
	}
	add_shortcode('lhgr_groomer_entry', 'lhgr_groomers_entry');
}
add_action('init', 'lhgr_shortcode_init');