<?php

require_once 'include/controllers/ControllerEditable.php';

class EditableTwigExtension extends Twig_Extension
{
	public function getName()
	{
		return 'editable';
	}

	public function getFunctions()
	{
		return [
			new Twig_SimpleFunction('create_editable', 'EditableTwigExtension::create_editable', ['is_safe' => ['html']])
		];
	}

	static public function create_editable($name) {
		return new ControllerEditable($name);
	}
}