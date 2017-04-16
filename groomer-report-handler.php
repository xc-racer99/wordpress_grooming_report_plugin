<?php
function add_groomer_entry_data() {
    /**
     * At this point, $_GET/$_POST variable are available
     *
     * We can do our normal processing here
     */

    // Sanitize the POST field


}
add_action( 'admin_post_nopriv_lhgr_groomer_entry', 'add_groomer_entry_data' );
add_action( 'admin_post_lhgr_groomer_entry', 'add_groomer_entry_data' );
