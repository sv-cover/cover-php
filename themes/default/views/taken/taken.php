<?php
require_once('markup.php');

function _parse_taaknrs_real($matches) {
	$model = get_model('DataModelTaken');
	$iter = $model->get_iter($matches[1], -1);

	if ($iter)
		return '<a href="taken.php?taak=' . $iter->get_id() . '">#' . $matches[1] . ':' . $iter->get('taak') . '</a>';
	else
		return $matches[0];

}

class TakenView extends View {
		protected $__file = __FILE__;

		function taken_get_easiers() {
			$commissie_model = get_model('DataModelCommissie');
			$leden = $commissie_model->get_leden(COMMISSIE_EASY);
			$easiers = array(0 => _('Niemand'));

			foreach ($leden as $lid)
				$easiers[$lid->get_id()] = $lid->get('voornaam');

			return $easiers;
		}

		function parse_taaknrs($value) {
			return preg_replace_callback('/#([0-9]+)([^0-9;]|$)/', '_parse_taaknrs_real', $value);
		}

}
?>