<?php
require_once('Notebook.php');

class StudieondersteuningView extends View {
		protected $__file = __FILE__;

		function render_vakken($model, $jaar) {
			$iters = $model->get_for_year($jaar);
			$result = "<ul>\n";
			$total = 0;

			foreach ($iters as $iter) {
				$num = $model->get_num_documenten($iter->get('id'));

				$result .= '<li><a href="studieondersteuning.php?vak=' . $iter->get('id') . '">' . $iter->get('naam') . '</a> (' . sprintf(_ngettext('%d document', '%d documenten', $num), $num) . ")</li>\n";

				$total += $num;
			}

			return array($result . "</ul>\n", $total);
		}
	
}
?>
