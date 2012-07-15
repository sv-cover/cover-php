<?php
	function page_navigation($url, $current, $max, $nav_num = 10) {
		$nav = '<div class="page_navigation">' . _('Ga naar pagina') . ': ';

		if ($current != 0)
			$nav .= '<a href="' . add_request($url, 'page=' . ($current - 1)) . '">' . image('previous.png', _('vorige'), _('Vorige pagina') . '</a>');
		
		$nav_min = max(0, $current - ($nav_num / 2));
		$nav_max = min($max, $current + ($nav_num / 2) - 1);
		
		if ($nav_max - $nav_min < $nav_num)
			$nav_max = min($max, $nav_min + $nav_num - 1);
		
		for ($i = $nav_min; $i <= $nav_max; $i++) {
			if ($i == $current)
				$nav .= '<span class="bold">' . ($i + 1) . '</span> ';
			else
				$nav .= '<a href="' . add_request($url, 'page=' . $i) . '">' . ($i + 1) . '</a> ';
		}
		
		if ($current != $max)
			$nav .= '<a href="' . add_request($url, 'page=' . ($current + 1)) . '">' . image('next.png', _('volgende'), _('Volgende pagina')) . '</a>';
		
		return $nav . "</div>\n";
	}
?>
