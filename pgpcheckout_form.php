<?php
	function pgpcheckout_form( $atts ) {
		global $wpdb;
		extract( shortcode_atts( array(
			'foo' => 'something',
			'bar' => 'something else',
		), $atts ) );
		
		$id_transaction = "";
		if(isset($_REQUEST["pgpcheckout_id_transaction"])) {
			$id_transaction = $_REQUEST["pgpcheckout_id_transaction"];
		}
		$key_pair = new Crypt_RSA_KeyPair(1024);
		$public_key = $key_pair->getPublicKey();
		$private_key = $key_pair->getPrivateKey();

		$rsa_obj = new Crypt_RSA;
		$enc_data = $rsa_obj->encrypt("aaaaa", $public_key);
		$dec_data = $rsa_obj->decrypt($enc_data, $private_key);

		echo("KEEEEEEY: " . $public_key->toString() . ";<br>");
		echo("coded: " . $enc_data . ";<br>");
		echo("decoded: " . $dec_data . ";<br>");

		$html = "";
		if(isset($_POST["pgpcheckout_posted"]) && $_POST["pgpcheckout_posted"] == "true") {
			$private_data = "";
			$public_data = "";
			foreach($_POST as $key=>$value) {
				if(strpos($key,"pgpcheckout_private") === 0) {
					$private_data .= $key . ": " . $value . "\n";
				}
				if(strpos($key,"pgpcheckout_public") === 0) {
					$public_data .= $key . ": " . $value . "\n";
				}
			};
			
			$data = array(
				'id_transaction' => $id_transaction, 
				'private_data' => $rsa_obj->encrypt($private_data, $public_key),
				'public_data' => $public_data
			);
			$wpdb->insert( $wpdb->prefix . "pgpcheckout_transactions", (array) $data );
			
			$html = __("Gracias por enviar la informacion.");	
		} else {
			$html = "
				<form class=\"pgpcheckout-form\" method=\"POST\">
					<input type=\"hidden\" name=\"pgpcheckout_posted\" value=\"true\" />
					<input type=\"hidden\" name=\"pgpcheckout_id_transaction\" value=\"" . $id_transaction . "\" />
					<label class=\"pgpcheckout-label\">" . __('Nombre:') . "</label><input type=\"text\"  name=\"pgpcheckout_public_cc_name\" class=\"pgpcheckout-input\" />
					<label class=\"pgpcheckout-label\">" . __('Numero:') . "</label><input type=\"text\"  name=\"pgpcheckout_private_cc_number\" class=\"pgpcheckout-input\" />
					<label class=\"pgpcheckout-label\">" . __('Expiracion:') . "</label><input type=\"text\"  name=\"pgpcheckout_private_cc_expires\" class=\"pgpcheckout-input\" />
					<label class=\"pgpcheckout-label\">" . __('Codigo de seguridad:') . "</label><input type=\"text\"  name=\"pgpcheckout_private_cc_security_code\" class=\"pgpcheckout-input\" />
					<input type=\"submit\" value=\"" . __('Enviar') . "\" />
				</form>
			";					
		}
		
		return $html;
	}
	add_shortcode( 'pgpcheckout_form', 'pgpcheckout_form' );
?>
