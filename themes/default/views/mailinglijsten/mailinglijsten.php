<?php
require_once 'form.php';
require_once 'markup.php';

class MailinglijstenView extends View
{
	protected $__file = __FILE__;

	public function format_naam($abonnement)
	{
		$naam = $abonnement->get('voornaam');

		if ($abonnement->get('tussenvoegsel'));
			$naam .= ' ' . $abonnement->get('tussenvoegsel');

		return $naam . ' ' . $abonnement->get('achternaam');
	}
}
