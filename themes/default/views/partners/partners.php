<?php

class PartnersView extends CRUDView
{
    public function type_options()
    {
        return [
            DataModelPartner::TYPE_SPONSOR => __('Sponsor'),
            DataModelPartner::TYPE_MAIN_SPONSOR => __('Main sponsor'),
            DataModelPartner::TYPE_OTHER => __('Other'),
        ];
    }

    // TODO: Find a way to not have to duplicate this implementation
    public function vacancy_type_options()
    {
        return [
            DataModelVacancy::TYPE_FULL_TIME => __('Fulltime'),
            DataModelVacancy::TYPE_PART_TIME => __('Part-time'),
            DataModelVacancy::TYPE_INTERNSHIP => __('Internship'),
            DataModelVacancy::TYPE_GRADUATION_PROJECT => __('Graduation project'),
            DataModelVacancy::TYPE_OTHER => __('Other/unknown'),
        ];
    }

    // TODO: Find a way to not have to duplicate this implementation
    public function vacancy_study_phase_options()
    {
        return [
            DataModelVacancy::STUDY_PHASE_BSC => __('Bachelor Student'),
            DataModelVacancy::STUDY_PHASE_MSC => __('Master Student'),
            DataModelVacancy::STUDY_PHASE_BSC_GRADUATED => __('Graduated Bachelor'),
            DataModelVacancy::STUDY_PHASE_MSC_GRADUATED => __('Graduated Master'),
            DataModelVacancy::STUDY_PHASE_OTHER => __('Other/unknown'),
        ];
    }

}
