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

	function is_current_member(DataIterMember $iter)
	{
		return get_identity()->get('id') == $iter->get_id();
	}

	function member_write_permission(DataIterMember $iter)
	{
		return $this->is_current_member($iter)
			|| get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY);
	}

	public function get_commissies(DataIterMember $iter)
	{
		$model = get_model('DataModelCommissie');

		return $model->get_commissies_for_member($iter->get_id());
	}

	protected function user_can_override_stuff()
	{
		return get_identity() instanceof ImpersonatingIdentityProvider;
	}

	protected function format_member_data(DataIterMember $iter, $field)
	{
		switch ($field) {
			case 'beginjaar':
				return sprintf('<a href="almanak.php?search_year=%d">%1$d</a>', $iter['beginjaar']);
			case 'adres':
				return sprintf('<a href="%s" target="_blank">%s</a>',
					'https://www.google.nl/maps/search/' . urlencode($iter['adres'] . ' ' . $iter['woonplaats']) . '/',
					markup_format_text($iter['adres']));
			case 'email':
				return sprintf('<a href="mailto:%s">%s</a>',
					urlencode($iter['email']),
					markup_format_text($iter['email']));
			default:
				return markup_format_text($iter[$field]);
		}
	}
}
