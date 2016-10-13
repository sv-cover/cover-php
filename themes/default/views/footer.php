<?php

    function view_footer($model, $iter, $params) {
		?>
				</div> <!-- CONTENTS_TEXT -->
				
				<div class="menu column">
					<? //echo create_agenda_lustrum('green'); ?>
					<?= create_agenda_menu('green'); ?>
					<?= create_poll_menu('blue'); ?>
					<div class="menuItem search">
						<form action="search.php" method="get">
							<label for="menu-search-query">
								<i class="fa fa-search"></i>
							</label>
							<input type="search" id="menu-search-query" name="query" placeholder="<?=__('Typ hier om te zoeken…')?>">
						</form>
					</div>
				</div>	
				
				<div class="aff column">
					<?php
                    	require_once dirname(__FILE__) . '/Rotator.php';
                        $rotator = new Rotator('images/banners/');
                        $banners = $rotator -> get(15);

                        $main_sponsors = '';

                        foreach ($banners as $banner)
                        	if ($banner['type'] === 'main-sponsor')
	                            $main_sponsors .= '<a href="'.$banner['url'].'" target="_blank"><img src="images/banners/'.$banner['filename'].'"></a><br /><br />';

	                    if(!empty($main_sponsors))
	                    	echo '<h4>'.__('Hoofdpartner').':</h4>' . $main_sponsors . '<h4>'.__('Partners').':</h4>';

                        foreach ($banners as $banner)
                        	if ($banner['type'] === 'default')
	                            echo '<a href="'.$banner['url'].'" target="_blank"><img src="images/banners/'.$banner['filename'].'"></a><br /><br />';
                    ?>
				</div>
		</div> <!-- CONTAINER -->

		</div> <!-- .world -->
		
		<?php /* Google Analytics */ ?>
		<script>
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		  ga('create', 'UA-12500955-1', 'auto');
		  ga('require', 'displayfeatures');
		  ga('send', 'pageview');
		</script>

		<?php /* Popup messages such as "your agenda item has been submitted" */ ?>
		<?php if (isset($_SESSION['alert'])): ?>
		<script>
			alert(<?= json_encode((string) $_SESSION['alert']) ?>);
			<?php unset($_SESSION['alert']) ?>
		</script>
		<?php endif ?>
	
		<?php /* Some people have to take the first of April way too serious */ ?>
		<?php if (date('md') == '0401'): ?>
		<script src="<?=get_theme_data('data/professionalism.js')?>"></script>
		<?php endif ?>
	
		<?php /* Print queries and their times in the comment section when enabled */ ?>
		<?php if (get_config_value('show_queries', false)): ?>
		<!--
		<?php foreach (get_db()->history as $query)
			printf("%f: %s\n\n", $query['duration'], $query['query']);

			printf("Driver: %s\n", get_class(get_db()));
			printf("Total time: %fs", array_sum(array_map(function($query) { return $query['duration']; }, get_db()->history)));
		?>
		-->
		<?php endif ?>
	</body>
</html>
<?php
	}
?>
