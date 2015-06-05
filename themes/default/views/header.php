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
		$iters = $model->get_agendapunten(logged_in());
		
		if (count($iters) != 0) {
			$contents = "<ul class=\"agenda\">\n";
			
			for ($i = 0; $i < min(10, count($iters)); $i++) {
				$iter = $iters[$i];
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
					</a>
				</li>';
			}
			
			$contents .= "</ul>\n";
		} else {
			$contents .= '<p><span class="smaller">' . __('Er staan op dit moment geen activiteiten op de agenda.') . "</span></p>\n";
		}
		
		$contents .= '<p><span class="smaller"><a href="agenda.php"><b>' . __('Volledige agenda') . '</b></a></span></p>';
		
		$contents .= create_jarigen();
		
		return create_menu($color, 'agenda', __('Agenda'), $contents);
	}
	
	function create_agenda_lustrum($color) {
		$model = get_model('DataModelLustrum');
		$iters = $model->get_agendapunten(logged_in());
		
		if (count($iters) != 0) {
			$contents = "<ul class=\"agenda\">\n";
			
			for ($i = 0; $i < min(16, count($iters)); $i++) { 
				$iter = $iters[$i];
				$contents .= '<li class="clearfix"><span class="date">' . sprintf('%02d-%02d', $iter->get('vandatum'), $iter->get('vanmaand')) . '</span><a href="agenda.php?agenda_id=' . $iter->get_id() . '">' . $iter->get('kop') . '</a></li>';
			}
			
			$contents .= "</ul>\n";
		} else {
			$contents .= '<p><span class="smaller">' . __('Er staan op dit moment geen activiteiten op de agenda.') . "</span></p>\n";
		}
		$contents = "<img src=\"images/lustrumlogo2.png\" alt=\"lustrum\">".$contents;
		return create_menu($color, 'lustrum', __('Lust, Rum & Rock \'n Roll'), $contents);
	}
	
	function createTopMenu() {
		$content = '
			<div class="headNav">
				<ul>
					<li><a href="index.php">' . __('Home') . '</a></li>';
		if (member_in_commissie(COMMISSIE_BESTUUR) ||
			member_in_commissie(COMMISSIE_KANDIBESTUUR) ||
			// member_in_commissie(COMMISSIE_BOEKCIE) ||
			member_in_commissie(COMMISSIE_EASY))
			$content .= '
					<li class = "dropDown"><a drop="beheer" href="" onclick="return false;">'.__('Beheer') .'</a></li>';
		
		
				//fill up the admin menu
		$admin = array();
		if (member_in_commissie(COMMISSIE_BESTUUR) || member_in_commissie(COMMISSIE_KANDIBESTUUR)) {
			$admin[] = '<a href="agenda.php?agenda_moderate">' . __('Agenda') . '</a>';
			$admin[] = '<a href="actieveleden.php">' . __('Actieve leden') . '</a>';
			$admin[] = '<a href="forum.php?admin=forums">' . __('Forum') . '</a>';
			$admin[] = '<a href="nieuwlid.php">' . __('Leden toevoegen') . '</a>';
			$admin[] = '<a href="show.php?show_new">' . __('Pagina maken') . '</a>';
		}
		
		// if (member_in_commissie(COMMISSIE_BOEKCIE)) {
		// 	$admin[] = '<a href="boeken.php?bestellingen">' . __('Bestelde boeken') . '</a>';
		// }

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
		$contents = '<p class="bold">' . __('Jarigen') . '</p>
		<hr>
		<div class="smaller">';

		$model = get_model('DataModelMember');
		
		$jarigen = $model->get_jarigen();
		
		if (!$jarigen || count($jarigen) == 0)
			$contents .=  __('Er zijn vandaag geen jarigen');
		else 
			$contents .= sprintf(_ngettext('Er is vandaag %d jarige:', 'Er zijn vandaag %d jarigen:', count($jarigen)), count($jarigen));
		
		$contents .= '<br>';
		
		// FIXME: Laat nooit jarigen zien die deze gegevens verborgen hebben volgens hun privay settings!
		foreach ($jarigen as $jarige) {
			$contents .= '<a href="profiel.php?lid=' . $jarige->get('id') . '">' . member_full_name($jarige) . '</a> (' . $jarige->get('leeftijd') . ')<br>';
		}

		return $contents . '</div>';
	}


	function create_login_form() {
		$contents = '
		<form action="dologin.php" method="post">
		<table>
		<tr><td><a href="wachtwoordvergeten.php">' . __('wachtwoord vergeten?') . '</a></td><td><a href="lidworden.php">' . __('lid worden') . '</a></td></tr>
		<tr><td colspan="2"><label for="email">' . __('E-mailadres') . ': </label>' . input_text('email', null, 'class', 'textField', 'id', 'email', 'placeholder', __('E-mailadres')) . '</td><td></td></tr>
		<tr><td colspan="2"><label for="password">' . __('Wachtwoord') . ': </label>' . input_password('password', 'class', 'textField', 'id', 'password', 'placeholder', __('Wachtwoord')) . '</td></tr>
		<tr><td>' . input_checkbox('remember', null, 'yes', 'checked', 'checked') . ' ' . label(__('Blijvend'), 'remember') . '</td><td class="text_right"><input type="hidden" name="referer" value="' . edit_url($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'], [], ['error']) . '"/>' . input_submit('subm', __('Inloggen')) . '</tr>';

		if (isset($_GET['error']) && $_GET['error'] == 'login')
			$contents .= '<tr><td colspan="2" class="error">' . __('Verkeerde combinatie van e-mailadres en wachtwoord') . '</td></tr>';

		$contents .= '
		</table></form>';
		
		return $contents;
	}
	
	function create_login() {
		if (($data = logged_in())) {
			$output =  __('Ingelogd') . ': <b>' . markup_format_text($data['voornaam']) . '</b><br/>
			<a class="logButton" href="dologout.php?referrer=' . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) . '">' . __('Uitloggen') . '</a>
			<a class="logButton" href="profiel.php?lid=' . $data['id'] . '">' . __('Profiel') . '</a>';

			if (get_identity() instanceof ImpersonatingIdentityProvider)
				$output .= '<a href="sessions.php?view=overrides" data-placement-selector="modal" data-partial-selector=".overrides-form" class="logLink">' . __('Bekijk als…') . '</a>';

			return $output;
		} else {
			return create_login_form();
		}
	}
	
	function create_message()
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
		<link rel="stylesheet" href="' . get_theme_data('style.css') . '?'.time().'" type="text/css">
		<!--[if lte IE 7]>
			<link rel="stylesheet" href="'. get_theme_data('styleIE.css') .'" type = "text/css" />
		<![endif]-->';
		$styles = get_styles();
		
		foreach ($styles as $style)
			echo '<link rel="stylesheet" href="' . get_theme_data('styles/' . $style . '.css') . '?'.time().'" type="text/css">' . "\n";
		
		$controller = basename($_SERVER['PHP_SELF'], '.php');
		
		echo '
		<script src="//code.jquery.com/jquery-1.10.2.js"></script>
		<script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
		<script type="text/javascript" src="' . get_theme_data('data/expander.js') . '"></script>
		<script type="text/javascript" src="' . get_theme_data('data/common.js') . '"></script>
		<script type="text/javascript" src="' . get_theme_data('data/popup.js') . '"></script>
		<script type="text/javascript" src = "' . get_theme_data('data/dropdown.js') . '"></script>
		<script type="text/javascript" src="data/connection.js"></script>

		<script type="text/javascript">
			function page_load() {
				function trace( msg ){
 				 if( typeof( jsTrace ) != \'undefined\' ){
   					 jsTrace.send( msg );
 				 }
				}
				
			';
			
			if (isset($_SESSION['alert'])) {
				echo 'alert(' . json_encode((string) $_SESSION['alert']) . ');';
				unset($_SESSION['alert']);
			}

			echo '
			}
		</script>
		
	</head>
	<body onLoad="page_load();">
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
		<div class="container clearfix">
			<div class="center column" id="contents">';
	}

?>
