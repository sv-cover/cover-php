<?php
require_once 'include/models/DataModelVacancy.php';

class VacanciesView extends CRUDView
{
	private $_partners;

	public function type_options()
	{
		return [
			DataModelVacancy::TYPE_FULL_TIME => __('Fulltime'),
			DataModelVacancy::TYPE_PART_TIME => __('Part-time'),
			DataModelVacancy::TYPE_INTERNSHIP => __('Internship'),
			DataModelVacancy::TYPE_GRADUATION_PROJECT => __('Graduation project'),
			DataModelVacancy::TYPE_OTHER => __('Other/unknown'),
		];
	}

	public function study_phase_options()
	{
		return [
			DataModelVacancy::STUDY_PHASE_BSC => __('Bachelor Student'),
			DataModelVacancy::STUDY_PHASE_MSC => __('Master Student'),
			DataModelVacancy::STUDY_PHASE_BSC_GRADUATED => __('Graduated Bachelor'),
			DataModelVacancy::STUDY_PHASE_MSC_GRADUATED => __('Graduated Master'),
			DataModelVacancy::STUDY_PHASE_OTHER => __('Other/unknown'),
		];
	}

	public function partners()
	{
		if (!isset($this->_partners))
			$this->_partners = get_model('DataModelVacancy')->partners();
		return $this->_partners;
	}

	public function render_index($iters)
	{
		$filter = array_intersect_key($_GET, array_flip(get_model('DataModelVacancy')::FILTER_FIELDS));
		return $this->render('index.twig', compact('iters', 'filter'));
	}

	public function get_filter_tags($filter)
	{
		$tags = [];

		foreach ($filter as $field => $values) {
			if (!is_array($values))
				$values = [$values];

			foreach ($values as $val) {
				$tag = [];
				if ($field === 'partner') {
					$partner = array_find($this->partners(), function ($item) use ($val) { return $item['id'] == $val; });
					if (empty($partner)) {
						$tag['name'] = $val;
						$tag['for'] = sprintf('field-partner-%s', $val);
					} else {
						$tag['name'] = $partner['name'];
						$tag['for'] = sprintf('field-partner-%s', $partner['id']);
					}
				} elseif ($field === 'type') {
					$tag['name'] = $this->type_options()[$val];
					$tag['for'] = sprintf('field-type-%s', $val);
				} elseif ($field === 'study_phase') {
					$tag['name'] = $this->study_phase_options()[$val];
					$tag['for'] = sprintf('field-study_phase-%s', $val);
				}

				if (!empty($tag))
					$tags[] = $tag;
			}
		}

		return $tags;
	}
}
