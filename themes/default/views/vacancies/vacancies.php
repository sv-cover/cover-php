<?php

require_once 'include/models/DataModelBanner.php';

class VacanciesView extends CRUDView
{
	public function study_year_options() {
		return [
			1 => __('First Year Bachelor'),
			2 => __('Second Year Bachelor'),
			3 => __('Third Year Bachelor'),
			4 => __('First Year Master'),
			5 => __('Second Year Master'),
			6 => __('Graduated Bachelor'),
			6 => __('Graduated Master')
		];
	}

	public function get_study_year($year)
	{
		return $year && isset($this->study_year_options()[$year])
			? $this->study_year_options()[$year]
			:  __('Not specified');
	}

	public function hours_options()
	{
		return [
			1 => __('0-8'),
			2 => __('8-16'),
			3 => __('Parttime'),
			4 => __('Fulltime'),
		];
	}

	public function get_hours($hours)
	{
		return $hours && isset($this->hours_options()[$hours])
			? $this->hours_options()[$hours]
			:  __('Not specified');
	}

	public function type_options()
	{
		return [
			1 => __('Side job'),
			2 => __('Graduation project'),
			3 => __('For graduated students'),
		];
	}

	public function get_type($type)
	{
		return $type && isset($this->type_options()[$type])
			? $this->type_options()[$type]
			:  __('Not specified');
	}

	public function get_logo($company){
		return get_model('DataModelBanner')->get_for_company($company);
	}
}
