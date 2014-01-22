<?php

    require_once('Rotator.php');
    
	function view_footer($model, $iter, $params) {
		?>
				</div> <!-- CONTENTS_TEXT -->
				
				<div class="menu column">
				<? //echo create_agenda_lustrum('green'); ?>
				<? echo create_agenda_menu('green'); ?>
				<!--<? echo create_links_menu('yellow'); ?>-->
				<? echo create_poll_menu('blue'); ?>
			
				</div>	
				
				<div class="aff column">
                    <?
                        $rotator = new Rotator('images/banners/');
                        $banners = $rotator -> get(7);
                        foreach ($banners as $banner)
                        {
                            echo '<a href="'.$banner['url'].'"><img src="images/banners/'.$banner['filename'].'" /></a><br /><br />';
                        }
                    ?>
				</div>
		</div> <!-- CONTAINER -->
	
		<script type="text/javascript">
			var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
			document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
		</script>
		<script type="text/javascript">
			try {
			var pageTracker = _gat._getTracker("UA-12500955-1");
			pageTracker._trackPageview();
			} catch(err) {}
		</script>
	</body>
</html>
<?php
	}
?>
