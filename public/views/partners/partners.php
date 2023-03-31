<?php

class PartnersView extends CRUDFormView
{
    public function type_options()
    {
        return [
            DataModelPartner::TYPE_SPONSOR => __('Sponsor'),
            DataModelPartner::TYPE_MAIN_SPONSOR => __('Main sponsor'),
            DataModelPartner::TYPE_OTHER => __('Other'),
        ];
    }
}
