<?php 
	function pgpcheckoutDisplayTransactions($message = null) {
			//Normal page
			if(!is_null($message)){	
				?>
				<div class="updated"><p><strong><?php echo $message; ?></strong></p></div>
				<?php
			}		
			
			global $wpdb;
			$table_name = $wpdb->prefix . "pgpcheckout_transactions";
			$transactions = $wpdb->get_results( 
				"
				SELECT 
					id,
					id_transaction,
					id_product,
					status,
					time,
					private_data,
					public_data		
				FROM $table_name
				"
			);
			
			?>
			<script type="text/javascript">
				function pgpcheckoutProcess(id) {
					document.getElementById("pgpcheckout_transaction_id").value = id;
					document.getElementById("pgpcheckout_manage_process_form").submit();
				}
			</script>
			<form name="pgpcheckout_manage_process_form" id="pgpcheckout_manage_process_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
				<?php wp_nonce_field( 'pgpcheckout_manage','pgpcheckout_manage_nonce' ); ?>
				<input type="hidden" name="pgpcheckout_action" value="process" id="pgpcheckout_action">
				<input type="hidden" name="pgpcheckout_transaction_id" value="-1" id="pgpcheckout_transaction_id">	
			</form>
			
			<div class='wrap'>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php echo __("Id") ?></th>
							<th><?php echo __("Transaction") ?></th>
							<th><?php echo __("Product") ?></th>
							<th><?php echo __("Status") ?></th>
							<th><?php echo __("Date") ?></th>
							<th><?php echo __("Private") ?></th>
							<th><?php echo __("Public") ?></th>
							<th><?php echo __("Action") ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th><?php echo __("Id") ?></th>
							<th><?php echo __("Transaction") ?></th>
							<th><?php echo __("Product") ?></th>
							<th><?php echo __("Status") ?></th>
							<th><?php echo __("Date") ?></th>
							<th><?php echo __("Private") ?></th>
							<th><?php echo __("Public") ?></th>
							<th><?php echo __("Action") ?></th>
						</tr>
					</tfoot>
					<tbody>
					<?php 
					foreach ( $transactions as $transaction ) 
					{
					?>
						<tr>
							<td><?php echo $transaction->id ?></td>
							<td><?php echo $transaction->id_transaction ?></td>
							<td><?php echo $transaction->id_product ?></td>
							<td><?php echo $transaction->status ?></td>
							<td><?php echo $transaction->time ?></td>
							<td><?php echo $transaction->id ?></td>
							<td>
								<ul>
								<?php 
									$aPublic = unserialize($transaction->public_data);
									if(is_array($aPublic)) {
										foreach($aPublic as $key=>$value) {
											echo "<li>" . $key . ":" . $value . "</li>";
										};					
									} else {
										echo __("No data");
									}
								?>
								</ul>
							</td>
							<td><a class="button-secondary" href="javascript:pgpcheckoutProcess(<?php echo $transaction->id ?>);" title="<?php echo __("Process") ?>"><?php echo __("Process") ?></a></td>
						</tr>
					<?php
					}
					
					?>
					</tbody>
				</table>
			</div>			
			<?php					
	}

	function pgpcheckoutProcess($id, $action = "display", $message = null) {
			if(!is_null($message)){	
				?>
				<div class="updated"><p><strong><?php echo $message; ?></strong></p></div>
				<?php
			}	


			global $wpdb;
			$table_name = $wpdb->prefix . "pgpcheckout_transactions";
			$transaction = $wpdb->get_row( 
				"
				SELECT 
					id,
					id_transaction,
					id_product,
					status,
					time,
					private_data,
					public_data		
				FROM $table_name
				WHERE id = $id
				"
			);

			?>
			<div class="wrap">
				<?php 
				if(!isset($_SESSION["PGPCHECKOUT_PRIVATE_KEY"])) {
				?>
				<form name="pgpcheckout_manage_process_privkey_form" id="pgpcheckout_manage_process_privkey_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
					<?php wp_nonce_field( 'pgpcheckout_manage','pgpcheckout_manage_nonce' ); ?>
					<input type="hidden" name="pgpcheckout_action" value="process_send_key" id="pgpcheckout_key_action">
					<input type="hidden" name="pgpcheckout_transaction_id" value="<?php echo $_POST['pgpcheckout_transaction_id'] ?>" id="pgpcheckout_transaction_id">						
					<p><?php _e("Private Encryption Key: " ); ?>
						<textarea id="pgpcheckout_private_key" name="pgpcheckout_private_key" rows="10" cols="40" ></textarea>
						<input class="button-primary" type="submit" name="send_key" value="<?php _e("Send Private Key"); ?>" id="submitbutton" />
					</p>
				</form>
				<?php
				}
				?>
				<form name="pgpcheckout_manage_process_form" id="pgpcheckout_manage_process_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
					<?php wp_nonce_field( 'pgpcheckout_manage','pgpcheckout_manage_nonce' ); ?>
					<input type="hidden" name="pgpcheckout_action" value="process_save" id="pgpcheckout_action">
					<input type="hidden" name="pgpcheckout_transaction_id" value="<?php echo $id ?>" id="pgpcheckout_transaction_id">	
					<ul>
						<li>Id: <?php echo $transaction->id ?></li>
						<li>id_transaction: <?php echo $transaction->id_transaction ?></li>
						<li>id_product: <?php echo $transaction->id_product ?></li>
						<li>status: <?php echo $transaction->status ?></li>
						<li>time: <?php echo $transaction->time ?></li>
						<?php 
							$aPublic = unserialize($transaction->public_data);
							if(is_array($aPublic)) {
								foreach($aPublic as $key=>$value) {
									echo "<li>" . $key . ": " . $value . "</li>";
								};					
							} else {
								echo "<li>" . __("No data") . "</li>";
							}
						?>
						<?php 
							$private_data = "";
							if(isset($_SESSION["PGPCHECKOUT_PRIVATE_KEY"])) {
								$private_key = Crypt_RSA_Key::fromString($_SESSION["PGPCHECKOUT_PRIVATE_KEY"]);
								if(!Crypt_RSA_Key::isValid($private_key)) die("Invali public Key");
								$rsa_obj = new Crypt_RSA;

								$private_data = $rsa_obj->decrypt($transaction->private_data, $private_key);
							}

				
							
							
							$aPrivate = unserialize($private_data);
							if(is_array($aPrivate)) {
								foreach($aPrivate as $key=>$value) {
									echo "<li>" . $key . ": " . $value . "</li>";
								};					
							} else {
								echo "<li>" . __("No data") . "</li>";
							}
						?>
					</ul>
				</form>
			</div>
			<?php
	}

	if(isset($_POST['pgpcheckout_action']) && check_admin_referer( 'pgpcheckout_manage', 'pgpcheckout_manage_nonce' )) {
		if($_POST['pgpcheckout_action'] == 'process' && isset($_POST['pgpcheckout_transaction_id']) && $_POST['pgpcheckout_transaction_id'] != -1) {
			//Form data sent
			pgpcheckoutProcess($_POST['pgpcheckout_transaction_id']);
		}
		else if($_POST['pgpcheckout_action'] == 'process_send_key') {
			//Form data sent
			?>
			<div class="updated"><p><strong><?php _e('Key'); ?></strong></p></div>
			<?php
			if(isset($_POST["pgpcheckout_private_key"])) {
				$_SESSION["PGPCHECKOUT_PRIVATE_KEY"] = $_POST["pgpcheckout_private_key"];
			}
			pgpcheckoutProcess($_POST['pgpcheckout_transaction_id']);
		} else {
			?>
			<div class="updated"><p><strong><?php _e('ERROR' ); ?></strong></p></div>
			<?php
		}
	} else {
		pgpcheckoutDisplayTransactions();
	}
?>
