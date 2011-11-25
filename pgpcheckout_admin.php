<?php 
	$public_key = "NOT SETED";
	if(isset($_POST['pgpcheckout_action'])) {
		if($_POST['pgpcheckout_action'] == 'save') {
			//Form data sent
			$public_key = $_POST['pgpcheckout_public_key'];
			update_option('pgpcheckout_public_key', $public_key);
			
			?>
			<div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
			<?php			
		}
		if($_POST['pgpcheckout_action'] == 'generate_keypair') {
			$key_pair = new Crypt_RSA_KeyPair(1024);
			$public_key = $key_pair->getPublicKey()->toString();
			$private_key = $key_pair->getPrivateKey()->toString();
		}
	} else {
		//Normal page display
		$public_key = get_option('pgpcheckout_public_key');
	}
	
	
?>

<div class="wrap">
<?php    echo "<h2>" . __( 'PGPCheckout Options', 'pgpcheckout_trdom' ) . "</h2>"; ?>

<form name="pgpcheckout_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<input type="hidden" name="pgpcheckout_action" value="save" id="pgpcheckout_action">
	<?php    echo "<h4>" . __( 'PGPCheckout Settings', 'pgpcheckout_trdom' ) . "</h4>"; ?>
	<p><?php _e("Public Encryption Key: " ); ?>
		<textarea id="pgpcheckout_public_key" name="pgpcheckout_public_key" rows="10" cols="40" ><?php echo $public_key; ?></textarea>
	<p class="submit">
	<?php
	if(isset($_POST['pgpcheckout_action']) && $_POST['pgpcheckout_action'] == 'generate_keypair') {
		?>
			<p><?php _e("Private Encryption Key: " ); ?>
				<textarea id="pgpcheckout_private_key" name="pgpcheckout_private_key" rows="10" cols="40" ><?php echo $private_key; ?></textarea>
			<p class="submit">		
		<?php
	}
	?>
	<input type="submit" name="save" value="<?php _e('Update Options', 'pgpcheckout_trdom' ) ?>" />
	<input type="submit" name="generate_keypair" value="<?php _e('Generate Key Pair', 'pgpcheckout_trdom' ) ?>" onclick="javascript:document.getElementById('pgpcheckout_action').value = 'generate_keypair';" />
	</p>
</form>
</div>