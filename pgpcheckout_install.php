<?php
global $pgpcheckout_db_version;
$pgpcheckout_db_version = "0.1.0";

function pgpcheckout_install() {
	global $wpdb;
	global $pgpcheckout_db_version;
	
	$table_name = $wpdb->prefix . "pgpcheckout_transactions";
	$sql = "CREATE TABLE " . $table_name . " (
		`id` bigint(12) NOT NULL AUTO_INCREMENT,
		`id_transaction` bigint(12) NOT NULL,
		`status` tinyint(2) NOT NULL DEFAULT 0,
		`time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`private_data` text(10240) NOT NULL,
		`public_data` text(10240) NOT NULL,
		UNIQUE KEY `id` (`id`)
	);";
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	
	add_option("pgpcheckout_db_version", $pgpcheckout_db_version);
}

?>