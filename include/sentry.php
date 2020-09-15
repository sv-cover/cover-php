<?php

function sentry_get_client()
{
	static $client = null;

	if ($client === null) {
		if (get_config_value('sentry_url')) {
			Sentry\init(['dsn' => get_config_value('sentry_url')]);
			$client = true;
		} else {
			$client = false;
		}
	}

	return $client ? $client : null;
}

function sentry_report_exception($e, $attributes = [])
{
	if (sentry_get_client())
		return Sentry\captureException($e, $attributes);
	return null;
}

function init_sentry()
{
	if (!sentry_get_client())
		return;

	$client->tags_context([
		'locale' => i18n_get_locale()
	]);

	Sentry\configureScope(function (Sentry\State\Scope $scope): void {
		$scope->setTag('locale', 'i18n_get_locale()');
		if (get_auth()->logged_in()) {
			$scope->setUser([
				'id' => get_identity()->get('id'),
				'email' => get_identity()->get('email')
			]);

			$scope->setExtra('session_id', get_auth()->get_session()->get('id'));
		}
	});
}