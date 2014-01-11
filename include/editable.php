<?php

	require_once('markup.php');
	require_once('login.php');
	require_once('member.php');

	if (!defined('IN_SITE'))
		return;

	/** @group Editable
	  * Get an array of pages of which an editable page consists
	  * @content the editable page content
	  *
	  * @result an array of pages
	  */
	function editable_split_pages($content) {
		$amount = preg_match_all('/\[page\](.*?)\[\/page\]/is', $content, $matches);

		if (!$amount)
			return Array($content);

		$splitpages = Array();

		foreach ($matches[1] as $page)
			$splitpages[] = $page;

		return $splitpages;
	}

	function _editable_parse_commissie_leden(&$page, $owner) {
		if (strstr($page, '[commissie_leden]')) {
			$model = get_model('DataModelCommissie');
			$leden = $model->get_leden($owner);
			
			if ($leden) {
				$lh = '<h3>' . __('Leden') . '</h3>
				<ul>';
				
				foreach ($leden as $lid)
					$lh .= '<li><a href="profiel.php?lid=' . $lid->get('id') . '">' . htmlspecialchars($lid->get('voornaam') . ' ' . $lid->get('tussenvoegsel') . ' ' . $lid->get('achternaam')) . '</a>' . htmlspecialchars($lid->get('functie') ? (' - ' . __($lid->get('functie'))) : '') . "</li>\n";
					
				$lh .= '</ul>';
			} else {
				$lh = '';
			}
			
			$page = preg_replace('/\[commissie_leden\]/i', $lh, $page);
		}	
	}
	
	function _editable_parse_commissie_poll(&$page, $owner) {
		if (!strstr($page, '[commissie_poll]'))
			return;

		/* Commissie polls are deprecated */
		$page = str_replace('[commissie_poll]', '', $page);
	}
	
	function _editable_parse_commissie_email(&$page, $owner) {
		if (!strstr($page, '[commissie_email]'))
			return;

		$model = get_model('DataModelCommissie');
			
		$email = $model->get_email($owner);
		$page = str_replace('[commissie_email]', '<a href="mailto:' . htmlspecialchars($email, ENT_QUOTES) . '">E-Mail</a>', $page);
	}
	
	function _editable_parse_commissie_foto(&$page, $owner) {
		if (!strstr($page,'[commissie_foto]'))
			return;
		
		/* CHECK: is this necessary */
		if ($owner == 12) { // KasCie
			$fotohtml = '<div class="commissie_foto"><img src="images/kascie.jpg"></div>';
		} elseif($owner == 14) { // RvA			
			$fotohtml = '<div class="commissie_foto"><img src="images/rva.jpg"></div>';
		} elseif($owner == 5) { // Brainstorm			
			$fotohtml = '<div class="commissie_foto"><img src="images/brainstorm.gif"></div>';
		} else {
			$model = get_model('DataModelCommissie');
			$login = $model->get_login($owner);

			if (file_exists("images/$login.jpg"))
		  		$fotohtml = '<div class="commissie_foto"><img src="images/' . $login . '.jpg"></div>';
		}

		$page = preg_replace('/\[commissie_foto\]/i', $fotohtml, $page);
	}

	function _editable_parse_commissie_agenda(&$page, $owner) {
		if (!strstr($page, '[commissie_agenda]'))
			return;
			
		$model = get_model('DataModelAgenda');
		$activiteiten = Array();
		
		/* Punten van deze commissie filteren */
		foreach ($model->get_agendapunten(logged_in()) as $punt)
			if ($punt->get('commissie') == $owner)
				$activiteiten[] = $punt;

		if (count($activiteiten) == 0) {
			$page = preg_replace('/\[commissie_agenda\]/i', '', $page);
			return;
		}

		$ah = '<a name="activiteiten"></a><h3>' . __('Commissieagenda') . '</h3>
		<p><ul>';

		foreach ($activiteiten as $punt) {
			$ah .= '<li><a href="agenda.php?id=' . $punt->get_id() . '"><b>' . htmlspecialchars($punt->get('kop')) . "</b></a><br/>\n";
			$ah .= agenda_period_for_display($punt) . '<br/>';

			if ($punt->get('locatie'))
				$ah .= __('Locatie') . ': ' . htmlspecialchars($punt->get('locatie')) . '<br/>';

			$ah .= "</li>\n";
		}
		
		$ah .= "</ul></p>\n";
		$page = preg_replace('/\[commissie_agenda\]/i', $ah, $page);
	}
	
	function _editable_parse_commissie_prive(&$page, $owner) {
		if (member_in_commissie($owner))
			$page = preg_replace(Array('/\[prive\]/i','/\[\/prive\]/i'), '', $page);
		else
			$page = preg_replace('/\[prive\](.*?)\[\/prive\]/ism', '', $page);
			
	}
	
	function _editable_parse_commissie_summary(&$page, $owner) {
		/* Just remove because the summary isn't used in general view */
		$page = preg_replace('/\[samenvatting\](.+?)\[\/samenvatting\]\s*/ism', '', $page);
	}

	/** @group Editable
	  * Parse editable page and return an array of pages with all markup
	  * formatted in html
	  * @content the editable page content
	  * @owner the owner of the editable page
	  *
	  * @result an array of pages with all markup replaced by html
	  */
	function editable_parse($content, $owner) {
		$splitpages = editable_split_pages($content);

		foreach ($splitpages as $page) {
			_editable_parse_commissie_summary($page, $owner);

			$page = markup_parse($page);

			_editable_parse_commissie_poll($page, $owner);
			_editable_parse_commissie_leden($page, $owner);
			_editable_parse_commissie_email($page, $owner);
			_editable_parse_commissie_foto($page, $owner);
			_editable_parse_commissie_agenda($page, $owner);
			
			_editable_parse_commissie_prive($page, $owner);
			
			$page = markup_clean($page);
			$pages[] = $page;
		}

    		return $pages;
	}
	
	/** @group Editable
	  * Get the summary of the editable
	  * @content the editable page content
	  * @owner the owner of the editable page
	  *
	  * @result a string containing the summary or an empty string if
	  * no summary could be found
	  */
	function editable_get_summary($content, $owner) {
		if (preg_match('/\[samenvatting\](.+?)\[\/samenvatting\]/msi', $content, $matches)) {
			$pages = editable_parse($matches[1], $owner);
			return $pages[0];
		} else {
			return '';
		}
	}
