<?php
add_action( 'get_inreach_feed_hook', 'get_inreach_kml' );

function get_inreach_kml() {
	error_log("Running cron job");

	$base_url = get_option('inreach_link');

	if ($base_url) {
		$url = $base_url . '?d1=' . date("Y-m-d", current_time('timestamp'));
		$path = plugin_dir_path( __FILE__ ) . 'inreachFeed.kml';
		file_put_contents($path, file_get_contents($url));
	}
}

function schedule_inreach_fetch() {
	if ( !wp_next_scheduled( 'get_inreach_feed_hook' ) ) {
		wp_schedule_event( time(), 'five_minutes', 'get_inreach_feed_hook' );
	}
}
add_action( 'init', 'schedule_inreach_fetch');

// Add custom cron interval
add_filter( 'cron_schedules', 'add_custom_cron_intervals', 10, 1 );

function add_custom_cron_intervals( $schedules ) {
	// $schedules stores all recurrence schedules within WordPress
	$schedules['five_minutes'] = array(
		'interval'=> 600,// Number of seconds, 600 in 10 minutes
		'display'=> 'Once Every 5 Minutes'
		);

	// Return our newly added schedule to be merged into the others
	return (array)$schedules;
}