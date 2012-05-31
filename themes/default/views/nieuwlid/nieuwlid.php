<?php
class NieuwlidView extends View {
		protected $__file = __FILE__;

		function render_text_row($caption, $field, $errors, $required) {
			$args = array_slice(func_get_args(), 4);
			array_unshift($args, null);
			array_unshift($args, $field);

			return table_row(label($caption, $field, $errors, $required),
			 	call_user_func_array('input_text', $args)) . "\n";
		}

}
?>