<?php
	require_once('include/login.php');
	require_once('include/form.php');
	require_once('include/member.php');
	
	
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
			'gastenboek' => 'gastenboek;markup',
			'boeken' => 'boeken;notebook;markup',
			'studieondersteuning' => 'studieondersteuning;notebook;markup',
			'afstudeerplaatsen' => 'markup',
			'index' => 'markup',
			'show' => 'markup',
			'links' => 'links',
			'taken' => 'taken;markup',
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
				$contents .= '<li class="clearfix"><span class="date">' . sprintf('%02d-%02d', $iter->get('vandatum'), $iter->get('vanmaand')) . '</span><a href="agenda.php?agenda_id=' . $iter->get_id() . '">' . $iter->get('kop') . '</a></li>';
			}
			
			$contents .= "</ul>\n";
		} else {
			$contents .= '<p><span class="smaller">' . _('Er staan op dit moment geen activiteiten op de agenda.') . "</span></p>\n";
		}
		
		$contents .= '<p><span class="smaller"><a href="agenda.php"><b>' . _('Volledige agenda') . '</b></a></span></p>';
		
		$contents .= create_jarigen();
		
		return create_menu($color, 'agenda', _('Agenda'), $contents);
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
			$contents .= '<p><span class="smaller">' . _('Er staan op dit moment geen activiteiten op de agenda.') . "</span></p>\n";
		}
		$contents = "<img src=\"images/lustrumlogo2.png\" alt=\"lustrum\">".$contents;
		return create_menu($color, 'lustrum', _('Lust, Rum & Rock \'n Roll'), $contents);
	}
	
	function create_links_menu($color) {
		$contents = '
		<ul class="links">
			<li><a href="index.php">' . _('Home') . '</a></li>';
		 
		$admin = array();
		
		if (member_in_commissie(COMMISSIE_BESTUUR)) {
			$admin[] = '<a href="agenda.php?agenda_moderate">' . _('Agenda') . '</a>';
			$admin[] = '<a href="links.php?links_moderate">' . _('Links') . '</a>';
			$admin[] = '<a href="studieondersteuning.php?so_moderate">' . _('Studieondersteuning') . '</a>';
			$admin[] = '<a href="actieveleden.php">' . _('Commissies') . '</a>';
			$admin[] = '<a href="forum.php?admin=forums">' . _('Forum') . '</a>';
			$admin[] = '<a href="nieuwlid.php">' . _('Leden toevoegen') . '</a>';
			$admin[] = '<a href="show.php?show_new">' . _('Pagina maken') . '</a>';
			$admin[] = '<a href="banners.php">' . _('Advertenties') . '</a>';
		}
		if (member_in_commissie(COMMISSIE_BOEKCIE)) {
			$admin[] = '<a href="boeken.php?bestellingen">' . _('Bestelde boeken') . '</a>';
		}
		if (member_in_commissie(COMMISSIE_EASY)) {
			$admin[] = '<a href="taken.php">' . _('Taken') . '</a>';
		}
		
		if (count($admin) > 0) {
			$contents .= '<li class="expander"><a href="javascript:do_expander(\'menu_admin\', true);"><img id="expander_menu_admin" class="expander" src="' . get_theme_data('images/collapsed.png') . '" alt="collapsed"/></a> <a href="javascript:do_expander(\'menu_admin\', true);">' . _('Beheer') . '</a>
			<div id="menu_admin" class="expander_menu">
			<ul class="expander">';
			
			foreach ($admin as $item)
				$contents .= '<li>' . $item . "</li>\n";
			
			$contents .= '</ul>
			</div>
			</li>';
		}
		
		$contents .= '	<li class="expander"><a href="javascript:do_expander(\'menu_cover\', true);"><img id="expander_menu_cover" class="expander" src="' . get_theme_data('images/collapsed.png') . '" alt="collapsed"/></a> <a href="javascript:do_expander(\'menu_cover\', true);">Cover</a>
			<div id="menu_cover" class="expander_menu">
			<ul class="expander">
				<li><a href="show.php?id=18">' . _('Lid/donateur worden') . '</a></li>
				<li><a href="show.php?id=16">' . _('Actief worden') . '</a></li>
				<li><a href="show.php?id=25">' . _('ALV') . '</a></li>
				<li><a href="show.php?id=0">' . _('Bestuur') . '</a></li>
				<li><a href="commissies.php">' . _('Commissies') . '</a></li>
				<li><a href="boeken.php">' . _('Boeken bestellen') . '</a></li>
				<li><a href="studieondersteuning.php">' . _('Studieondersteuning') . '</a></li>
				<li><a href="show.php?id=28">' . _('Zusterverenigingen') . '</a></li>
				<li><a href="show.php?id=30">' . _('Documenten') . '</a></li>
			</ul>
			</div>
			</li>
			<li class="expander"><a href="javascript:do_expander(\'menu_bedrijven\', true);"><img id="expander_menu_bedrijven" class="expander" src="' . get_theme_data('images/collapsed.png') . '" alt="collapsed"/></a> <a href="javascript:do_expander(\'menu_bedrijven\', true);">Bedrijven</a>
			<div id="menu_bedrijven" class="expander_menu">
			<ul class="expander">
				<li><a href="show.php?id=51">Bedrijfsprofielen</a></li>
				<li><a href="show.php?id=54">Vacatures</a></li>
			</ul>
			</div>
			</li>
			<li><a href="almanak.php">' . _('Almanak') . '</a></li>
			<li><a href="forum.php">' . _('Forum') . '</a></li>
			<li><a href="fotoboek.php">' . _('Foto\'s') . '</a></li>
			<li><a href="gastenboek.php">' . _('Gastenboek') . '</a></li>
			<li><a href="weblog.php">' . _('Weblog') . '</a></li>
			<li class="expander"><a href="javascript:do_expander(\'menu_ki\', true);"><img id="expander_menu_ki" class="expander" src="' . get_theme_data('images/collapsed.png') . '" alt="collapsed"/></a> <a href="javascript:do_expander(\'menu_ki\', true);">K.I.</a>
			<div id="menu_ki" class="expander_menu">
			<ul class="expander">
				<li><a href="show.php?id=23">' . _('De studie') . '</a></li>
				<li><a href="show.php?id=24">' . _('Alumni') . '</a></li>
				<li><a href="links.php">' . _('Links') . '</a></li>
				<li><a href="afstudeerplaatsen.php">' . _('Afstudeerplaatsen') . '</a></li>
			</ul>
			</div>
			</li>
			<li class="expander"><a href="javascript:do_expander(\'menu_inf\', true);"><img id="expander_menu_inf" class="expander" src="' . get_theme_data('images/collapsed.png') . '" alt="collapsed"/></a> <a href="javascript:do_expander(\'menu_inf\', true);">Informatica</a>
			<div id="menu_inf" class="expander_menu">
			<ul class="expander">
				<li><a href="show.php?id=41">' . _('De studie') . '</a></li>
				<li><a href="afstudeerplaatsen.php">' . _('Afstudeerplaatsen') . '</a></li>
			</ul>
			</div>
			</li>
			<li><a href="show.php?id=17">' . _('Contact') . '</a></li>
		</ul>';

		$names = array('cover', 'ki', 'inf');
		
		if (member_in_commissie(COMMISSIE_BESTUUR))
			$names[] = 'admin';
	
		foreach ($names as $name) {
			$collapse = 'collapse_' . $name;
			
			if (isset($_SESSION['menu_config'][$collapse]) && !$_SESSION['menu_config'][$collapse]) {
				$contents .= '
				<script type="text/javascript">
					do_expander(\'menu_' . $name . '\', false);
				</script>';
			}
		}

		return create_menu($color, 'links', _('Menu'), $contents);
	}
	
	function createTopMenu() {
		$content = '
			<div class="headNav">
				<ul>
					<li><a href="index.php">' . _('Home') . '</a></li>';
		if (member_in_commissie(COMMISSIE_BESTUUR) ||
			member_in_commissie(COMMISSIE_BOEKCIE) ||
			member_in_commissie(COMMISSIE_EASY))
			$content .= '
					<li class = "dropDown"><a drop="beheer" href="" onclick="return false;">'._('Beheer') .'</a></li>';
		
		
				//fill up the admin menu
		$admin = array();
		if (member_in_commissie(COMMISSIE_BESTUUR)) {
			$admin[] = '<a href="agenda.php?agenda_moderate">' . _('Agenda') . '</a>';
			$admin[] = '<a href="links.php?links_moderate">' . _('Links') . '</a>';
			$admin[] = '<a href="studieondersteuning.php?so_moderate">' . _('Studieondersteuning') . '</a>';
			$admin[] = '<a href="actieveleden.php">' . _('Commissies') . '</a>';
			$admin[] = '<a href="forum.php?admin=forums">' . _('Forum') . '</a>';
			$admin[] = '<a href="nieuwlid.php">' . _('Leden toevoegen') . '</a>';
			$admin[] = '<a href="show.php?show_new">' . _('Pagina maken') . '</a>';
			$admin[] = '<a href="banners.php">'. _('Advertenties') .'</a>';
		}
		
		if (member_in_commissie(COMMISSIE_BOEKCIE)) {
			$admin[] = '<a href="boeken.php?bestellingen">' . _('Bestelde boeken') . '</a>';
		}
		if (member_in_commissie(COMMISSIE_EASY)) {
			$admin[] = '<a href="taken.php">' . _('Taken') . '</a>';
			$admin[] = '<a href="_priv/docs/api/html/">' . _('Documentatie') . '</a>';
		}
		
		$content .= '
					<li class="dropDown"><a drop="vereniging" href="" onclick="return false;">Vereniging</a></li>
					<li class="dropDown"><a drop="leden" href="" onclick="return false;">Leden</a></li>
					<li class="dropDown"><a drop="bedrijven" href="" onclick="return false;">Bedrijven</a></li>
					<li><a href="forum.php">' . _('Forum') . '</a></li>
					<li><a href="fotoboek.php">' . _('Foto\'s') . '</a></li>
					<li class="dropDown"><a drop="studie" href="" onclick="return false;">Studie</a></li>
					<li><a href="show.php?id=17">' . _('Contact') . '</a></li>
				</ul><script>
