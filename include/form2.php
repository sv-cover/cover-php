<?php

namespace cover\form;

interface hasHTML
{
	public function toHTML(): string;
}

class Element implements hasHTML
{
	public function __construct($name, array $attributes = [], array $children = null)
	{
		$this->name = $name;

		$this->attributes = $attributes;

		$this->children = $children;
	}

	public function toHTML()
	{
		return $this->children === null
			? sprintf('<%s%s>',
				$this->name,
				$this->attributesToString())
			: sprintf('<%s%s>%s<%1$s>',
				$this->name,
				$this->attributesToString(),
				$this->childrenToString());
	}

	protected function attributesToHTML()
	{
		$string = '';

		foreach ($this->attributes as $name => $value)
			$string .= sprintf(' %s="%s"', $name, $this->escapeAttribute($value));

		return $string;
	}

	protected function childrenToHTML()
	{
		$string = '';

		foreach ($this->children as $child) {
			if ($child instanceof hasHTML)
				$string .= $child->toHTML();
			else
				$string .= $this->escapeHTML($child);
		}

		return $string;
	}

	protected function escapeHTML($child)
	{
		return htmlspecialchars($child);
	}

	protected function escapeAttribute($value)
	{
		return htmlspecialchars($value, ENT_QUOTES);
	}
}

class Form implements \ArrayAccess, \Countable
{
	public function add(Field $field)
	{
		$this->fields[$field->name()] = $field;
	}

	public function __offsetExists($name)
	{
		return isset($this->fields[$name]);
	}

	public function __offsetGet($name)
	{
		return $this->fields[$name]->value();
	}

	public function count()
	{
		return count($this->fields);
	}
}

abstract class Field
{
	private $name;

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function name()
	{
		return $this->name;
	}

	abstract public function value();

	abstract public function isValid();

	abstract public function toHTML();
}

trait HasLabel
{
	private $label;

	public function label()
	{
		return $this->label;
	}

	public function setLabel(string $label)
	{
		$this->label = $label;
	}
}

class Validity
{
	private $valid;

	protected $messages;

	public function __construct(boolean $valid, array $messages = [])
	{
		$this->valid = $valid;

		$this->messages = $messages;
	}

	public function isValid(): boolean
	{
		return $this->valid;
	}

	public function messages(): array
	{
		return $this->messages;
	}

	static public function all(array $validities): Validity
	{
		return array_reduce($validities, Validity::combine);
	}

	static public function combine(Validity $left, Validity $right): Validity
	{
		return new Validity(
			$left->isValid() && $right->isValid(),
			array_merge($left->messages(), $right->messages())
		);
	}
}

trait CanValidate
{
	private $validators = [];

	public function addValidator(Validator $validator)
	{
		$this->validators[] = $validator;
	}

	public function validate($value): Validity
	{
		$verdicts = [];

		foreach ($this->validators as $validator)
			$verdicts[] = $validator->validate($value);

		return Validity::all($verdicts);
	}

	public function isValid()
	{
		return $this->validate($this->value())->isValid();
	}
}

trait HasDefaultValue
{
	private $defaultValue = null;

	public function setDefaultValue($value)
	{
		$this->defaultValue = $value;
	}

	public function defaultValue()
	{
		return $this->defaultValue;
	}

	public function isDefaultValue()
	{
		return $this->value() == $this->defaultValue();
	}
}

class TextField extends Field
{
	use HasLabel, HasDefaultValue, CanValidate, CanRender;

	public function __construct(string $name)
	{
		parent::__construct($name);
	}

	public function value()
	{
		return isset($_POST[$this->name])
			? $_POST[$this->name]
			: $this->defaultValue();
	}

	public function toHTML()
	{
		return (new Element('input', [
			'type' => 'text',
			'name' => $this->name(),
			'value' => $this->value(),
			'class' => !$this->isDefaultValue() && $this->isValid() ? 'valid' : 'invalid'
		]))->toHTML();
	}
}