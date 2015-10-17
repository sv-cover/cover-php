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
		static $is_current = null;

		if ($is_current !== null)
			return $is_current;

		return $is_current = logged_in('id') == $iter->get('lidid');
	}

	function member_write_permission($iter) {
		static $perms = null;

		if ($perms !== null)
			return $perms;

		$perms = $this->is_current_member($iter)
			|| member_in_commissie(COMMISSIE_BESTUUR)
			|| member_in_commissie(COMMISSIE_KANDIBESTUUR);

		return $perms;
	}

	public function get_commissies($iter)
	{
		$model = get_model('DataModelCommissie');

		return $model->get_commissies_for_member($iter->get('id'));
	}

	protected function user_can_override_stuff()
	{
		return get_identity() instanceof ImpersonatingIdentityProvider;
	}
}
