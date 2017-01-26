<?php

require_once 'include/models/DataModelBanner.php';

class VacanciesView extends CRUDView
{
	public function study_year_options() {
		return [
			1 => __('Eerstejaars Bachelor'),
			2 => __('Tweedejaars Bachelor'),
			3 => __('Derdejaars Bachelor'),
			4 => __('Eerstejaars Master'),
			5 => __('Tweedejaars Master'),
			6 => __('Afgestudeerd')
		];
	}

	public function get_study_year($year)
	{
		return $year && isset($this->study_year_options()[$year])
			? $this->study_year_options()[$year]
			:  __('Niet gespecificeert');
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
			:  __('Niet gespecificeert');
	}

	public function type_options()
	{
		return [
			1 => __('Bijbaan'),
			2 => __('Afstudeerproject'),
			3 => __('Voor afgestudeerden'),
		];
	}

	public function get_type($type)
	{
		return $type && isset($this->type_options()[$type])
			? $this->type_options()[$type]
			:  __('Niet gespecificeert');
	}

	public function get_logo($company){
		return get_model('DataModelBanner')->get_for_company($company);
	}
}
