<?php
require_once('form.php');

function empty_to_http_formatter($value) {
	if (!$value)
		return 'http://';
	else
		return $value;
}


class ProfielView extends View {
		protected $__file = __FILE__;

		function is_current_member($lidid) {
			static $is_current = null;

			if ($is_current !== null)
				return $is_current;

			$member_data = logged_in();
			$is_current = ($member_data && $member_data['id'] == $lidid);

			return $is_current;
		}

		function member_write_permission($lidid) {
			static $perms = null;

			if ($perms !== null)
				return $perms;

			$perms = ($this->is_current_member($lidid) || member_in_commissie(COMMISSIE_BESTUUR));

			return $perms;
		}

		function privacy_parse($model, $iter, $label, $name, $data, $errors, $read_only = false) {
			/* If the currently logged in member does not have write access 
			 * and the field is private, then return ''
			 */
			if (!$this->member_write_permission($iter->get('lidid')) && $model->is_private($iter, $name))
				return '';

			/* Setup the label */
			$result = '<tr><td>' . label($label, $name, $errors) . ':</td><td>';

			/* Show an input text field when there is write permission and
			 * there is data */
			if (member_in_commissie(COMMISSIE_BESTUUR) || ($this->member_write_permission($iter->get('lidid')) && !$read_only))
				$result .= input_text($name, $data);
			else /* Show the field otherwise */
				$result .= $data[$name];

			if ($name == 'adres' && !$model->is_private($iter, 'woonplaats') && !$model->is_private($iter, 'postcode')) {
				$provincie = '';
				if (strtolower($data['woonplaats']) == 'groningen')
					$provincie = 'Groningen';

				$result .= ' <a href="http://maps.google.nl/maps?f=q&hl=nl&q=' . urlencode($data['adres'] . ', ' . $provincie . ' ' . $data['postcode'] . ' ' . $data['woonplaats']) . '&ie=UTF8&z=15&om=1&iwloc=addr">' . __('opzoeken') . '</a>';

			}

			return $result . '</td></tr>';
		}


		function get_privacy_name($model, $iter) {
			if ($model->is_private($iter, 'naam') && !$this->member_write_permission($iter->get('id')))
				return __('Onbekend');
			else
				return $iter->get('nick') ? $iter->get('nick') : member_full_name($iter);
		}

}
?>
