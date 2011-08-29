<?php
	function pgpcheckout_form( $atts ) {
		global $wpdb;
		extract( shortcode_atts( array(
			'foo' => 'something',
			'bar' => 'something else',
		), $atts ) );
		
		$id_transaction = "";
		if($_REQUEST["pgpcheckout_id_transaction"]) {
			$id_transaction = $_REQUEST["pgpcheckout_id_transaction"];
		}
		
		$html = "";
		if($_POST["pgpcheckout_posted"] == "true") {
			$data = array(
				'id_transaction' => $id_transaction, 
				'cc_name' => $_POST["pgpcheckout_cc_name"],
				'cc_number' => $_POST["pgpcheckout_cc_number"],
				'cc_expires' => $_POST["pgpcheckout_cc_expires"],
				'cc_security_code' => $_POST["pgpcheckout_cc_security_code"]
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