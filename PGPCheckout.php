<?php
	/*
	Plugin Name: PGPCheckout
	Plugin URI: http://www.jurgens.com.ar
	Description: Secure credit card storage
	Author: Gabriel Jurgens
	Version: 0.1.0
	Author URI: http://www.jurgens.com.ar
	*/
?>
<?php
function pgpcheckout_admin() {
	include('pgpcheckout_import_admin.php');
}

function pgpcheckout_admin_actions() {
	add_options_page("PGPCheckout", "PGPCheckout", 1, "PGPCheckout", "pgpcheckout_admin");
}

add_action('admin_menu', 'pgpcheckout_admin_actions');
?>