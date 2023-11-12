<?php

use App\Form\Extension\BulmaButtonTypeExtension;
use App\Form\Extension\BulmaCheckboxTypeExtension;
use App\Form\Extension\BulmaChoiceTypeExtension;
use App\Form\Extension\BulmaFileTypeExtension;
use App\Form\Extension\ChipsChoiceTypeExtension;
use App\Form\Extension\OptionalCheckboxTypeExtension;
use App\Form\Extension\OptionalFormTypeExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\NativeSessionTokenStorage;
use Symfony\Component\Validator\Validation;


function get_csrf_manager()
{
	static $csrf_manager;
	if (!isset($csrf_manager)) {
		// TODO: use SessionTokenStorage after proper HttpFoundation integration
		$csrf_generator = new UriSafeTokenGenerator();
		$csrf_storage = new NativeSessionTokenStorage();
		$csrf_manager = new CsrfTokenManager($csrf_generator, $csrf_storage);
	}

	return $csrf_manager;
}

function get_form_factory()
{
	static $form_factory;

	if (!isset($form_factory)) {
		// creates the validator - details will vary
		$validator = Validation::createValidator();

		$form_factory = Forms::createFormFactoryBuilder()
			->addExtension(new HttpFoundationExtension())
			->addExtension(new ValidatorExtension($validator))
			->addExtension(new CsrfExtension(get_csrf_manager()))
			->addTypeExtensions([
				new BulmaButtonTypeExtension(),
				new BulmaCheckboxTypeExtension(),
				new BulmaChoiceTypeExtension(),
				new BulmaFileTypeExtension(),
				new ChipsChoiceTypeExtension(),
				new OptionalCheckboxTypeExtension(),
				new OptionalFormTypeExtension(),
			])
			->getFormFactory();
	}

	return $form_factory;
}
