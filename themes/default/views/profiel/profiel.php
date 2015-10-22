<?php
require_once 'include/form.php';
require_once 'include/markup.php';
require_once 'include/facebook.php';

function empty_to_http_formatter($value) {
	if (!$value)
		return 'http://';
	else
		return $value;
}


class ProfielView extends View
{
	protected $__file = __FILE__;

	function is_current_member($iter)
	{
		return get_identity()->get('id') == $iter['lidid'];
	}

	function member_write_permission($iter)
	{
		return $this->is_current_member($iter)
			|| get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY);
	}

	public function get_commissies($iter)
	{
		$model = get_model('DataModelCommissie');

		return $model->get_commissies_for_member($iter->get_id());
	}

	protected function user_can_override_stuff()
	{
		return get_identity() instanceof ImpersonatingIdentityProvider;
	}
}
