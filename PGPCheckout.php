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

function pgpcheckout_manage() {
	include('pgpcheckout_manage.php');
}

function pgpcheckout_admin_actions() {
	add_options_page("PGPCheckout", "PGPCheckout", "activate_plugins", "PGPCheckout", "pgpcheckout_admin");
	add_submenu_page('tools.php', __('PGPCheckout'), __('PGPCheckout'), 'activate_plugins', 'pgpcheckout_manage', 'pgpcheckout_manage');
}

function pgpcheckout_install_hook() {
	include('pgpcheckout_install.php');
	pgpcheckout_install();
}

function pgpcheckout_form_hook() {
	include('lib/tinypgp.php');
	include('pgpcheckout_form.php');
}

function pgpcheckout_admin_init() {
    if (!session_id()) {
        session_start();
		//session_destroy();
    }
}


add_action('admin_init', 'pgpcheckout_admin_init');
add_action('admin_menu', 'pgpcheckout_admin_actions');
add_action('init', 'pgpcheckout_form_hook');

register_activation_hook(WP_PLUGIN_DIR . '/PGPCheckout/PGPCheckout.php','pgpcheckout_install_hook');
?>