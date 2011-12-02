<?php
	function pgpcheckout_form( $atts ) {
		global $wpdb;
		extract( shortcode_atts( array(
			'id_transaction' => 0,
			'id_product' => 0,
		), $atts ) );


		if(isset($_REQUEST["pgpcheckout_id_transaction"])) {
			$id_transaction = $_REQUEST["pgpcheckout_id_transaction"];
		}
		if(isset($_REQUEST["pgpcheckout_id_product"])) {
			$id_product = $_REQUEST["pgpcheckout_id_product"];
		}
		//$key_pair = new Crypt_RSA_KeyPair(1024);
		//$public_key = $key_pair->getPublicKey();
		//$private_key = $key_pair->getPrivateKey();
		
		
		$public_key = Crypt_RSA_Key::fromString(get_option('pgpcheckout_public_key'));
		if(!Crypt_RSA_Key::isValid($public_key)) die("Invali public Key");
		$rsa_obj = new Crypt_RSA;
		//$enc_data = $rsa_obj->encrypt("aaaaa", $public_key);
		//$dec_data = $rsa_obj->decrypt($enc_data, $private_key);

		//echo("KEEEEEEY: |" . $public_key->toString() . "|;<br>");
		//echo("coded: " . $enc_data . ";<br>");
		//echo("decoded: " . $dec_data . ";<br>");

		$html = "";
		$aPublic = array();
		$aPrivate = array();
		if(isset($_POST["pgpcheckout_posted"]) && $_POST["pgpcheckout_posted"] == "true") {
			$private_data = "";
			$public_data = "";
			foreach($_POST as $key=>$value) {
				if(strpos($key,"pgpcheckout_private") === 0) {
					$aPrivate[$key] = $value;
				}
				if(strpos($key,"pgpcheckout_public") === 0) {
					$aPublic[$key] = $value;
				}
			};
			
			$data = array(
				'id_transaction' => $id_transaction, 
				'id_product' => $id_product, 
				'private_data' => $rsa_obj->encrypt(serialize($aPrivate), $public_key),
				'public_data' => serialize($aPublic)
			);
			$wpdb->insert( $wpdb->prefix . "pgpcheckout_transactions", (array) $data );
			
			$html = __("Gracias por enviar la informacion.");	
		} else {
			$html = "
				<form class=\"pgpcheckout-form\" method=\"POST\">
					<input type=\"hidden\" name=\"pgpcheckout_posted\" value=\"true\" />
					<input type=\"hidden\" name=\"pgpcheckout_id_transaction\" value=\"" . $id_transaction . "\" />
					<input type=\"hidden\" name=\"pgpcheckout_id_product\" value=\"" . $id_product . "\" />
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
