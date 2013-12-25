<?php
	function view_auth_common() {
		echo '<div class="messageBox error_message">' . 
	sprintf(__('Dit deel van de website is alleen toegankelijk voor Cover-leden. Vul links je E-Mail en wachtwoord in te loggen. Indien je je wachtwoord vergeten bent kun je een nieuw wachtwoord %s. Heb je problemen met inloggen, mail dan naar %s.'), '<a href="wachtwoordvergeten.php">' . __('aanvragen') . '</a>', '<a href="mailto:webcie@ai.rug.nl">' . __('de WebCie') . '</a>') . '</div>';
	}
?>
