<?php

	function view_invalid_key($model, $iter, $params = null) {
		echo '<h1>' . __('Bevestiging') . ' - ' . __('Fout') . '</h1>
		<p>' . sprintf(__('Je hebt een ongeldige link gevolgd. Er staat geen actie in de wachtrij met de opgegeven sleutel. Probeer het opnieuw of neem contact op met %s als het probleem zich blijft voordoen.'), '<a href="mailto:webcie@ai.rug.nl">' . __('de WebCie') . '</a>') . '</p>';
	}
	
	function view_invalid_confirm($model, $iter, $params = null) {
		echo '<h1>' . __('Bevestiging') . ' - ' . __('Fout') . '</h1>
		<p>' . __('De opgegeven bevestiging kon niet worden uitgevoerd.'). '</p>';
	}
	
	function view_wachtwoord_success($model, $iter, $params = null) {
		echo '<h1>' . __('Wachtwoord veranderd') . '</h1>
			<p>' . sprintf(__('Je wachtwoord is veranderd. Er is een mailtje naar %s gestuurd met je nieuwe inlog-gegevens.'), $params['email']) . '</p>';
	}

	function view_email_success($model, $iter, $params = null) {
		echo '<h1>' . __('E-mailadres veranderd') . '</h1>
			<p>' . sprintf(__('Je e-mailadres is nu veranderd naar %s.'), markup_format_text($params['member']['email'])) . '</p>';
	}
