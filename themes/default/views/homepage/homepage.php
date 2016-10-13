<?php
	require_once 'include/form.php';
 
	class HomepageView extends View {
		protected $__file = __FILE__;

		protected function render_banners(){
			require_once dirname(__FILE__) . '/../Rotator.php';
			$rotator = new Rotator('images/sponsors/');
			$banners = $rotator -> get(15);

			foreach ($banners as $banner)
				echo '<a href="'.$banner['url'].'" target="_blank"><img src="images/sponsors/'.$banner['filename'].'" class="frontpage_banner"></a>';
		}
	}
