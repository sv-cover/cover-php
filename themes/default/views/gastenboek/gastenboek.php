<?php
	require_once 'include/form.php';
	require_once 'include/markup.php';
	require_once 'include/pagenavigation.php';

	class GastenboekView extends View {
		protected $__file = __FILE__;

		function navigation($model, $nav_num = 10) {
			$max = $model->get_max_pages();
			$current = $model->current_page;
			$request = get_request('search', 'page');

			if ($model->condition)
				$request = add_request($request, 'search=' . urlencode($model->condition));

			return page_navigation($request, $current, $max - 1, $nav_num);	
		}
	}
?>