$("#verenigingClick").click(function(){
	$(".subNav ul:visible").each( function() {
		if (this != "#vereniging"){
			$(this).slideToggle("slow");
		}
	});
	$("#vereniging").slideToggle("slow");
}); 
</script>
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
					<li><a href="show.php?id=0">' . _('Bestuur') . '</a></li>
					<li><a href="commissies.php">' . _('Commissies') . '</a></li>
					<li><a href="show.php?id=28">' . _('Zusterverenigingen') . '</a></li>
					<li><a href="show.php?id=18">' . _('Lid/donateur worden') . '</a></li>
					<li><a href="show.php?id=30">' . _('Documenten') . '</a></li>
					<li><a href="show.php?id=25">' . _('ALV (historie)') . '</a></li>
					<li><a href="weblog.php">' . _('Weblog') . '</a></li>
				</ul>
				<ul id="leden" class="expander">
					<li><a href="almanak.php">' ._('Almanak') .'</a></li>
					<li><a href="http://www.shitbestellen.nl" target="_blank">' ._('Merchandise') .'</a></li>
					<li><a href="profiel.php#msdnaa">' ._('MSDNAA') .'</a></li>
				</ul>
				<ul id="bedrijven" class="expander">
					<li><a href="show.php?id=51">Bedrijfsprofielen</a></li>
					<li><a href="show.php?id=54">Vacatures</a></li>
					<li><a href="afstudeerplaatsen.php">' . _('Afstudeerplaatsen') . '</a></li>
					<li><a href="show.php?id=56">' ._('Sponsormogelijkheden') .'</a></li>
				</ul>
				<ul id="studie" class="expander">
					<li><a href="show.php?id=23">' . _('K.I.') . '</a></li>
					<li><a href="show.php?id=41">' . _('Informatica') . '</a></li>
					<li><a href="show.php?id=24">' . _('Alumni') . '</a></li>
					<li><a href="gastenboek.php">' . _('Gastenboek') . '</a></li>
					<li><a href="links.php">' . _('Links') . '</a></li>
					<li><a href="boeken.php">' . _('Boeken bestellen') . '</a></li>
					<li><a href="http://studieondersteuning.svcover.nl/">' . htmlentities(_('Tentamens & Samenvattingen')) . '</a></li>				
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
		$forum_model = get_model('DataModelForum');
		$config_model = get_model('DataModelConfiguratie');
		
		$id = $config_model->get_value('poll_forum');
		
		/* Get last thread */
		if ($id) {
			$forum = $forum_model->get_iter($id);
			
			if ($forum)
				$thread = $forum->get_newest_thread();
		}

		if ($thread) {
			$contents = '<p><a href="forum.php?thread=' . $thread->get('id') . '">' . $thread->get('subject') . '</a></p>';
			ob_start();
			run_view('poll', $poll_model, $thread, array('enable_new' => logged_in() && $thread->get('since') >= 14));
			$contents .= ob_get_contents();
			ob_end_clean();
		} else
			$contents = '<p>' . _('Er is op dit moment geen poll') . '</p>';

		return create_menu($color, 'poll', _('Poll'), $contents);
	}
	
	function create_onestat_menu() {
		?>
		<div <?= member_in_commissie(COMMISSIE_EASY) ? "" : 'style="visibility: hidden"' ?>>
		<?
			include("onestat.txt");
		?>
		</div>
		<?
	}

	function create_menu($color, $name, $title, $contents) {
		echo '
		<div class="menuItem">
			<hr class="menu_contents" />
			<a href="javascript:do_menu_expander(\'menu_' . $name . '\', true)"><img id="expander_menu_' . $name . '" class="menuControl" src="' . get_theme_data('images/min.png') . '" alt="min"/></a>
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
		$contents = '<p class="bold">' . _('Jarigen') . '</p>
		<hr>
		<div class="smaller">';

		$model = get_model('DataModelMember');
		
		$jarigen = $model->get_jarigen();
		
		if (!$jarigen || count($jarigen) == 0)
			$contents .=  _('Er zijn vandaag geen jarigen');
		else 
			$contents .= sprintf(ngettext('Er is vandaag %d jarige:', 'Er zijn vandaag %d jarigen:', count($jarigen)), count($jarigen));
		
		$contents .= '<br>';
		
		foreach ($jarigen as $jarige) {
			$contents .= '<a href="profiel.php?lid=' . $jarige->get('id') . '">' . member_full_name($jarige) . '</a> (' . $jarige->get('leeftijd') . ')<br>';
		}

		return $contents . '</div>';
	}


	function create_login_form() {
		$contents = '
		<form action="dologin.php" method="post">
		<table>
		<tr><td><a href="wachtwoordvergeten.php">wachtwoord vergeten?</a></td><td><a href="lidworden.php">lid worden?</a></td></tr>
		<tr><td colspan="2"><label for="email">E-mail adres: </label>' . input_text('email', null, 'class', 'textField', 'id', 'email','value', _('email'), 'onFocus', 'javascript:clear_text(this, \'email\');') . '</td><td></td></tr>
		<tr><td colspan="2"><label for="pass">Wachtwoord: </label>' . input_password('pass', null, 'class', 'textField', 'id', 'pass', 'value', 'password', 'onFocus', 'javascript:clear_text(this, \'\');') . '</td></tr>
		<tr><td>' . input_checkbox('remember', null, 'yes', 'checked', 'checked') . ' ' . _('Blijvend') . '</td><td class="text_right"><input type="hidden" name="referer" value="' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . '"/>' . input_submit('subm', _('Inloggen')) . '</tr></table>
		</form>';
		
		return $contents;
	}
	
	function create_login() {
		if (($data = logged_in())) {
			//require_once('../isdecoverkameropen/ck.php');
			$output =  _('Ingelogd') . ': <b>' . $data['voornaam'] . '</b><br/>
			<a class="logButton" href="dologout.php?referrer=' . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) . '">' . _('Uitloggen') . '</a>
			<a class="logButton" href="profiel.php?lid=' . $data['id'] . '">' . _('Profiel') . '</a>';
			if($ck_open) {
				$output .= "<p class=\"clearBoth\">De CoverKamer is <b>open</b></p>";
			} else {
				$output .= "<p class=\"clearBoth\">De CoverKamer is <b>dicht</b></p>";
			}
			return $output;
		} else {
			return create_login_form();
		}
	}
	
	function create_message() {
		if (!member_in_commissie(COMMISSIE_BESTUUR))
			return '';

		/* Check for moderates */
		$model = get_model('DataModelAgenda');
		$admin = '';

		if (($aantal = $model->has_moderate())) {
			if ($aantal == 1)
				$cap .= _('Er staat nog 1 agendapunt in de wachtrij');
			else
				$cap .= sprintf(_('Er staan nog %d agendapunten in de wachtrij'), $aantal);
			
			$admin .= '<a href="agenda.php?agenda_moderate">' . $cap . "</a><br/>\n";
		}
		
		if ($admin)
			return '<div class="message">' . $admin . '</div>';
		else
			return '';
	}
			
	function view_header($model, $iter, $params) {
		header('Content-type: text/html; charset=ISO-8859-15');

		echo '
<!DOCTYPE HTML>
<html>
	<head>
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<meta http-equiv="Content-type" content="text/html; charset=ISO-8859-15">
	<link rel="icon" type="image/png" href="images/favicon.png">';
		
		
		echo '<title>Cover :: ' . _('Studievereniging Kunstmatige Intelligentie en Informatica') . ', RuG</title>
		<link rel="stylesheet" href="' . get_theme_data('style.css') . '?'.time().'" type="text/css">
		<!--[if lte IE 7]>
			<link rel="stylesheet" href="'. get_theme_data('styleIE.css') .'" type = "text/css" />
		<![endif]-->';
		$styles = get_styles();
		
		foreach ($styles as $style)
			echo '<link rel="stylesheet" href="' . get_theme_data('styles/' . $style . '.css') . '?'.time().'" type="text/css">' . "\n";
		
		$controller = basename($_SERVER['PHP_SELF'], '.php');
		
		if ($controller == 'gastenboek')
			echo '<link rel="alternate" type="application/rss+xml" title="RSS" href="gastenboek.php?rss">' . "\n";
		
		echo '<script type="text/javascript" src="' . get_theme_data('data/expander.js') . '"></script>
		<script type="text/javascript" src="' . get_theme_data('data/common.js') . '"></script>
		
		<!--<script type = "text/javascript" src = "' . get_theme_data('data/menu.js') . '"></script>
<script type = "text/javascript" src = "' . get_theme_data('data/jsTrace.js') . '"></script>
<script type = "text/javascript" src = "' . get_theme_data('data/dom-drag.js') . '"></script>
		-->
		
		<script type="text/javascript" src="' . get_theme_data('data/popup.js') . '"></script>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
		<script type = "text/javascript" src = "' . get_theme_data('data/dropdown.js') . '"></script>
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
				echo 'alert("' . str_replace("\n", '\n', str_replace('"', '\"', $_SESSION['alert'])) . '");';
				unset($_SESSION['alert']);
			}

			echo '
			}
		</script>
		
	</head>
	<body onLoad="page_load();" id="world">
		<div class="header">
				' . create_message() . '
				<div class="login">
				' . create_login() . '
				</div>';
				
				$logo = '<a href="."><img src="' . get_theme_data('images/cover_logo.png') . '" alt="logo"/></a>';
				if (date('m') == 12 && date('d') > 5 && date('d') < 27){
					$logo = '<a href="."><img src="' . get_theme_data('images/kerstlogo.png') . '" style="margin-top: -20px;" alt="logo"/></a>';
				} else if (date('m') == 9 && date('d') > 13 && date('d') < 21 && date('Y') == 2013){
					$logo = '<a href="."><img src="' . get_theme_data('images/lustrumlogo.png') . '" alt="logo"/></a>';
				}
		echo $logo.'
		</div>
		<div class="topMenu clearfix">
			'. createTopMenu() . '
		</div>
		<div class="container clearfix">
			<div class="center column" id="contents">';
		//echo(var_dump(logged_in()));
	}

?>
