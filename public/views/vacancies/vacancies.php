<?php
require_once 'src/models/DataModelVacancy.php';

class VacanciesView extends CRUDFormView
{
	private $_partners;

	public function type_options()
	{
		return [
			DataModelVacancy::TYPE_FULL_TIME => __('Full-time'),
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

	private function get_tag($field, $value)
	{
		// Don't render empty filters, but 0 is not empty
		if (empty($value) && $value !== 0)
			return [];

		$tag = [];
		if ($field === 'partner') {
			$partner = array_find(
				$this->partners(),
				function ($item) use ($value) { return $item['id'] == $value; }
			);
			if (empty($partner)){
				$tag['name'] = $value;
				$tag['for'] = sprintf('field-partner-%s', $value);
			} else {
				$tag['name'] = $partner['name'];
				$tag['for'] = sprintf('field-partner-%s', $partner['id']);
			}
		} elseif ($field === 'type') {
			$tag['name'] = $this->type_options()[$value];
			$tag['for'] = sprintf('field-type-%s', $value);
		} elseif ($field === 'study_phase') {
			$tag['name'] = $this->study_phase_options()[$value];
			$tag['for'] = sprintf('field-study_phase-%s', $value);
		}
		return $tag;
	}

	public function get_filter_tags($filter)
	{
		$tags = [];

		foreach ($filter as $field => $values) {
			if (!is_array($values))
				$values = [$values];

			foreach ($values as $val) {
				try {
					$tag = $this->get_tag($field, $val);
				} catch (Exception $e) {
					continue;
				}

				if (!empty($tag))
					$tags[] = $tag;
			}
		}

		return $tags;
	}
}
