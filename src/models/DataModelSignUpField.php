<?php

require_once 'src/framework/data/DataModel.php';
require_once 'src/framework/fields.php';

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;

class DataIterSignUpField extends DataIter
{
	static public function fields()
	{
		return [
			'id',
			'form_id',
			'name',
			'type',
			'properties',
			'sort_index',
			'deleted'
		];
	}

	public function get_properties()
	{
		try {
			return json_decode($this->data['properties'], true);
		} catch (Exception $e) {
			return [];
		}
	}

	public function set_properties(array $properties)
	{
		$this->data['properties'] = json_encode($properties);
	}

	public function get_form()
	{
		return get_model('DataModelSignUpForm')->get_iter($this['form_id']);
	}

	public function get_form_data(DataIterSignUpEntry $entry = null)
	{
		return $this->widget()->get_form_data($entry ? $entry->value_for_field($this) : null);
	}

	public function get_type_label()
	{
		return get_model('DataModelSignUpField')->field_types[$this['type']]['label'];
	}

	public function prefill(\DataIterMember $member)
	{
		return $this->widget()->prefill($member);
	}

	public function process(Form $form)
	{
		return $this->widget()->process($form);
	}

	public function build_form(FormBuilderInterface $form_builder)
	{
		return $this->widget()->build_form($form_builder);
	}

	public function get_configuration_form()
	{
		return $this->widget()->get_configuration_form();
	}

	public function process_configuration(Form $form)
	{
		$widget = $this->widget();

		if (!$widget->process_configuration($form))
			return false;
		
		$this['properties'] = $widget->configuration();

		return true;
	}

	public function render_configuration($renderer, array $form_attr)
	{
		return $this->widget()->render_configuration($renderer, $form_attr);
	}

	public function configure($callback)
	{
		$widget = $this->widget();
		$callback($widget);
		$this['properties'] = $widget->configuration();
	}

	public function column_labels()
	{
		return $this->widget()->column_labels();
	}

	public function export(DataIterSignUpEntry $entry = null)
	{
		return $this->widget()->export($entry ? $entry->value_for_field($this) : null);
	}

	private function widget()
	{
		return get_model('DataModelSignUpField')->instantiate($this['type'], $this['name'], $this['properties']);
	}
}

class DataModelSignUpField extends DataModel
{
	public $dataiter = 'DataIterSignUpField';

	public $field_types;

	public function __construct($db)
	{
		parent::__construct($db, 'sign_up_fields');

		$this->field_types = [
			'text' => [
				'class' => \fields\Text::class,
				'label' => __('Text field')
			],
			'checkbox' => [
				'class' => \fields\Checkbox::class,
				'label' => __('Checkbox')
			],
			'choice' => [
				'class' => \fields\Choice::class,
				'label' => __('Multiple choice question')
			],
			'name' => [
				'class' => \fields\Name::class,
				'label' => __('Full name field')
			],
			'address' => [
				'class' => \fields\Address::class,
				'label' => __('Address field')
			],
			'email' => [
				'class' => \fields\Email::class,
				'label' => __('E-mail address field')
			],
			'phone' => [
				'class' => \fields\Phone::class,
				'label' => __('Phone number field')
			],
			'bankaccount' => [
				'class' => \fields\BankAccount::class,
				'label' => __('Bank account (IBAN) field')
			],
			'editable' => [
				'class' => \fields\Editable::class,
				'label' => __('Titles and text (layout)')
			]
		];
	}

	public function update_order(array $fields)
	{
		$values = [];

		foreach (array_values($fields) as $n => $field)
			$values[] = sprintf('(%d, %d)', $field['id'], $n);

		$sql_values = implode(', ', $values);
		
		$this->db->query("
			UPDATE {$this->table} as t 
			SET sort_index = index
			FROM (VALUES $sql_values) as v(id, index)
			WHERE v.id = t.id");
	}

	public function find($conditions)
	{
		if (is_array($conditions) && !isset($conditions['deleted']))
			$conditions['deleted'] = false;

		return parent::find($conditions);
	}

	public function insert(DataIter $iter)
	{
		if ($iter['sort_index'] === null)
			$iter['sort_index'] = $this->_next_sort_index($iter['form_id']);

		return parent::insert($iter);
	}

	private function _next_sort_index($form_id)
	{
		return $this->db->query_value("
			SELECT
				COALESCE(MAX(sort_index) + 1, 0)
			FROM
				{$this->table}
			WHERE
				form_id = :form_id
			", [':form_id' => $form_id]);
	}

	public function delete(DataIter $iter)
	{
		$iter['deleted'] = true;
		$this->update($iter);
	}

	public function restore(DataIter $iter)
	{
		$iter['deleted'] = false;
		$this->update($iter);
	}

	public function instantiate($type, string $name, array $properties)
	{
		$class = new ReflectionClass($this->field_types[$type]['class']);
		return $class->newInstance($name, $properties);
	}

	protected function _generate_query($where)
	{
		return parent::_generate_query($where) . ' ORDER BY sort_index ASC NULLS LAST';
	}
}