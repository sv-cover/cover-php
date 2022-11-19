<?php

require_once 'src/framework/data/DataModel.php';

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class DataIterSignUpForm extends DataIter
{
	private $_form;

	static public function fields()
	{
		return [
			'id',
			'committee_id',
			'created_on',
			'open_on',
			'closed_on',
			'participant_limit',
			'agenda_id'
		];
	}

	static public function rules()
	{
		return [
			'committee_id' => [
				'validate' => ['committee']
			],
			'created_on' => [
				'default' => function() {
					return new DateTime('now');
				},
				'validate' => ['datetime']
			],
			'open_on' => [
				'clean' => function($value) {
					try {
						return $value ? new DateTime($value) : null;
					} catch (Exception $e) {
						return $value; // the validate will catch the invalid value next
					}
				},
				'validate' => ['datetime']
			],
			'closed_on' => [
				'clean' => function($value) {
					try {
						return $value ? new DateTime($value) : null;
					} catch (Exception $e) {
						return $value; // the validate will catch the invalid value next
					}
				},
				'validate' => ['datetime']
			],
			'participant_limit' => [
				'clean' => function($value) {
					return $value !== '' ? intval($value) : null;
				}
			],
			'agenda_id' => [
				'clean' => function($value) {
					return $value ? intval($value) : null;
				},
				'validate' => [
					function($agenda_id, $field, $iter, $data) {
						// Null is fine
						if ($agenda_id === null)
							return true;

						// But if it is filled in, it has to be an activity organized by the
						// committee that owns this form.
						$committee_id = $data['committee_id'] ?? $iter['committee_id'];

						if ($committee_id === null)
							return false;

						$agenda_item = get_model('DataModelAgenda')->get_iter($agenda_id);

						return $agenda_item['committee_id'] == $committee_id;
					}
				]
			]
		];
	}

	public function get_form(DataIterSignUpEntry $entry, array $defaults = [])
	{
		if (!isset($this->_form))
		{
			if ($entry['member_id'])
				$defaults['member_id'] = $entry['member_id'];
			$data = array_merge($entry->get_form_data(), $defaults);
			$builder = \get_form_factory()
				->createNamedBuilder(sprintf('form-%s', $this['id']), FormType::class, $data);

			foreach ($this->get_fields() as $field)
				$field->build_form($builder);

			$builder
				->add('return_path', HiddenType::class)
				->add('member_id', HiddenType::class)
				->add('submit', SubmitType::class, [
					'label' => __('Sign me up'),
				]);

			$this->_form = $builder->getForm();
		}
		return $this->_form;
	}

	public function get_fields()
	{
		return get_model('DataModelSignUpField')->find(['form_id' => $this['id']]);
	}

	public function get_entries()
	{
		return get_model('DataModelSignUpEntry')->find(['form_id' => $this['id']]);
	}

	public function get_column_labels()
	{
		$headers = [];

		foreach ($this->get_fields() as $field)
			$headers = array_merge($headers, $field->column_labels());

		$headers['signed-up-on'] = 'Signed up on';

		return $headers;
	}

	public function get_agenda_item()
	{
		return $this['agenda_id'] ? get_model('DataModelAgenda')->get_iter($this['agenda_id']) : null;
	}

	public function get_description()
	{
		return sprintf('Sign-up form #%d', $this['id']);
	}

	public function get_signup_count()
	{
		return $this->data['signup_count'] ?? count($this['entries']);
	}

	public function is_open()
	{
		$open_on = is_string($this['open_on']) ? new DateTime($this['open_on']) : $this['open_on'];
		if (!$open_on || $open_on > new DateTime())
			return false;

		$closed_on = is_string($this['closed_on']) ? new DateTime($this['closed_on']) : $this['closed_on'];
		if ($closed_on && $closed_on < new DateTime())
			return false;

		if (!empty($this['participant_limit']) && $this['signup_count'] >= $this['participant_limit'])
			return false;

		return true;
	}

	public function new_entry(DataIterMember $member = null)
	{
		$iter = get_model('DataModelSignUpEntry')->new_iter([
			'form_id' => $this['id'],
			'member_id' => $member ? $member['id'] : null,
			'created_on' => date('Y-m-d H:i:s')
		]);
		if ($member)
			$iter->prefill();
		return $iter;
	}

	public function get_entries_for_member(DataIterMember $member)
	{
		return get_model('DataModelSignUpEntry')->find([
			'form_id' => $this['id'],
			'member_id' => $member['id']
		]);
	}

	public function new_field($type, callable $configure_callback = null)
	{
		$model = get_model('DataModelSignUpField');

		if (!isset($model->field_types[$type]))
			throw new InvalidArgumentException('Unknown form field type');

		$iter = $model->new_iter([
			'form_id' => $this['id'],
			'name' => uniqid(), // for now
			'type' => $type,
			'properties' => '{}'
		]);

		if ($configure_callback)
			$iter->configure($configure_callback);

		return $iter;
	}
}

class DataModelSignUpForm extends DataModel
{
	public $dataiter = 'DataIterSignUpForm';

	public function __construct($db)
	{
		parent::__construct($db, 'sign_up_forms');
	}

	protected function _generate_query($where)
	{
		if (is_array($where))
			$where = $this->_generate_conditions_from_array($where);

		$WHERE = $where ? " WHERE {$where}" : "";

		return "
			SELECT
				{$this->table}.*,
				COUNT(sign_up_entries.id) as signup_count
			FROM
				{$this->table}
			LEFT JOIN sign_up_entries ON
				sign_up_entries.form_id = {$this->table}.id
			{$WHERE}
			GROUP BY
				{$this->table}.id,
				{$this->table}.committee_id,
				{$this->table}.agenda_id,
				{$this->table}.created_on,
				{$this->table}.open_on,
				{$this->table}.closed_on";
	}
}