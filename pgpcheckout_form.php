<?php
	function pgpcheckout_form( $atts ) {
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
			$html = __('Gracias por enviar la informacion.');	
		} else {
			$html = "
				<form class=\"pgpcheckout-form\" method=\"POST\">
					<input type=\"hidden\" name=\"pgpcheckout_posted\" value=\"true\" />
					<input type=\"hidden\" name=\"pgpcheckout_id_transaction\" value=\"" . $id_transaction . "\" />
					<label class=\"pgpcheckout-label\"></label><input type=\"text\"  name=\"pgpcheckout_\" class=\"pgpcheckout-input\" />
					<label class=\"pgpcheckout-label\"></label><input type=\"text\"  name=\"pgpcheckout_\" class=\"pgpcheckout-input\" />
					<label class=\"pgpcheckout-label\"></label><input type=\"text\"  name=\"pgpcheckout_\" class=\"pgpcheckout-input\" />
					<label class=\"pgpcheckout-label\"></label><input type=\"text\"  name=\"pgpcheckout_\" class=\"pgpcheckout-input\" />
					<label class=\"pgpcheckout-label\"></label><input type=\"text\"  name=\"pgpcheckout_\" class=\"pgpcheckout-input\" />
					<label class=\"pgpcheckout-label\"></label><input type=\"text\"  name=\"pgpcheckout_\" class=\"pgpcheckout-input\" />
					<input type=\"submit\" value=\"" . __('Enviar') . "\" />
				</form>
			";					
		}
		
		return $html;
	}
	add_shortcode( 'pgpcheckout_form', 'pgpcheckout_form' );
?>