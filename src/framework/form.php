<?php

use App\Form\Extension\BulmaButtonTypeExtension;
use App\Form\Extension\BulmaCheckboxTypeExtension;
use App\Form\Extension\BulmaChoiceTypeExtension;
use App\Form\Extension\BulmaFileTypeExtension;
use App\Form\Extension\ChipsChoiceTypeExtension;
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
			])
			->getFormFactory();
	}

	return $form_factory;
}


if (!defined('IN_SITE'))
	return;

/** @group Form
  * A check function which checks if a value is a non empty
  * @name the name of the POST value
  * @value reference; the value
  *
  * @result true when value is non empty, false otherwise
  */	
function check_value_empty($name, $value) {
	if (!isset($value) || !trim($value))
		return false;
	else
		return $value;
}

/** @group Form
  * A check function which checks if a value is a valid number. Besides
  * checking this function will also convert the value to a float
  * @name the name of the POST value
  * @value reference; the value
  *
  * @result true when value is a number, false otherwise
  */
function check_value_tofloat($name, $value) {
	if (!is_numeric($value))
		return false;
	else
		return floatval($value);
}

/** @group Form
  * A check function which checks if a value is a valid number. Besides
  * checking this function will also convert the value to an int
  * @name the name of the POST value
  * @value reference; the value
  *
  * @result true when value is a number, false otherwise
  */
function check_value_toint($name, $value) {
	if (!is_numeric($value))
		return false;
	else
		return intval($value);
}

/** @group Form
  * A check function which formats a checkbox value. It sets the
  * POST value to 1 if the value is either 'yes' or 'on' and to 0
  * otherwise
  * @name the name of the POST value
  * @value reference; the value
  *
  * @result always true
  */
function check_value_checkbox($name, $value) {
	if ($value == 'yes' || $value == 'on')
		return 1;
	else
		return 0;
}

/** @group Form
  * A function which checks if POSTed values are valid and optionally
  * formats values
  *
  * @check an array of values to check. Each item in this array
  * is either a a string (the name of a field) or an associative array 
  * containing a 'name' key (the name of a field) and a 'function' 
  * key containing the check function to call. If only a name is
  * specified the default check function (#check_value_empty) will
  * be used. Check functions have two parameters: name and value and 
  * returns either false on error or true when the value is valid. 
  * The value parameter is passed by reference to allow formatting
  * the value. Common check functions available are: 
  * #check_value_toint and #check_value_checkbox
  * @errors reference; will be set to an array of fields that didn't
  * successfully check
  *
  * @result an array with name => value values
  */
function check_values($check, &$errors, array $data = null) {
	$fields = array();
	$errors = array();

	foreach ($check as $item) {
		if (!is_array($item)) {
			$name = $item;
			$func = 'check_value_empty';
		} else {
			$name = $item['name'];

			if (isset($item['function']))
				$func = $item['function'];
			else
				$func = 'check_value_empty';
		}
		
		$result = call_user_func($func, $name, isset($data) ? $data[$name] : get_post($name));
		
		if ($result === false)
			$errors[] = $name;
		else
			$fields[$name] = $result;
	}
	
	return $fields;
}

/**
 * Class that helps collect errors when validating a form.
 */
class ErrorSet implements ArrayAccess, Countable
{
	public $namespace;

	public $errors;

	public function __construct(array $namespace = [], &$errors = null)
	{
		$this->namespace = $namespace;

		if ($errors !== null)
			$this->errors =& $errors;
		else
			$this->errors = [];
	}

	protected function key($field)
	{
		return implode('.', array_merge($this->namespace, [$field]));
	}

	public function namespace($namespace)
	{
		return new ErrorSet(array_merge($this->namespace, [$namespace]), $this->errors);
	}

	public function offsetSet($field, $error)
	{
		if (is_null($field)) // Old append syntax
			$field = $error;

		$this->errors[$this->key($field)] = $error;
	}

	public function offsetGet($field)
	{
		$key = $this->key($field);
		return isset($this->errors[$key])
			? $this->errors[$key]
			: null;
	}

	public function offsetExists($field)
	{
		return isset($this->errors[$this->key($field)]);
	}

	public function offsetUnset($field)
	{
		unset($this->errors[$this->key($field)]);
	}

	public function count()
	{
		$counter = 0;

		foreach ($this->errors as $field => $error)
			if ($this->inNamespace($field))
				$counter++;

		return $counter;
	}

	private function inNamespace($field)
	{
		$ns = implode('.', $this->namespace);
		return substr_compare($field, $ns, 0, strlen($ns)) === 0;
	}
}
