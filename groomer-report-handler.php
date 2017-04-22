<?php
function check_date($date, $post_id) {
	$entries = get_post_meta($post_id, 'groomer_entry');

	foreach ($entries as $entry) {
		if ( $date == $entry[0])
			return $entry;
	}
	return false;
}

function add_groomer_entry_data() {
	/**
	 * At this point, $_GET/$_POST variable are available
	 *
	 * We can do our normal processing here
	 */

	// Sanitize the POST field
	// Generate the correct date to use
	$date = current_time("Y-m-d");
	if ( $_POST[date] == "yesterday" ) {
		$date = date("Y-m-d", strtotime("-1 day", current_time("timestamp")));
	} else if ($_POST[date] == "two_days_ago" ) {
		$date = date("Y-m-d", strtotime("-2 days", current_time("timestamp")));
	}

	// Deal with each of the trails
	foreach( $_POST['groomed'] as $trail_id => $entry ) {
		if ($entry == 'groomed') {
			$prev_entry = check_date($date, $trail_id);
			if ($prev_entry) {
				/* There's already an entry for this date, update that entry instead of creating a new one */
				update_post_meta($trail_id, 'groomer_entry', array($date, sanitize_text_field($_POST['comment'][$trail_id])), $prev_entry);
			} else {
				add_post_meta($trail_id, 'groomer_entry', array($date, sanitize_text_field($_POST['comment'][$trail_id])));
			}

			// Remove the comment field so we don't add another entry
			unset($_POST['comment'][$trail_id]);
		}
	}

	// Now deal with any remaining comments that don't have a date, ie weren't groomed
	foreach( $_POST['comment'] as $trail_id => $comment ) {
		if ( !empty($comment) ) {
			add_post_meta($trail_id, 'groomer_entry', array( '', sanitize_text_field($comment)));
		}
	}

	// Die and give a message saying we succesfully updated, including a link to go back to the previous page, falling back to the homepage
	$url = get_site_url();

	if(isset($_SERVER["HTTP_REFERER"])) {
		$url = $_SERVER["HTTP_REFERER"];
	}

	echo '<meta http-equiv="refresh" content="5; url=' . $url . '">';

	die('<p>Succesfully updated the trails.  Click <a href="' . $url . '">here</a> if you are not automatically redirected after 5 seconds</p>');
}
add_action( 'admin_post_nopriv_lhgr_groomer_entry', 'add_groomer_entry_data' );
add_action( 'admin_post_lhgr_groomer_entry', 'add_groomer_entry_data' );
