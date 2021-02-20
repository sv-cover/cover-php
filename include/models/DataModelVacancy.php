<?php
require_once 'include/search.php';
require_once 'include/data/DataModel.php';
require_once 'include/models/DataModelCommissie.php';

class DataIterVacancy extends DataIter implements SearchResult
{
	static public function fields()
	{
		return [
		    'title',
		    'description',
		    'type',
		    'study_phase',
		    'url',
		    'partner_id',
		    'partner_name',
		    'created_on',
		    'updated_on',
		];
	}

	static public function rules()
	{
		return [
			'title' => [
				'required' => true,
				'validate' => ['not_empty'],
			],
			'description' => [
				'required' => true,
			],
			'type' => [
				'required' => true,
				'clean' => 'intval',
				'validate' => [
					'not_empty',
					function($type) {
						return in_array($type, [
							DataModelVacancy::TYPE_FULL_TIME,
							DataModelVacancy::TYPE_PART_TIME,
							DataModelVacancy::TYPE_INTERNSHIP,
							DataModelVacancy::TYPE_GRADUATION_PROJECT,
							DataModelVacancy::TYPE_OTHER,
						]);
					}
				],
			],
			'study_phase' => [
				'required' => true,
				'clean' => 'intval',
				'validate' => [
					'not_empty',
					function($type) {
						return in_array($type, [
							DataModelVacancy::STUDY_PHASE_BSC,
							DataModelVacancy::STUDY_PHASE_MSC,
							DataModelVacancy::STUDY_PHASE_BSC_GRADUATED,
							DataModelVacancy::STUDY_PHASE_MSC_GRADUATED,
							DataModelVacancy::STUDY_PHASE_OTHER,
						]);
					}
				],
			],
			'url' => [
				'clean' => 'clean_empty',
				'validate' => [
					'optional',
					function($url) {
						return filter_var($url, FILTER_VALIDATE_URL) !== FALSE;
					}
				],
			],
			'partner_id' => [
				// partner_id XOR partner_name is required, this is validated at partner_name
				'clean' => function($value) {
					return $value ? intval($value) : null;
				},
				'validate' => [
					'optional',
					function($partner_id) {
						try {
							$partner = get_model('DataModelPartner')->get_iter($partner_id);
						} catch (DataIterNotFoundException $e) {
							return false;
						}

						return true;
					}
				]
			],
			'partner_name' => [
				// OK, this XOR thing is inconvenient. Ideally, we would use the 'clean_empty'
				// cleaner on this field, but that would break validation, which we need for the XOR
				// thing. Without 'clean_empty' an emptystring would end up in the database, but
				// null would be more desirable. Therefore, the DataIter->set() function is
				// overridden to set empty values on partner_name to null. Ideally, this would be
				// fixed with more robust form handling.
				'required' => true,
				'validate' => [
					function($partner_name, $field, $iter, $data) {
						return empty($partner_name) xor !isset($data['partner_id']);
					}
				]
			],
		];
	}

	public function get_search_relevance()
	{
		return 0.5;
	}
	
	public function get_search_type()
	{
		return 'vacancy';
	}

	public function get_absolute_url()
	{
		return sprintf('vacancies.php?view=read&id=%d', $this->get_id());
	}

	public function get_partner()
	{
		if (isset($this->data['partner_id']))
			return get_model('DataModelPartner')->get_iter($this->data['partner_id']);
		return null;
	}

	public function set($field, $value)
	{
		// Fix for limitations of the valication chain.
		if ($field == 'partner_name' && empty($value))
			$value = null;
		return parent::set($field, $value);
	}
}

class DataModelVacancy extends DataModel implements SearchProvider
{
	const TYPE_FULL_TIME = 0;
	const TYPE_PART_TIME = 1;
	const TYPE_INTERNSHIP = 2;
	const TYPE_GRADUATION_PROJECT = 3;
	const TYPE_OTHER = 4;

	const STUDY_PHASE_BSC = 0;
	const STUDY_PHASE_MSC = 1;
	const STUDY_PHASE_BSC_GRADUATED = 2;
	const STUDY_PHASE_MSC_GRADUATED = 3;
	const STUDY_PHASE_OTHER = 4;

	public $dataiter = 'DataIterVacancy';

	public function __construct($db)
	{
		parent::__construct($db, 'vacancies');
	}

	public function update(DataIter $iter)
	{
		$iter['updated_on'] = new DateTime();

		return parent::update($iter);
	}

	protected function _id_string($id, $table = null)
	{
		return sprintf("%s.id = %d", $table !== null ? $table : $this->table, $id);
	}

	public function search($query, $limit = null)
	{
		return [];
	}
}
