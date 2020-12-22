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
				'validate' => ['not_empty']
			],
			'description' => [
				'required' => true
			],
			'type' => [
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
				]
			],
			'study_phase' => [
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
		$iter['last_modified'] = new DateTime();

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
