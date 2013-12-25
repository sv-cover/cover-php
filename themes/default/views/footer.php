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
				<? echo create_onestat_menu();	?>
			
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
					<!--<a href="http://www.procam.nl"><img src="/images/banner_procam.gif" alt="Procam ICT"></a>
					<br /><br />
					<a href="http://www.cafekarakter.nl/"><img src="/images/banner_karakter.jpg" alt="Cafe Karakter"></a>
					<br /><br />
					<a href="http://www.sogeti.nl"><img src="/images/banner_sogeti.png" alt="Sogeti"></a>
					<br /><br />
					<a href="http://www.finan.nl/index.php?option=com_content&task=blogcategory&id=26&Itemid=123"><img src="/images/banner_FINAN.png" alt="Finan"></a>
					<br /><br />
					<a href="http://werkenbijtno.nl/"><img src="/images/tno.jpg" alt="Werken bij TNO"></a>
					<br /><br />
					<a href="http://www.axonline.nl/"><img src="/images/axonlogo.png" alt="Alumnivereniging Axon"></a>-->
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
