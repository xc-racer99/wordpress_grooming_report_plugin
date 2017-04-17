<?php
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
	foreach( $_POST['groomed'] as $entry ) {
		if ($entry == 'groomed') {
			$trail_id = key($_POST['groomed']);
			add_post_meta($trail_id, 'groomer_entry', array($date, $_POST['comment'][$trail_id]));

			// Remove the comment field so we don't add another entry
			unset($_POST['comment'][$trail_id]);
		}
	}

	// Now deal with any remaining comments that don't have a date, ie weren't groomed
	foreach( $_POST['comment'] as $comment ) {
		if ( !empty($comment) ) {
			$trail_id = key($_POST['comment']);
			add_post_meta($trail_id, 'groomer_entry', array( '', $comment));
		}
	}

	echo var_dump( $_POST );
}
add_action( 'admin_post_nopriv_lhgr_groomer_entry', 'add_groomer_entry_data' );
add_action( 'admin_post_lhgr_groomer_entry', 'add_groomer_entry_data' );
