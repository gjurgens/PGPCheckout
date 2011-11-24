<?php
global $pgpcheckout_db_version;
$pgpcheckout_db_version = "0.1.0";

function pgpcheckout_install() {
	global $wpdb;
	global $pgpcheckout_db_version;
	
	$table_name = $wpdb->prefix . "pgpcheckout_transactions";
	$sql = "CREATE TABLE " . $table_name . " (
		`id` mediumint(9) NOT NULL AUTO_INCREMENT,
		`id_transaction` int(11) NOT NULL,
		`time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`cc_name` varchar(255) NOT NULL,
		`cc_number` varchar(255) NOT NULL,
		`cc_expires` varchar(255) NOT NULL DEFAULT '',
		`cc_security_code` varchar(255) NOT NULL,
		UNIQUE KEY `id` (`id`)
	);";
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	
	add_option("pgpcheckout_db_version", $pgpcheckout_db_version);
}

?>