<?php

	function view_invalid_key($model, $iter, $params = null) {
		echo '<h1>' . _('Bevestiging') . ' - ' . _('Fout') . '</h1>
		<p>' . sprintf(_('Je hebt een ongeldige link gevolgd. Er staat geen actie in de wachtrij met de opgegeven sleutel. Probeer het opnieuw of neem contact op met %s als het probleem zich blijft voordoen.'), '<a href="mailto:webcie@ai.rug.nl">' . _('de WebCie') . '</a>') . '</p>';
	}
	
	function view_invalid_confirm($model, $iter, $params = null) {
		echo '<h1>' . _('Bevestiging') . ' - ' . _('Fout') . '</h1>
		<p>' . _('De opgegeven bevestiging kon niet worden uitgevoerd.'). '</p>';
	}
	
	function view_wachtwoord_success($model, $iter, $params = null) {
		echo '<h1>' . _('Wachtwoord veranderd') . '</h1>
			<p>' . sprintf(_('Je wachtwoord is veranderd. Er is een mailtje naar %s gestuurd met je nieuwe inlog-gegevens.'), $params['email']) . '</p>';
	}

?>
