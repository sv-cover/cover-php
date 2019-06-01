<?php

function sentry_get_client()
{
	static $client = null;

	if ($client === null)
		$client = get_config_value('sentry_url')
			? new Raven_Client(get_config_value('sentry_url'))
			: false;

	return $client ? $client : null;
}

function sentry_report_exception($e, $attributes = [])
{
	$client = sentry_get_client();

	if ($client === null)
		return null;

	return $client->captureException($e, $attributes);
}

function init_sentry()
{
	$client = sentry_get_client();

	if (!$client)
		return;

	$client->tags_context([
		'locale' => i18n_get_locale()
	]);

	if (get_auth()->logged_in()) {
		$client->user_context([
			'id' => get_identity()->get('id'),
			'email' => get_identity()->get('email')
		]);

		$client->extra_context([
			'session_id' => get_auth()->get_session()->get('id')
		]);
	}

	// We already take care of the exceptions ourselves, and use our
	// own error handler to turn warnings etc. into exceptions.
	// However, some errors cannot be caught that way, so let's fall
	// back on Sentry's client for those.
	$error_handler = new Raven_ErrorHandler($client);
	$error_handler->registerShutdownFunction();
}