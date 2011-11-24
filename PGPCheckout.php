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
	include('pgpcheckout_admin.php');
}

function pgpcheckout_admin_actions() {
	add_options_page("PGPCheckout", "PGPCheckout", 1, "PGPCheckout", "pgpcheckout_admin");
}

function pgpcheckout_install_hook() {
	include('pgpcheckout_install.php');
	pgpcheckout_install();
}

function pgpcheckout_form_hook() {
	include('lib/tinypgp.php');
	include('pgpcheckout_form.php');
}

add_action('admin_menu', 'pgpcheckout_admin_actions');
add_action('init', 'pgpcheckout_form_hook');

register_activation_hook(WP_PLUGIN_DIR . '/PGPCheckout/PGPCheckout.php','pgpcheckout_install_hook');
?>