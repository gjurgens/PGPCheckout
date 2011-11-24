<?php 
	if(isset($_POST['pgpcheckout_hidden']) && $_POST['pgpcheckout_hidden'] == 'Y') {
		//Form data sent
		$dbhost = $_POST['pgpcheckout_dbhost'];
		update_option('pgpcheckout_dbhost', $dbhost);
		
		$dbname = $_POST['pgpcheckout_dbname'];
		update_option('pgpcheckout_dbname', $dbname);
		
		$dbuser = $_POST['pgpcheckout_dbuser'];
		update_option('pgpcheckout_dbuser', $dbuser);
		
		$dbpwd = $_POST['pgpcheckout_dbpwd'];
		update_option('pgpcheckout_dbpwd', $dbpwd);

		?>
		<div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
		<?php
	} else {
		//Normal page display
		$dbhost = get_option('pgpcheckout_dbhost');
		$dbname = get_option('pgpcheckout_dbname');
		$dbuser = get_option('pgpcheckout_dbuser');
		$dbpwd = get_option('pgpcheckout_dbpwd');
	}
	
	
?>

<div class="wrap">
<?php    echo "<h2>" . __( 'PGPCheckout Product Display Options', 'pgpcheckout_trdom' ) . "</h2>"; ?>

<form name="pgpcheckout_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<input type="hidden" name="pgpcheckout_hidden" value="Y">
	<?php    echo "<h4>" . __( 'PGPCheckout Database Settings', 'pgpcheckout_trdom' ) . "</h4>"; ?>
	<p><?php _e("Database host: " ); ?><input type="text" name="pgpcheckout_dbhost" value="<?php echo $dbhost; ?>" size="20"></p>
	<p><?php _e("Database name: " ); ?><input type="text" name="pgpcheckout_dbname" value="<?php echo $dbname; ?>" size="20"></p>
	<p><?php _e("Database user: " ); ?><input type="text" name="pgpcheckout_dbuser" value="<?php echo $dbuser; ?>" size="20"></p>
	<p><?php _e("Database password: " ); ?><input type="text" name="pgpcheckout_dbpwd" value="<?php echo $dbpwd; ?>" size="20"></p>
	<p class="submit">
	<input type="submit" name="Submit" value="<?php _e('Update Options', 'pgpcheckout_trdom' ) ?>" />
	</p>
</form>
</div>