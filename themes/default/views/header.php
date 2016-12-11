<?php
	require_once 'include/login.php';
	require_once 'include/form.php';
	require_once 'include/member.php';
	require_once 'include/policies/policy.php';
	
	
	/** 
	  * Get style files for the current used controller. This functions
	  * uses $_SERVER['PHP_SELF'] to determine the current controller file and map
	  * the styles
	  *
	  * @result an array with style files
	  */
	function get_styles() {
		$controller = basename($_SERVER['PHP_SELF'], '.php');
		
		$mapping = array(
			'boeken' => 'boeken;notebook;markup',
			'studieondersteuning' => 'studieondersteuning;notebook;markup',
			'index' => 'markup',
			'show' => 'markup',
			'links' => 'links',
			'almanak' => 'almanak',
			'profiel' => 'profiel;markup',
			'commissies' => 'commissies;markup',
			'workinggroups' => 'commissies;markup',
			'agenda' => 'agenda;markup',
			'fotoboek' => 'fotoboek;markup',
			'forum' => 'forum;markup',
			'weblog' => 'weblog;markup',
			'nieuwlid' => 'nieuwlid'
		);
		
		if (isset($mapping[$controller]))
			return explode(';', $mapping[$controller]);
		else
			return array();
	}	
	
	function create_agenda_menu($color) {
		$model = get_model('DataModelAgenda');
		
		$iters = array_filter($model->get_agendapunten(), [get_policy($model), 'user_can_read']);
		
		if (count($iters) != 0) {
			$contents = "<ul class=\"agenda\">\n";
			
			foreach (array_slice($iters, 0, 10) as $iter) {
				$date = strtotime($iter->get('van'));
				$details = $iter->get('extern')
					? __('Externe activiteit')
					: agenda_short_period_for_display($iter);
				$contents .= '<li>
					<a href="agenda.php?agenda_id=' . $iter->get_id() . '">
						<div class="calendar-icon">
							<span class="month">'.strftime('%b', $date).'</span>
							<span class="day">'.strftime('%d', $date).'</span>
						</div>
						<span class="title">' . $iter->get('kop') . '</span>
						<span class="details">' . $details . '</span>
						' . ($iter->is_proposal() ? '<span class="label-pending">' . __('Nog niet gepubliceerd') . '</span>' : '') . '
					</a>
				</li>';
			}
			
			$contents .= "</ul>\n";
		} else {
			$contents = '<p><span class="smaller">' . __('Er staan op dit moment geen activiteiten op de agenda.') . "</span></p>\n";
		}
		
		$contents .= '<p><span class="smaller"><a href="agenda.php"><b>' . __('Volledige agenda') . '</b></a></span></p>';
		
		$contents .= create_jarigen();
		
		return create_menu($color, 'agenda', __('Agenda'), $contents);
	}
	
	function createTopMenu() {
		$content = '
			<div class="headNav">
				<ul>
					<li><a href="index.php">' . __('Home') . '</a></li>';
		if (member_in_commissie(COMMISSIE_BESTUUR) ||
			member_in_commissie(COMMISSIE_KANDIBESTUUR) ||
			member_in_commissie(COMMISSIE_EASY))
			$content .= '
					<li class = "dropDown"><a drop="beheer" href="" onclick="return false;">'.__('Beheer') .'</a></li>';
		
		
		//fill up the admin menu
		$admin = array();
		$admin[] = '<a href="show.php?show_new">' . __('Pagina maken') . '</a>';

		if (member_in_commissie(COMMISSIE_BESTUUR) || member_in_commissie(COMMISSIE_KANDIBESTUUR)) {
			$admin[] = '<a href="agenda.php?agenda_moderate">' . __('Agenda') . '</a>';
			$admin[] = '<a href="actieveleden.php">' . __('Actieve leden') . '</a>';
			$admin[] = '<a href="forum.php?admin=forums">' . __('Forum') . '</a>';
			$admin[] = '<a href="nieuwlid.php">' . __('Leden toevoegen') . '</a>';
		}
		
		if (member_in_commissie(COMMISSIE_EASY)) {
			$admin[] = '<a href="settings.php">' . __('Instellingen') . '</a>';
		}
		
		$content .= '
					<li class="dropDown"><a drop="vereniging" href="" onclick="return false;">' . __('Vereniging') . '</a></li>
					<li class="dropDown"><a drop="leden" href="" onclick="return false;">' . __('Leden') . '</a></li>
					<li class="dropDown"><a drop="bedrijven" href="" onclick="return false;">' . __('Bedrijven') . '</a></li>
					<li><a href="forum.php">' . __('Forum') . '</a></li>
					<li><a href="fotoboek.php">' . __('Foto\'s') . '</a></li>
					<li class="dropDown"><a drop="studie" href="" onclick="return false;">' . __('Studie') . '</a></li>
					<li><a href="show.php?id=17">' . __('Contact') . '</a></li>
				</ul>';

		$language_options = array();

		foreach (i18n_get_languages() as $code => $language) {
			$language_options[] = sprintf('
				<label id="lang-%1$s">
					<input type="radio" name="language" value="%1$s"%3$s>
					<span>%2$s</span>
				</label>',
				$code, strtoupper($code),
				i18n_get_language() == $code ? ' checked="checked"' : '');
		}

		$content .= '
				<form method="post" id="language-switch" action="index.php">
					<input type="hidden" name="submindexlanguage" value="1">
					<input type="hidden" name="return_to" value="' . htmlentities($_SERVER['REQUEST_URI'], ENT_QUOTES) . '">
					' . implode("\n", $language_options) . '
					<button type="submit">' . __('Verander taal') . '</button>
				</form>
				</form>
			</div>
			<div class="subNav">
		';
				//if admin menu contains items
		if (count($admin) > 0)
		{
			$content .= '
				<ul id="beheer" class="expander">';
						//fill up the admin menu
				foreach ($admin as $item)
				{
					$content .='
					<li>'. $item . '</li>';
				}
			$content .= '
				</ul>';
		}
		$content .= '
				<ul id="vereniging" class="expander">
					<li><a href="commissies.php?commissie=board">' . __('Bestuur') . '</a></li>
					<li><a href="besturen.php">' . __('Vorige besturen') . '</a></li>
					<li><a href="commissies.php">' . __('Commissies') . '</a></li>
					<li><a href="workinggroups.php">' . __('Werkgroepen') . '</a></li>
					<li><a href="show.php?id=28">' . __('Zusterverenigingen') . '</a></li>
					<li><a href="show.php?id=18">' . __('Lid/donateur worden') . '</a></li>
					<li><a href="show.php?id=30">' . __('Documenten') . '</a></li>
					<li><a href="weblog.php">' . __('Weblog') . '</a></li>
				</ul>
				<ul id="leden" class="expander">
					<li><a href="almanak.php">' .__('Almanak') .'</a></li>
					<li><a href="mailinglijsten.php">' .__('Mailinglijsten') .'</a></li>
					<li><a href="https://wiki.svcover.nl/" target="_blank">' . __('Wiki') . '</a></li>
					<li><a href="https://sd.svcover.nl/" target="_blank">' . __('Standaardocumenten') . '</a></li>
					<li><a href="stickers.php">' . markup_format_text(__('Stickerkaart')) . '</a></li>
					<li><a href="http://www.shitbestellen.nl" target="_blank">' .__('Merchandise') .'</a></li>
					<li><a href="dreamspark.php">' .__('Dreamspark') .'</a></li>
				</ul>
				<ul id="bedrijven" class="expander">
					<li><a href="show.php?id=51">' . __('Bedrijfsprofielen') . '</a></li>
					<li><a href="show.php?id=54">' . __('Vacatures') . '</a></li>
					<li><a href="show.php?id=31">' . __('Stages/afstudeerplaatsen') . '</a></li>
					<li><a href="show.php?id=56">' .__('Sponsormogelijkheden') .'</a></li>
				</ul>
				<ul id="studie" class="expander">
					<li><a href="show.php?id=23">' . __('K.I.') . '</a></li>
					<li><a href="show.php?id=41">' . __('Informatica') . '</a></li>
					<li><a href="show.php?id=24">' . __('Alumni') . '</a></li>
					<li><a href="boeken.php">' . __('Boeken bestellen') . '</a></li>
					<li><a href="show.php?id=27">' . __('Info voor studenten') . '</a></li>
					<li><a href="http://studieondersteuning.svcover.nl/" target="_blank">' . markup_format_text(__('Tentamens & Samenvattingen')) . '</a></li>
				</ul>
			</div>
			';
			
		$names = array('cover', 'ki', 'inf');
		
		if (member_in_commissie(COMMISSIE_BESTUUR))
			$names[] = 'admin';
	
		foreach ($names as $name) {
			$collapse = 'collapse_' . $name;
			
			if (isset($_SESSION['menu_config'][$collapse]) && !$_SESSION['menu_config'][$collapse]) {
				$content .= '
				<script type="text/javascript">
					do_expander(\'menu_' . $name . '\', false);
				</script>';
			}
		}
		return $content;
	}



	function create_poll_menu($color) {
		$poll_model = get_model('DataModelPoll');
		$thread = $poll_model->get_latest_poll();

		if ($thread) {
			$contents = '<p><a href="forum.php?thread=' . $thread->get('id') . '">' . $thread->get('subject') . '</a></p>';
			ob_start();
			run_view('poll', $poll_model, $thread, array(
				'enable_new' => $poll_model->can_create_new_poll()));
			$contents .= ob_get_contents();
			ob_end_clean();
		} else
			$contents = '<p>' . __('Er is op dit moment geen poll') . '</p>';

		return create_menu($color, 'poll', __('Poll'), $contents);
	}
	
	function create_menu($color, $name, $title, $contents) {
		echo '
		<div class="menuItem">
			<hr class="menu_contents" />
			<div class="menu_header">
				' . $title . '
				<hr />
			</div>
			<div class="menu_contents" id="menu_' . $name . '">
			' . $contents . '
			</div>
		</div>';

		$collapse = 'collapse_' . $name;
		
		if (isset($_SESSION['menu_config'][$collapse]) && $_SESSION['menu_config'][$collapse]) {
			echo '
			<script type="text/javascript">
				do_menu_expander(\'menu_' . $name . '\', false);
			</script>';
		}
	}
	
	function create_jarigen() {
		$model = get_model('DataModelMember');
		
		$jarigen = $model->get_jarigen();

		$jarigen = array_filter($jarigen, function($member) use ($model) {
			return !$member->is_private('naam') && !$member->is_private('geboortedatum');
		});

		$lines = array_map(function($jarige) {
			return sprintf('<a href="profiel.php?lid=%d">%s</a> (%d)', $jarige->get_id(),
				markup_format_text(member_full_name($jarige, BE_PERSONAL)), $jarige['leeftijd']);
		}, $jarigen);

		// Cover Dies Natalis
		if (date('m-d') == '09-20')
			array_unshift($lines, sprintf('<span style="font-weight: bold; color: #c60c30;">Cover!</span> (%d)', date('Y') - 1993));
		
		$header = count($lines) > 0
			? sprintf(_ngettext('Er is vandaag %d jarige:', 'Er zijn vandaag %d jarigen:',
				count($lines)), count($lines))
			: __('Er zijn vandaag geen jarigen');
	
		return '<p class="bold">' . __('Jarigen') . '</p>
			<hr>
			<div class="smaller">'
				. $header . "<br>\n"
				. implode("<br>\n", $lines)
				. '</div>';
	}


	function create_login() {
		$referrer =  $_SERVER['PHP_SELF'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');

		if (get_auth()->logged_in()) {
			$output =  __('Ingelogd') . ': <b>' . markup_format_text(get_identity()->get('voornaam')) . '</b><br/>
			<a class="logButton" href="sessions.php?view=logout&amp;referrer=' . urlencode($referrer) . '">' . __('Uitloggen') . '</a>
			<a class="logButton" href="profiel.php?lid=' . get_identity()->get('id') . '">' . __('Profiel') . '</a>';

			if (get_identity() instanceof ImpersonatingIdentityProvider)
				$output .= '<a href="sessions.php?view=overrides&amp;referrer=' . urlencode($referrer) . '" data-placement-selector="modal" data-partial-selector=".overrides-form" class="logLink">' . __('Bekijk als…') . '</a>';

			return $output;
		} else {
			return login_link(__('Log in'), ['class' => 'logButton']);
		}
	}
	
	function create_message_agenda_item_moderations()
	{
		/* Check for moderates */
		$model = get_model('DataModelAgenda');
		
		$proposed_updates = array_filter($model->get_proposed(), [get_policy($model), 'user_can_moderate']);

		$aantal = count($proposed_updates);

		if ($aantal > 0)
			return '
				<div class="message">
					<a href="agenda.php?agenda_moderate">' . __N(
						'Er staat nog %d agendapunt in de wachtrij',
						'Er staan nog %d agendapunten in de wachtrij', $aantal) . '</a>
				</div>';
		else
			return '';
	}

	function create_message_account_status()
	{
		if (get_identity()->get('type', -1) == MEMBER_STATUS_UNCONFIRMED)
			return sprintf('<div class="message">%s</div>', __('Je lidmaatschap is nog niet goedgekeurd door de secretaris. Tot dan is de toegang tot sommige delen van de website, zoals de oude foto\'s en lidgegevens, beperkt.'));
		else
			return '';
	}

	function create_message()
	{
		return implode("\n", array_filter([
			create_message_account_status(),
			create_message_agenda_item_moderations()
		]));
	}

	function view_promotional_banner()
	{
		if (!logged_in())
			return view_signup_banner();
		else if (basename($_SERVER['SCRIPT_NAME']) == 'index.php') {
			if (get_config_value('committee_battle', false))
				return view_committee_battle_banner();
		}
	}

	function view_signup_banner()
	{
		return '
			<div class="promotional-banner sign-up-banner">
				<div class="background background-0 current"></div>
				<div class="background background-1"></div>
				<div class="background background-3"></div>
				<div class="background background-4"></div>
				<div class="background background-5"></div>

				<h1>' . __('Word Coverlid') . '</h1>
				<p>' . __('Voor boeken, activiteiten en gezelligheid.') . '</p>
				<a href="lidworden.php?utm_source=svcover.nl&utm_medium=banner&utm_campaign=member%20registration" class="button">' . __('Meld je aan!') . '</a>
			</div>
		';
	}

	function view_committee_battle_banner()
	{
		// $committees = get_identity()->get('committees');

		// $model = get_model('DataModelCommitteeBattleScore');

		// $scores = $model->get_scores_for_committees(array_map(function($id) {
		// 	return ['id' => $id];
		// }, $committees));

		$committee_model = get_model('DataModelCommissie');
		$committee_model->type = DataModelCommissie::TYPE_COMMITTEE;

		$committees = $committee_model->get(false);

		$committee_photos = array_map(getter('thumbnail'), $committees);
		$committee_photos = array_filter($committee_photos);
		shuffle($committee_photos);

		return '
			<div class="promotional-banner committee-battle-banner" data-photos="' . markup_format_attribute(json_encode($committee_photos)) . '">
				<h1>' . __('Committee battle') . '</h1>
				<p>' . __('Kijk hoe goed jouw commissies het doen tijdens de Committee Battle.') . '</p>
				<a href="committeebattle.php" class="button">' . __('Doe mee!') . '</a>
			</div>
		';
	}

	function view_header($model, $iter, $params) {
		header('Content-type: text/html; charset=UTF-8');

		echo '
<!DOCTYPE HTML>
<html>
	<head>
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<meta http-equiv="Content-type" content="text/html; charset=' . WEBSITE_ENCODING . '">
	<link rel="icon" type="image/png" href="images/favicon.png">
	<link rel="apple-touch-icon" sizes="152x152" href="images/apple-touch-icon-152x152.png">';
		
		$title = 'Cover :: ' . __('Studievereniging Kunstmatige Intelligentie en Informatica') . ', RuG';

		if (isset($params['title']))
			$title = $params['title'] . ' :: ' . $title;
		
		echo '<title>' . markup_format_text($title) . '</title>
		<link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
		<link rel="stylesheet" href="' . get_theme_data('style.css') . '" type="text/css">
		<link rel="stylesheet" href="' . get_theme_data('styles/font-awesome.min.css') . '" type="text/css">
		<!--[if lte IE 7]>
			<link rel="stylesheet" href="'. get_theme_data('styleIE.css') .'" type = "text/css" />
		<![endif]-->';
		$styles = get_styles();
		
		foreach ($styles as $style)
			echo '<link rel="stylesheet" href="' . get_theme_data('styles/' . $style . '.css')  . '" type="text/css">' . "\n";
		
		echo '
		<script src="' . get_theme_data('data/jquery-2.2.0.min.js') . '"></script>
		<script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
		<script type="text/javascript" src="' . get_theme_data('data/expander.js') . '"></script>
		<script type="text/javascript" src="' . get_theme_data('data/common.js') . '"></script>
		<script type="text/javascript" src="' . get_theme_data('data/popup.js') . '"></script>
		<script type="text/javascript" src = "' . get_theme_data('data/dropdown.js') . '"></script>
		<script type="text/javascript" src="data/connection.js"></script>';

		// Embed scripts as specified by the view
		if (isset($params['view']) && $params['view'] instanceof View)
			foreach ($params['view']->get_scripts() as $script)
				printf('<script src="%s"></script>', markup_format_attribute($script));

		echo '
	</head>
	<body>
		<div class="world">
		<div class="header clearfix">
				' . create_message() . '
				<div class="login">
				' . create_login() . '
				</div>';
				$logo = '<a href="."><img class="cover-logo" src="' . get_theme_data('images/cover_logo.png') . '" alt="logo"/></a>';
				if(intval(date('m')) == 12 && intval(date('d'))>=6){
					$logo = '<a href="."><img class="cover-logo" src="' . get_theme_data('images/cover_logo_christmas.png') . '" alt="logo" style="height:auto;margin-top:-12px"/></a>';
				}
		echo $logo.'
		</div>
		<div class="topMenu clearfix">
			'. createTopMenu() . '
		</div>
		' . view_promotional_banner() . '
		<div class="container clearfix">
			<div class="center column" id="contents">';
	}

?>
