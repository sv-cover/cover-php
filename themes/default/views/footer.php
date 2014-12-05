<?php

    function view_footer($model, $iter, $params) {
		?>
				</div> <!-- CONTENTS_TEXT -->
				
				<div class="menu column">
				<? //echo create_agenda_lustrum('green'); ?>
				<? echo create_agenda_menu('green'); ?>
				<? echo create_poll_menu('blue'); ?>
			
				</div>	
				
				<div class="aff column">
                    <?php
                    	require_once dirname(__FILE__) . '/Rotator.php';
                        $rotator = new Rotator('images/banners/');
                        $banners = $rotator -> get(7);
                        foreach ($banners as $banner)
                        {
                            echo '<a href="'.$banner['url'].'" target="_new"><img src="images/banners/'.$banner['filename'].'"></a><br /><br />';
                        }
                    ?>
				</div>
		</div> <!-- CONTAINER -->
	
		<script>
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		  ga('create', 'UA-12500955-1', 'auto');
		  ga('require', 'displayfeatures');
		  ga('send', 'pageview');

		</script>
		<?php if (date('md') == '0401'): ?>
		<script src="<?=get_theme_data('data/professionalism.js')?>"></script>
		<?php endif ?>

		<?php if (get_config_value('show_queries', false)): ?>
		<!--
		<?php foreach (get_db()->history as $query)
			printf("%f: %s\n\n", $query['duration'], $query['query']); ?>
		-->
		<?php endif ?>
	</body>
</html>
<?php
	}
?>
