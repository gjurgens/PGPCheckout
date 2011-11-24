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
			$data = array(
				'id_transaction' => $id_transaction, 
				'cc_name' => $rsa_obj->encrypt($_POST["pgpcheckout_cc_name"], $public_key),
				'cc_number' => $rsa_obj->encrypt($_POST["pgpcheckout_cc_number"], $public_key),
				'cc_expires' => $rsa_obj->encrypt($_POST["pgpcheckout_cc_expires"], $public_key),
				'cc_security_code' => $rsa_obj->encrypt($_POST["pgpcheckout_cc_security_code"], $public_key)
			);
			$wpdb->insert( $wpdb->prefix . "pgpcheckout_transactions", (array) $data );
			
			$html = __('Gracias por enviar la informacion.');	
		} else {
			$html = "
				<form class=\"pgpcheckout-form\" method=\"POST\">
					<input type=\"hidden\" name=\"pgpcheckout_posted\" value=\"true\" />
					<input type=\"hidden\" name=\"pgpcheckout_id_transaction\" value=\"" . $id_transaction . "\" />
					<label class=\"pgpcheckout-label\">" . __('Nombre:') . "</label><input type=\"text\"  name=\"pgpcheckout_cc_name\" class=\"pgpcheckout-input\" />
					<label class=\"pgpcheckout-label\">" . __('Numero:') . "</label><input type=\"text\"  name=\"pgpcheckout_cc_number\" class=\"pgpcheckout-input\" />
					<label class=\"pgpcheckout-label\">" . __('Expiracion:') . "</label><input type=\"text\"  name=\"pgpcheckout_cc_expires\" class=\"pgpcheckout-input\" />
					<label class=\"pgpcheckout-label\">" . __('Codigo de seguridad:') . "</label><input type=\"text\"  name=\"pgpcheckout_cc_security_code\" class=\"pgpcheckout-input\" />
					<input type=\"submit\" value=\"" . __('Enviar') . "\" />
				</form>
			";					
		}
		
		return $html;
	}
	add_shortcode( 'pgpcheckout_form', 'pgpcheckout_form' );
?>
