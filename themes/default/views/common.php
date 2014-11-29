<?php
	function view_auth() {
		echo '<h1>' . __('Geen toegang') . '</h1>';
		echo '<div class="messageBox error_message">' . sprintf(__('Dit deel van de website is alleen toegankelijk voor Cover-leden. Vul links je E-Mail en wachtwoord in te loggen. Indien je je wachtwoord vergeten bent kun je een nieuw wachtwoord %s. Heb je problemen met inloggen, mail dan naar %s.'), '<a href="wachtwoordvergeten.php">' . __('aanvragen') . '</a>', '<a href="mailto:webcie@ai.rug.nl">' . __('de WebCie') . '</a>') . '</div>';
	}

	function view_not_found($model, $iter, $params) {
		header('Status: 404 Not Found');
		echo '<h1>' . __('Niet gevonden') . '</h1>';
		echo '<div class="messageBox error_message">';
		echo '<p>' . __('De pagina die je zocht bestaat niet.') . '</p>';
		if (isset($params['details']))
			echo '<p>' . markup_format_text($params['details']) . '</p>';
		echo '</div>';
	}
