<?php
	function pgpcheckout_form( $atts ) {
		global $wpdb;
		extract( shortcode_atts( array(
			'id_transaction' => 0,
			'id_product' => 0,
			'public_fields' => '{}',
			'private_fields' => '{}'
		), $atts ) );

		$oPublicFields = json_decode($public_fields);
		$oPrivateFields = json_decode($private_fields);

		//var_dump($oPublicFields);

		if(isset($_REQUEST["pgpcheckout_id_transaction"])) {
			$id_transaction = $_REQUEST["pgpcheckout_id_transaction"];
		}
		if(isset($_REQUEST["pgpcheckout_id_product"])) {
			$id_product = $_REQUEST["pgpcheckout_id_product"];
		}
		
		$public_key = Crypt_RSA_Key::fromString(get_option('pgpcheckout_public_key'));
		if(!Crypt_RSA_Key::isValid($public_key)) die("Invali public Key");
		$rsa_obj = new Crypt_RSA;

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
					<ul>
			";
			
			foreach($oPublicFields as $pubKey=>$pubConfig) {
				$html .= "<li><label class=\"pgpcheckout-label\">" . $pubConfig->display . "</label><input type=\"" . $pubConfig->type . "\"  name=\"pgpcheckout_public_" . $pubKey . "\" class=\"" . $pubConfig->class . "\" /></li>";
			};
			foreach($oPrivateFields as $privKey=>$privConfig) {
				$html .= "<li><label class=\"pgpcheckout-label\">" . $privConfig->display . "</label><input type=\"" . $privConfig->type . "\"  name=\"pgpcheckout_private_" . $privKey . "\" class=\"" . $privConfig->class . "\" /></li>";
			};
			 			
			
			$html .= "
					</ul>
					<input type=\"submit\" value=\"" . __('Enviar') . "\" />
				</form>			
			";				
		}
		
		return $html;
	}
	add_shortcode( 'pgpcheckout_form', 'pgpcheckout_form' );
?>
