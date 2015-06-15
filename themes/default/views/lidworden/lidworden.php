<?php
require_once 'include/controllers/ControllerEditable.php';

class LidwordenView extends View
{
	protected $__file = __FILE__;
	
	protected function render_text_row($caption, $field, $errors, $required) {
		$args = array_slice(func_get_args(), 4);
		array_unshift($args, null);
		array_unshift($args, $field);

		return table_row(label($caption, $field, $errors, $required),
		 	call_user_func_array('input_text', $args)) . "\n";
	}

	public function view_verzonden($model, $iter, $params = null)
	{
		echo '<h1>' . __('Lidmaatschapsformulier') . '</h1>
		<p>' . __('Je lidmaatschapsaanvraag is verstuurd.') . '</p>
		<h2>' . __('Opmerkingen') . '</h2>';

		$opmerkingen = new ControllerEditable('Opmerkingen aanmelden');
		$opmerkingen->run();
	}
}
