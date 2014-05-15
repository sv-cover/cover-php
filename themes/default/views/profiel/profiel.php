<?php
require_once 'form.php';
require_once 'markup.php';

function empty_to_http_formatter($value) {
	if (!$value)
		return 'http://';
	else
		return $value;
}


class ProfielView extends View {
		protected $__file = __FILE__;

		function is_current_member($iter) {
			static $is_current = null;

			if ($is_current !== null)
				return $is_current;

			return $is_current = logged_in('id') == $iter->get('lidid');
		}

		function member_write_permission($iter) {
			static $perms = null;

			if ($perms !== null)
				return $perms;

			$perms = ($this->is_current_member($iter) || member_in_commissie(COMMISSIE_BESTUUR));

			return $perms;
		}

		function privacy_parse($model, $iter, $label, $name, $data, $errors, $read_only = false) {
			/* If the currently logged in member does not have write access 
			 * and the field is private, then return ''
			 */
			if (!$this->member_write_permission($iter->get('lidid')) && $model->is_private($iter, $name))
				return '';

			/* Setup the label */
			$result = '<tr><td>' . label($label, __($name), $errors) . ':</td><td>';

			/* Show an input text field when there is write permission and
			 * there is data */
			if (member_in_commissie(COMMISSIE_BESTUUR) || ($this->member_write_permission($iter->get('lidid')) && !$read_only))
				$result .= input_text($name, $data);
			else /* Show the field otherwise */
				$result .= htmlspecialchars($data[$name]);

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

		public function get_active_session_count($member_id)
		{
			$model = get_model('DataModelSession');
			
			return count($model->getActive($member_id));
		}

		public function get_active_subscriptions($member_id)
		{
			$model = get_model('DataModelMailinglijst');

			$all_lists = $model->get_lijsten($member_id, !member_in_commissie(COMMISSIE_BESTUUR));

			$subscriptions = array();

			foreach ($all_lists as $list)
				if ($list->get('subscribed'))
					$subscriptions[] = $list;

			return $subscriptions;
		}

		public function get_commissies($iter)
		{
			$model = get_model('DataModelCommissie');

			return $model->get_commissies_for_member($iter->get('id'));
		}

}
