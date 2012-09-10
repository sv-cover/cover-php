<?php
	require_once('markup.php');
	require_once('pagenavigation.php');
	require_once('poll/poll.php');

	function view_auth($model, $iter, $params = null) {
		echo '<h1>' . _('Forum') . '</h1>
		<div class="error_message">' . _('Je kunt niet schrijven in dit forum') . '</div>';
	}
	
	function view_not_editable($model, $iter, $params = null) {
		echo '<h1>' . _('Forum') . '</h1>
		<div class="error_message">' . _('Je hebt geen permissie om dit bericht te wijzigen') . '</div>';
	}
	
	function view_thread_not_found($model, $iter, $params = null) {
		echo '<h1>' . _('Forum') . '</h1>
		<div class="error_message">' . _('Dat bericht bestaat niet') . '</div>';
	}

	function view_message_not_found($model, $iter, $params = null) {
		echo '<h1>' . _('Forum') . '</h1>
		<div class="error_message">' . _('Dat bericht bestaat niet') . '</div>';
	}
	
	function view_thread($model, $iter, $params = null) {
		$forum = $model->get_iter($iter->get('forum'), -1);

		echo '<h1><a href="forum.php">' . _('Forum') . '</a> :: <a href="forum.php?forum=' . $forum->get('id') . '">' . $forum->get('name') . '</a></h1>';
		/*
		<h2>' . $iter->get('subject') . '</h2>
		*/
		$page = $params['page'];
		
		if (!$page)
			$page = 0;

		$messages = $iter->get_messages($page, $max);

		$bar = '';
	
		if ($max > 0)
			$bar .= '' . page_navigation('forum.php?thread=' . $iter->get('id'), $page, $max) . '';
		
		$canwrite = $model->check_acl($iter->get('forum'), ACL_REPLY);

		if ($canwrite)
			$bar .= '<span id="newbericht" class="button" style="padding-left: 10px;">'.image('new.png', _('nieuw'), _('Nieuw bericht toevoegen')) . ' <span class="messageText">' . _('Nieuw antwoord') . '</span></span>'; 
		else
			$bar .= _('Je kunt niet antwoorden in dit forum');

	

		$bar .= '</div>';
		
		echo '<div class="topbar">'.$bar;

		if ($canwrite) {
			$authors = get_authors($model, $iter->get('forum'), ACL_REPLY);
			render_new_message($authors, $iter, 'submforumnewmessage', $params, false);
		}

		echo '<table class="forum">';
		echo '<tr class="separator"><td colspan="2">'.$iter->get('subject').'</td></tr>';	
			//echo '<tr id="' . ($message == $iter ? 't' : 'p') . $message->get('id') . '" class="separator"><td colspan="2"></td></tr>';
		$i = 0;
		$admin = member_in_commissie(COMMISSIE_BESTUUR);

		foreach ($messages as $message) {
			$author_info = $model->get_author_info($message);
			$link = get_author_link($message);

			//echo '<tr id="' . ($message == $iter ? 't' : 'p') . $message->get('id') . '" class="separator"><td colspan="2"></td></tr>';
			echo '<tr class="r' . $i . '"><td class="author">';
			
			if ($author_info && $author_info['name']) {
				echo '<span class="bold">' . ($link ? ('<a href="' . $link . '">') : '') . $author_info['name'] . ($link ? '</a>' : '') . '</span><br>';
				
				if ($author_info['avatar'])
					echo '<img class="avatar" src="' . $author_info['avatar'] . '" alt="Avatar">';
				
				$posts = $model->get_author_stats($message, $total);
				
				echo '<br><span class="smaller">' . _('Posts') . ': ' . $posts .
				'<br>(' . sprintf('%02.02f', floatval($posts) / $total * 100) . '%)</span>';
			} else {
				echo 'Onbekend';
			}
			
			echo '</td>';
			echo '<td class="message">
			<div class="right">' . $message->get('datum') . '</div>
			<div class="right">';

			if ($message->editable())
				echo '<a href="forum.php?modmessage=' . $message->get('id') . '&page=' . $page . '">' . image('edit_small.png') . '</a>';
				
			if ($admin)
				echo ' <a href="forum.php?delmessage=' . $message->get('id') . '&page=' . $page . '">' . image('delete_small.png', _('verwijder'), _('Verwijder bericht')) . '</a>';
			
			
			echo ' <a href="javascript:void(0)" onclick="quote(' . $message->get('id') . ', \'' . $author_info['name'] . '\');">' . image('quote.png', _('quote'), _('Quote geselecteerde tekst van bericht')) . '</a></div>';

			echo markup_parse($message->get('message'));
			
			if ($iter->get('poll') && $message == $messages[0] && $page == 0) {
				$poll_model = get_model('DataModelPoll');
				
				echo '<div class="forum_poll">';
				run_view('poll', $poll_model, $iter);
				echo '</div>';
			}
			
			if ($author && $author->get('onderschrift'))
				echo '<div class="onderschrift">' . markup_parse($author->get('onderschrift')) . '</div>';
			
			echo '
			</td></tr>';
			
			$i = ($i ? 0 : 1);
		}
	
		echo '</table>';
		$bar = '';
	
		if ($max > 0)
			$bar .= '' . page_navigation('forum.php?thread=' . $iter->get('id'), $page, $max) . '';
		
		$canwrite = $model->check_acl($iter->get('forum'), ACL_REPLY);

		if ($canwrite)
			$bar .= '<span id="newbericht2" class="button" style="padding-left: 10px;">'.image('new.png', _('nieuw'), _('Nieuw bericht toevoegen')) . ' <span class="messageText">' . _('Nieuw antwoord') . '</span></span>'; 
		else
			$bar .= _('Je kunt niet antwoorden in dit forum');

		if ($iter->editable()) {
			$bar .= '<div class="text_right" style="padding-left: 10px;"><form method="post" action="forum.php"><br />' . input_hidden('thread_id', $iter->id) . input_hidden('submforummovethread', 'yes') . _('Verplaats bericht naar') . ': <select name="forum_id">';
			
			foreach ($model->get() as $forum) {
				if ($model->check_acl($forum->id, ACL_WRITE))
					$bar .= '<option value="' . $forum->id . '"' . ($forum->id == $iter->forum ? ' selected="selected"' : '') . '>' . $forum->name . '</option>';
			}
			
			$bar .='</select> <input class="noborder" type="image" src="' . get_theme_data('images/next.png') . '"></form></div>';
		}

		$bar .= '</div>';
		echo '<div class="bar">'.$bar;
		
		render_new_message_javascript($params, false);
	}

	function view_forum_not_found($model, $iter, $params = null) {
		echo '<h1>' . _('Forum') . '</h1>
		<div class="error_message">' . _('Dat forum bestaat niet') . '</div>';		
	}
	
	function render_new_message($authors, $parent, $subm, $params, $subject = true) {
		echo '<div id="forum_bericht" class="beheer" ">
		<form name="forum" action="forum.php" method="post">
		<div class="right"><a href="javascript:message_preview();">' . image('preview.png', _('voorbeeld'), _('Voorbeeld tonen'), 'class="button" id="message_preview"'), '</a></div>
		<table>';

		echo input_hidden($subm, 'yes');
		echo input_hidden('parent_id', $parent->get('id'));
		echo table_row(label(_('Auteur'), 'author', $params['errors'], true) . ':', select_field('author', $authors, null));
		
		if ($subject)
			echo table_row(label(_('Onderwerp'), 'subject', $params['errors'], true) . ':', input_text('subject', null, 'maxlength', '250'));

		echo '<tr><td colspan="2">
		<div id="message_content">
		' . textarea_field('message', null, $params['errors']) . '
		</div>
		<div id="message_preview_content">
		</div>
		</td></tr>';
		echo '<tr><td colspan="2" class="submit">' . input_submit('subm', _('Bericht plaatsen'), 'button', 'onClick', 'if (sending) return false; sending = true;') . '</td></tr>';
		echo '</table></form>
		</div>
		<script type="text/javascript">
			var sending = false;
		</script>';
		
		render_preview_message_javascript();
	}
	
	function render_preview_message_javascript() {
		echo '<script type="text/javascript">
			var message_preview_request = null;
			var message_loading = 0;

			function message_preview_done() {
				divpreview = document.getElementById("message_preview_content");
				message_preview_request = null;

				divpreview.innerHTML = this.get_response();
			}
			
			function message_preview_loading() {
				if (!message_preview_request) {
					message_loading = 0;
					return;
				}

				var divpreview = document.getElementById("message_preview_content");
				var text = "' . _('Bezig met laden') . '";

				if (message_loading == 4)
					message_loading = 0;
				
				for (n = 0; n < message_loading; n++)
					text += ".";
				
				message_loading++;
				
				divpreview.innerHTML = "<span class=\"bold\">" + text + "</span>";

				if (message_preview_request)
					setTimeout("message_preview_loading();", 500);
			}
			
			function message_request_preview() {
				message_preview_request = new Connection();
				message_preview_request.on_task_finished = message_preview_done;

				message_preview_request.post("forum", "forum.php?preview");
				
				message_preview_loading();
			}
			
			function message_cancel_preview() {
				if (message_preview_request) {
					message_preview_request.abort();
					message_preview_request = null;
				}
			}
			
			function message_preview() {
				var img = document.getElementById("message_preview");
				var preview;
				
				div = document.getElementById("message_content");
				divpreview = document.getElementById("message_preview_content");
				
				if (img.src.match("preview.png$")) {
					img.src = "themes/' . get_theme() . '/images/edit.png";
					img.title = "' . _('Voorbeeld sluiten') . '";
					img.alt = "' . _('voorbeeld sluiten') . '";
					
					div.style.display = "none";
					divpreview.style.display = "block";
					
					message_request_preview();
				} else {
					img.src = "themes/' . get_theme() . '/images/preview.png";
					img.title = "' . _('Voorbeeld tonen') . '";
					img.alt = "' . _('voorbeeld') . '";
					
					div.style.display = "block";
					divpreview.style.display = "none";

					message_cancel_preview();
					divpreview.innerHTML = "";
				}
			}
		</script>';	

	}
	
	function render_new_message_javascript($params, $subject = true) {
		echo '
		<script type="text/javascript">
			function nieuw_bericht() {
				div = document.getElementById("forum_bericht");
					
					window.location = "#forum_bericht";
					
					if (' . ($subject ? 'document.forum.subject.value != "" && ' : '') . 'document.forum.message.value == "")
						document.forum.message.focus();';
					
					if ($subject)
						echo '
					else
						document.forum.subject.focus();';
					
					echo '
			}
			
			function quote(id, author) {
				var str = "";

				if (document.getSelection) {
					str = document.getSelection();
				} else if (document.selection && document.selection.createRange) {
					var range = document.selection.createRange();
					str = range.text;
					document.selection.empty();
				}
				
				show_bericht();
				
				document.forum.message.value = "[quote=" + author + "]" + str + "[/quote]";
				div = document.getElementById("forum_bericht");
	
				
				document.forum.message.focus();
			}
			
			function toggle_bericht()
			{
				if ($("#forum_bericht").is(":hidden:"))
					$("#forum_bericht").show().find("textarea").focus();
				else
					$("#forum_bericht").hide();
					
				window.location = "#forum_bericht";
			}
			
			function show_bericht()
			{
				if($("#forum_bericht").is(":hidden"))
					$("#forum_bericht").show().focus();
			}
			
			$("#newbericht,#newbericht2").click(function(event)
			{
				toggle_bericht();
			});
			';
			
			if ((isset($params['errors']) && count($params['errors']) > 0) || isset($params['startadd']))
				echo "nieuw_bericht();\n";		
			
			
		echo '</script>
		';
	}
	
	function view_add_poll($model, $iter, $params = null) {
		/*echo '<h2><a href="forum.php">' . _('Forum') . '</a> :: <a href="forum.php?forum=' . $iter->get('id') . '">' . $iter->get('name') . '</a></h2>';
		echo '<h2>' . _('Nieuwe poll toevoegen') . '</h2>';
		
		$config_model = get_model('DataModelConfiguratie');
		$id = $config_model->get_value('poll_forum');
		
		if ($id && $iter->get('id') == $id) {
			/* Get the last thread *//*
			$thread = $iter->get_last_thread();
			
			if ($thread && $thread->get('since') < 14 && !member_in_commissie(COMISSIE_EASY)) {
				$num = 14 - $thread->get('since');

				echo '<div class="error_message">' . sprintf(ngettext('Je kunt hier pas over %d dag weer een poll plaatsen', 'Je kunt hier pas over %d dagen weer een poll plaatsen', $num), $num) . '</div>';
				return;
			}
		}

		echo '<p>' . _('Gebruik het onderstaande formulier om een nieuwe poll toe te voegen.') . '</p>';
		
		if (isset($params['errors']) && count($params['errors']) > 0)
			echo '<div class="error_message">' . _('Niet alle velden zijn goed ingevuld (er moet minstens 1 optie ingevuld zijn') . '</div>';

		$authors = get_authors($model, $iter->get('id'), ACL_POLL);

		echo '
		<form action="forum.php?forum=' . $iter->get('id') . '&addpoll" method="post">' . 
		input_hidden('submforumpollnieuw', 'yes') . '
		
		<table class="default_noborder">
		<tbody id="options_table">';
		
		echo table_row(label(_('Auteur'), 'author', $params['errors'], true) . ':', select_field('author', $authors, null));
		echo table_row(label(_('Onderwerp/vraag'), 'subject', $params['errors'], true) . ':', input_text('subject', null, 'maxsize', '150'));
		echo table_row(label(_('Omschrijving'), 'message', $params['errors'], true) . ':', textarea_field('message', null, $params['errors']));
		
		for ($i = 0; $i < 3; $i++)
			echo '<tr id="optie_tr_' . $i . '"><td>' . _('Optie') . ' ' . ($i + 1) . ':</td><td>' . input_text('optie_' . $i, null, 'maxlength', 150) . '</td></tr>';
		
		echo '</tbody><tr class="submit"><td class="submit" colspan="2">' . 
		
		input_button(_('Nieuwe optie'), 'add_option()') . ' ' .
		input_submit('subm', _('Opslaan'), 'button', 'onClick', 'if (sending) return false; sending = true;') . '
		
		</td></tr>
		</table></form>'; */
		echo '<h1><a href="forum.php">' . _('Forum') . '</a> :: <a href="forum.php?forum=' . $iter->get('id') . '">' . $iter->get('name') . '</a></h1>';
		echo '<div class="topbar"></div><table class="poll"><tr class="header"><td colspan="2">Nieuwe poll toevoegen.</td></tr>';
		
		$config_model = get_model('DataModelConfiguratie');
		$id = $config_model->get_value('poll_forum');
		
		if ($id && $iter->get('id') == $id) {
			/* Get the last thread */
			$thread = $iter->get_last_thread();
			
			if ($thread && $thread->get('since') < 14 && !member_in_commissie(COMISSIE_EASY)) {
				$num = 14 - $thread->get('since');

				echo '<tr><td colspan="2">' . sprintf(ngettext('Je kunt hier pas over %d dag weer een poll plaatsen', 'Je kunt hier pas over %d dagen weer een poll plaatsen', $num), $num) . '</td></tr>';
				return;
			}
		}

		echo '<tr><td colspan="2">' . _('Gebruik het onderstaande formulier om een nieuwe poll toe te voegen.') . '</td></tr>';
		
		if (isset($params['errors']) && count($params['errors']) > 0)
			echo '<tr><td colspan="2">' . _('Niet alle velden zijn goed ingevuld (er moet minstens 1 optie ingevuld zijn') . '</td></tr>';

		$authors = get_authors($model, $iter->get('id'), ACL_POLL);

		echo '
		<form action="forum.php?forum=' . $iter->get('id') . '&addpoll" method="post">' . 
		input_hidden('submforumpollnieuw', 'yes') . '
		
		<tbody id="options_table">';
		
		echo '<tr><td style="border: none;">'.label(_('Auteur'), 'author', $params['errors'], true) . ':</td><td>'. select_field('author', $authors, null).'</td></tr>';
		echo table_row(label(_('Onderwerp/vraag'), 'subject', $params['errors'], true) . ':', input_text('subject', null, 'maxsize', '150'));
		echo table_row(label(_('Omschrijving'), 'message', $params['errors'], true) . ':', textarea_field('message', null, $params['errors']));
		
		for ($i = 0; $i < 6; $i++)
			echo '<tr id="optie_tr_' . $i . '"><td>' . _('Optie') . ' ' . ($i + 1) . ':</td><td>' . input_text('optie_' . $i, null, 'maxlength', 150) . '</td></tr>';
		
		echo '</tbody></table><div class="bar" style="border-top: 1px solid #000000;"><span  style="padding-left: 10px;">' . 
		
		//input_button(_('Nieuwe optie'), 'add_option()') . ' ' .
		input_submit('subm', _('Opslaan'), 'button', 'onClick', 'if (sending) return false; sending = true;') . '</span></div></form>';
		/*
		<script type="text/javascript">
			var sending = false;
			var num_options = 3;
			var max_options = 10;

			function add_option() {
				if (num_options >= max_options) {
					alert("Het maximum aantal opties (" + max_options + ") is bereikt");
					return;
				}

				tr = document.createElement("tr");
				tr.setAttribute("id", "optie_tr_" + num_options);
				
				tr.appendChild(document.createElement("td"));
				tr.appendChild(document.createElement("td"));

				tr.childNodes[0].appendChild(document.createTextNode("' . _('Optie') . ' " + (num_options + 1) + ":"));
				
				inp = document.createElement("input");
				inp.setAttribute("type", "text");
				inp.setAttribute("class", "text");
				inp.setAttribute("name", "optie_" + num_options);
				inp.setAttribute("maxlength", "150");

				tr.childNodes[1].appendChild(inp);
				document.getElementById("options_table").appendChild(tr);

				num_options++;
			}
		</script>
		';*/
	}
	
	function get_authors($model, $forumid, $acl) {
		$authors = array();
		$member_data = logged_in();
		$authors[-1] = member_full_name();

		$commissie_model = get_model('DataModelCommissie');

		foreach ($member_data['commissies'] as $commissie) {
			if ($model->check_acl_commissie($forumid, $acl, $commissie))
				$authors[$commissie] = $commissie_model->get_naam($commissie);
		}
		
		return $authors;
	}
	
	function view_forum($model, $iter, $params = null) {
		if ($iter->get('id') == 7){
			if (!logged_in()) {
				return;
			}
		}
		echo '<h1><a href="forum.php">' . _('Forum') . '</a> :: ' . $iter->get('name') . '</h1>';
		$i = 0;
		$page = $params['page'];
		$threads = $model->get_threads($iter, $page, $max);

		$bar = '';

		if ($max > 0)
			$bar .= page_navigation('forum.php?forum=' . $iter->get('id'), $page, $max);

		$canwrite = $model->check_acl($iter->get('id'), ACL_WRITE);
		$canpoll = $model->check_acl($iter->get('id'), ACL_POLL);

		if ($canwrite)
			$bar .= '<span id="newbericht" class="button" style="padding-left: 10px;">'.image('new.png', _('nieuw'), _('Nieuw bericht toevoegen')) . ' <span class="messageText">' . _('Nieuw antwoord') . '</span></span>';
		
		if ($canpoll)
			$bar .= '<a href="forum.php?forum=' . $iter->get('id') . '&addpoll"><span id="newpoll" class="button">' . image('new.png', _('nieuw'), _('Nieuwe poll toevoegen')) . _('Nieuwe poll') . '</span></a>';
		if (!$canpoll && !$canwrite)
			$bar .= _('Je kunt niet schrijven in dit forum');
			
		$bar .= '</div>';
		
		echo '<div class="topbar">'.$bar ;

		if ($canwrite) {
			$authors = get_authors($model, $iter->get('id'), ACL_WRITE);
			render_new_message($authors, $iter, 'submforumnewthread', $params);
		}

		echo '<table class="forum">
			<tr class="header">
			<td colspan="2">' . _('Onderwerp') . '</td>
			<td class="text_center">' . _('Auteur') . '</td>
			<td>' . _('Reacties') . '</td>
			<td class="text_center">' . _('Laatste') . '</td>
		</tr>';
		
		$member_model = get_model('DataModelMember');
		
		foreach ($threads as $thread) {
			echo '<tr class="r' . $i . '"><td class="icon">' . image($model->thread_unread($thread->get('id')) ? 'thread_new.png' : 'thread.png', '', '') . '</td>
			<td class="subject"><a href="forum.php?thread=' . $thread->get('id') . '"><span class="subject">' . ($thread->get('poll') ? ('[' . _('Poll') . '] ') : '') . $thread->get('subject') . '</span></a>';
			
			$pages = $thread->get_num_thread_pages();
			$i = ($i ? 0 : 1);
			
			if ($pages > 1) {
				$nav = array();
				$link = '<a href="forum.php?thread=' . $thread->get('id');

				for ($p = 0; $p < $pages; $p++)
					$nav[] = $link . '&page=' . $p . '">' . ($p + 1) . '</a>';
				
				echo '<br><span class="smaller">[ ' . _('Pagina') . ': ' . implode(', ', $nav) . ' ]</span>';
			}
			
			$link = get_author_link($thread);
			$author_info = $model->get_author_info($thread);

			echo '</td>
			<td class="text_center">' . ($author_info['name'] ? ('<a href="' . $link .  '">' . $author_info['name'] . '</a>') : _('Onbekend')) . '</td>
			<td class="text_center">' . $thread->get_num_messages() . '</td>
			<td class="last">';

			if ($thread->get('datum')) {
				echo $thread->get('datum');
				$lastid = $thread->get('last_id');

				if ($author_info['last_name'] || $author_info['name'])
					echo '<br><a href="forum.php?thread=' . $thread->get('id') . '&page=' . ($pages - 1) . '#' . ($lastid == $thread->get('id') ? 't' : 'p') . $lastid . '">' . ($author_info['last_name'] ? $author_info['last_name'] : $author_info['name']) . '</a>';
			}

			echo '</td>
			</tr>';
		}
		
		echo '</table>'; 
		$bar = '';

		if ($max > 0)
			$bar .= '<div class="right">' . page_navigation('forum.php?forum=' . $iter->get('id'), $page, $max) . '</div>';

		$bar .= '</div>';
		echo '<div class="bar">'.$bar;
		
		if ($canwrite)
			render_new_message_javascript($params);
	}

	function view_fora($model, $iters, $params = null) {
		echo '<h1>' . _('Forum') . '</h1>
		<div class="topbar"></div><table class="forum">';
		$i=0;
		$headers = $model->get_headers();

		foreach ($iters as $iter) {
			$lastheader = null;

			while (count($headers) > 0 && $iter->get('position') > $headers[0]->get('position')) {
				$lastheader = $headers[0];
				array_shift($headers);
			}
			
			if ($lastheader) {
				echo '<tr class="forum_header"><td colspan="5">' . $lastheader->get('name') . '</td></tr>';
				$i=0;
			}
			if (!($iter->get('id') == 7 && !logged_in())){
			$num_threads = $iter->get_num_threads();
			$num_messages = $iter->get_num_forum_messages();

			echo '<tr class="r' . $i . '"><td class="icon">' . image($model->forum_unread($iter->get('id')) ? 'thread_new.png' : 'thread.png', '', '') . '</td>
			<td class="forum"><span class="bold"><a href="forum.php?forum=' . $iter->get('id') . '">' . $iter->get('name') . '</a></span><div class="smaller">' . markup_parse($iter->get('description')) . '</div></td>
			<td class="text_center">' . $num_threads . '</td>
			<td class="text_center">' . $num_messages . '</td>
			<td class="last">';
			$i = ($i ? 0 : 1);
			$last = $iter->get_last_thread();
			
			if ($last)
				echo $last->get('datum') . '<br><a href="forum.php?thread=' . $last->get('id') . '">' . $last->get('subject') . '</a>';
			
			echo '</td></tr>';
			}
		}

		echo '</table><div class="bar"></div>';
	}
	
	/* Admin stuff */
	function render_admin_menu($sub) {
		$menu = array('forums' => _('Forums'), 'rights' => _('Rechten'), 'groups' => _('Groepen'), 'special' => ('Speciale forums'));
		echo '<div class="admin_menu">
		<ul>';
		
		foreach ($menu as $id => $name)
			echo '<li' . ($sub == $id ? ' class="selected"' : '') . '><a href="forum.php?admin=' . $id . '">' . $name . '</a></li>';
		
		echo '</ul>
		</div>';
	}
	
	function render_admin_forums($model, $params) {
		echo '<h2>' . _('Volgorde') . '</h2>';
		
		$forums = $model->get(false);
		$headers = $model->get_headers();
		$values = array();
		
		foreach ($forums as $forum) {
			while (count($headers) > 0 && $forum->get('position') > $headers[0]->get('position')) {
				$values[-1 * $headers[0]->get('id')] = ' --- ' . $headers[0]->get('name') . ' --- ';
				array_shift($headers);
			}
			
			$values[$forum->get('id')] = $forum->get('name');
		}
		
		echo '<form name="forums" method="post" action="forum.php" onSubmit="javascript:fill_forum_order();">
		' . input_hidden('forum_order', '') . 
		input_hidden('submforumorder', 'yes') . '
		<table>
			<tr><td>
			' . select_field('forums', $values, null, 'size', '10', 'class', 'forum_list', 'onChange', 'javascript:select_current()', 'onKeyPress', 'javascript:on_key_down(event)') . '
			</td>
			<td>
			<a href="javascript:move_up();">' . image('up.png', _('Omhoog'), _('Verplaats geselecteerd forum naar boven')) . '</a><br><br>' . '<a href="javascript:move_down();">' . image('down.png', _('Omlaag'), _('Verplaats geselecteerd forum naar beneden'))  . '</a>
			</td>
			</tr>
			<tr><td colspan="2">' . input_text('hname', null, 'id', 'header_name') . ' ' . input_button(_('Toevoegen'), 'add_header()') . ' ' . input_button(_('Wijzigen'), 'modify_header()') . ' ' . input_button(_('Verwijderen'), 'javascript:delete_header()') . '</td></tr>
			<tr class="submit"><td class="submit" colspan="2">' . input_submit('subm', _('Opslaan')) . '</td></tr>
		</table>
		</form>
		<script type="text/javascript">
			function get_option_text(option) {
				if (option.value.substr(0, 1) == "-") {
					return option.text.substring(4, option.text.length - 4);
				} else {
					return option.text;
				}
			}
			
			function on_key_down(event) {
				if (!event)
					event = window.event;
					
				var code;
				
				if (event.keyCode)
					code = event.keyCode;
				else if (event.which)
					code = event.which;
				else
					return true;
				
				if (!event.shiftKey)
					return true;

				switch (code) {
					case 38:
						move_up();
					break;
					case 40:
						move_down();
					break;
					case 36:
					break;
						move_home();
					case 35:
						move_end();
					break;
				}

				return false;				
			}
			
			function fill_forum_order() {
				elem = document.forums.forum_order;
				sel = document.forums.forums;

				forums = new Array();

				for (i = 0; i < sel.options.length; i++)
					forums.push(sel.options[i].value + "=" + get_option_text(sel.options[i]));
				
				elem.value = forums.join(";");
				
				return true;
			}
			
			function select_current() {
				var sel = document.forums.forums;
				var s;

				if (sel.selectedIndex == -1 || sel.options[sel.selectedIndex].value.substr(0, 1) != "-")
					s = "";
				else 
					s = get_option_text(sel.options[sel.selectedIndex]);
				
				document.forums.hname.value = s;
			}
			
			select_current();
			var newindex;
			
			function timeout_handler() {
				var sel = document.forums.forums;

				sel.options[newindex].selected = true;
			}
			
			function move_forum(direction) {
				var sel = document.forums.forums;
				
				index = sel.selectedIndex;
				newindex = index + direction;

				if (newindex < 0)
					return;
				
				if (newindex >= sel.options.length)
					return;
				
				/* Switch index with newindex */
				value = sel.options[newindex].value;
				text = sel.options[newindex].text;

				sel.options[newindex].value = sel.options[index].value;
				sel.options[newindex].text = sel.options[index].text;

				sel.options[index].value = value;
				sel.options[index].text = text;	
				
				setTimeout("timeout_handler()", 0);	
			}
			
			function move_end() {
				var sel = document.forums.forums;
				move_forum(sel.options.length - sel.selectedIndex - 1);
			}
			
			function move_home() {
				var sel = document.forums.forums;
				move_forum(sel.selectedIndex - 1);
			}
			
			function move_up() {
				move_forum(-1);
			}
			
			function move_down() {
				move_forum(1);				
			}
			
			function modify_header() {
				var s = document.forums.hname.value.replace(";", "");

				if (s == "")
					return;

				sel = document.forums.forums;
				
				if (sel.selectedIndex == -1 || sel.options[sel.selectedIndex].value.substr(0, 1) != "-")
					return;
				
				sel.options[sel.selectedIndex].text = " --- " + s + " --- ";
			}
			
			function delete_header() {
				sel = document.forums.forums;
				
				if (sel.selectedIndex == -1 || sel.options[sel.selectedIndex].value.substr(0, 1) != "-")
					return;
				
				sel.options[sel.selectedIndex] = null;
				select_current();
			}
			
			function add_header() {
				var s = document.forums.hname.value.replace(";", "");

				if (s == "")
					return;

				sel = document.forums.forums;
				sel.options[sel.options.length] = new Option(" --- " + s + " --- ", "-");
				
				document.forums.hname.value = "";
			}
		</script>
		';
		
		echo '<div class="bar"><div class="right"><a href="javascript:nieuw_forum()">' . image('new.png', _('nieuw'), _('Nieuw forum toevoegen'), 'class="button"') . '</a> <a href="javascript:nieuw_forum()">' . _('Nieuw forum') . '</a></div><h2>' . _('Forums') . '</h2></div>

		<div id="forum_nieuw" class="beheer">
		<form name="forum" action="forum.php" method="post">
		<table>';

		echo input_hidden('submforumnieuw', 'yes');
		echo table_row(label(_('Naam'), 'name', $params['errors'], true) . ':', input_text('name', null));
		echo '<tr><td colspan="2">' . label(_('Beschrijving'), 'description', $params['errors'], true) . ':<br>' . textarea_field('description', null, $params['errors'], 'class', 'small') . '</td></tr>';
		echo '<tr><td colspan="2" class="submit">' . input_submit('subm', _('Forum toevoegen')) . '</td></tr>';
		echo '</table></form>
		</div>	
		<form action="forum.php" method="post">
		' . input_hidden('submforumforums', 'yes') . '
		<table class="moderate">
			<tr class="header">
				<td>' . image('delete_small.png', 'D', _('Verwijder geselecteerde forums')) . '</td>
				<td>' . _('Forum') . '</td>
			</tr>';
		
		$i = 0;
		
		foreach ($forums as $forum) {
			echo '<tr class="r' . $i . '">
				<td>' . input_checkbox('del_' . $forum->get('id'), null) . '</td>
				<td>' . input_text('name_' . $forum->get('id'), $forum->data, 'field', 'name') . '<br>' . textarea_field('description_' . $forum->get('id'), $forum->data, $params['errors'], 'field', 'description', 'class', 'small') . '</td>';
				
			echo '</tr>';
			
			$i = ($i ? 0 : 1);
		}
		
		echo '<tr><td class="submit" colspan="2">' . input_submit('subm', _('Opslaan')) . '</td></tr>';
		
		echo '</table></form>
		<script type="text/javascript">
			function nieuw_forum() {
				div = document.getElementById("forum_nieuw");
				
				if (div.style.display == "none" || div.style.display == "") {
					div.style.display = "block";
					
					if (document.forum.name.value != "" && document.forum.description.value == "")
						document.forum.description.focus();
					else
						document.forum.name.focus();
				} else {
					div.style.display = "none";
				}
			}
			';
			
			if (isset($params['errors']['name']) || isset($params['errors']['description']))
				echo "nieuw_forum();\n";		
			
		echo '</script>
		';
	}
	
	function render_member_selection($model, $show_groups) {
		$types = array(-1 => _('Iedereen'), 1 => _('Lid'), 2 => _('Commissie'));
		
		if ($show_groups)
			$types[3] = _('Groep');

		echo '<table class="default">';
		
		if (!$show_groups) {
			$groups = $model->get_groups();
			$values = array();

			foreach ($groups as $group)
				$values[$group->get('id')] = $group->get('name');
			
			echo '<tr>
				<td>' . label(_('Groep'), 'guid', null, true) . ':</td>
				<td>' . select_field('guid', $values, null) . '</td>
				</tr>';
		}
		
		echo '<tr>
				<td class="small">' . label(_('Type'), 'type', null, true) . ':</td>
				<td>' . select_field('type', $types, null, 'onChange', 'javascript:type_changed()') . '</td>
			</tr>
			<tr>
				<td>' . label(_('Naam'), 'name', null, true) . ':</td>
				<td>
					<div class="hidden" id="name_everyone">
						' . _('Iedereen') . '
					</div>
					<div class="hidden" id="name_member">
						' .
					select_field('member', array(0 => '[' . _('Zoeken') . ' &gt;&gt;]'), null), '<a href="javascript:zoek_leden();">' . image('previous.png', '<<', _('Zoeken naar leden'), 'class="middle"') . '</a> ' . input_text('voornaam', null, 'class', 'small', 'onKeyPress', 'return search_key_down(event)') . '
					</div>
					<div class="hidden" id="name_commissie">
						';
					
					$commissie_model = get_model('DataModelCommissie');
					$commissies = $commissie_model->get();
					$values = array(-1 => 'Alle commissies');
					
					foreach ($commissies as $commissie)
						$values[$commissie->get('id')] = $commissie->get('naam');
					
					echo select_field('commissie', $values, null) . '</div>';
					
					if ($show_groups) {
						echo '<div class="hidden" id="name_group">';
						
						$groups = $model->get_groups();
						$values = array(-1 => 'Alle groepen');
						
						foreach ($groups as $group)
							$values[$group->get('id')] = $group->get('name');

						echo select_field('group', $values, null) . '
						</div>';
					}
					
					echo '
				</td>
			</tr>';
			
			if ($show_groups) {
				echo '<tr>
					<td>' . label(_('Rechten'), 'rights', null, false) . ':</td>
					<td>' . input_checkbox('read', array('read' => 'yes')) . ' ' . _('Lezen') . '<br>
					' .	input_checkbox('write', null) . ' ' . _('Schrijven') . '<br>
					' .	input_checkbox('reply', null) . ' ' . _('Antwoorden') . '<br>
					' . 	input_checkbox('poll', null) . ' ' . _('Poll') . '</td>
				</tr>';
			}
			
			echo '<tr><td class="submit" colspan="2">' . input_submit('subm', $show_groups ? _('Rechten toevoegen') : 'Lid toevoegen') . '</td></tr>
		</table>
		</form>
		<script type="text/javascript">
			function type_changed() {
				var sel = document.fforum.type;
				var divs = Array(document.getElementById("name_everyone"), document.getElementById("name_member"), document.getElementById("name_commissie")' . ($show_groups ? ', document.getElementById("name_group")' : '') . ');
				
				for (i = 0; i < divs.length; i++)
					divs[i].style.display = "none";
				
				divs[sel.value == -1 ? 0 : sel.value].style.display = "block";
			}
			
			type_changed();

			var search_member_request = null;
			var idle_timeout = 0;

			function delete_all_names() {
				var sel = document.fforum.member;
				
				for (i = 0; i < sel.options.length; i++)
					sel.options[i] = null;
			}

			function on_search_member_done() {
				var values = this.get_response();
				var sel = document.fforum.member;

				delete_all_names();
				
				if (values == "") {
					sel.options[sel.options.length] = new Option("[' . _('Geen leden gevonden') . ']", "0");
				} else {
					values = values.split("\n");
					
					for (i = 0; i < values.length; i++) {
						opt = values[i].split("\t");
						
						sel.options[i] = new Option(opt[1] + " (#" + opt[0] + ")", opt[0]);
					}
				}
				
				search_member_request = null;
			}

			function zoek_leden_idle() {
				var query = document.fforum.voornaam.value;
				idle_timeout = 0;
				
				if (query.length >= 3)
					zoek_leden();
			}
			
			function zoek_leden() {
				if (search_member_request != null)
					search_member_request.abort();
				
				var query = document.fforum.voornaam.value;
				
				if (query == "") {
					alert("' . _('Je moet wel een naam invullen') . '");
					return;
				} else if (query.length < 3) {
					alert("' . _('Geef minstens 3 letters op om naar te zoeken') . '");
					return;
				}
				
				search_member_request = new Connection();
				search_member_request.on_task_finished = on_search_member_done;
				
				search_member_request.get("actieveleden.php?search_members=" + encodeURIComponent(query));
			}
			
			function search_key_down(event) {
				if (!event)
					event = window.event;
					
				var code;
				
				if (event.keyCode)
					code = event.keyCode;
				else if (event.which)
					code = event.which;
				else
					return true;
				
				if (code == 13) {
					zoek_leden();
					return false;
				} else {
					if (code >= 32 && code <= 126) {
						if (idle_timeout != 0)
							clearTimeout(idle_timeout);

						idle_timeout = setTimeout("zoek_leden_idle()", 1000);
					}

					return true;
				}
			}
		</script>';
	}
	
	function render_admin_rights_forum($model, $forum, $params) {
		echo '<h2>' . _('Rechten') . ' :: ' . $forum->get('name') . '</h2>
		<div class="smaller">' . markup_parse($forum->get('description')) . '</div><br>
		<form method="post" action="forum.php?admin=rights&forum=' . $forum->get('id') . '">
		' . input_hidden('submforumrights', 'yes') . '
		<table class="moderate">
			<tr class="header">
				<td>' . image('delete_small.png', 'D', _('Verwijder geselecteerde rechten')) . '</td>
				<td>' . _('Type') . '</td>
				<td>' . _('Naam') . '</td>
				<td>' . _('Lees') . '</td>
				<td>' . _('Schrijf') . '</td>
				<td>' . _('Antw.') . '</td>
				<td>' . _('Poll') . '</td>
			</tr>';
		
		$rights = $forum->get_rights();
		$acls = $model->get_acls();

		foreach ($rights as $right) {
			$id = $right->get('id');

			echo '<tr>
				<td>' . input_hidden('right_' . $id, 'yes') . input_checkbox('del_' . $id, null) . '</td>
				<td>' . $model->get_acl_type($right) . '</td>
				<td>' . $model->get_acl_name($right) . '</td>';
			
			$perms = intval($right->get('permissions'));
			$i = 0;

			foreach ($acls as $acl) {
				echo '<td>' . input_checkbox('acl_' . $id . '_' . $i, (($perms & $acl) ? array('acl_' . $id . '_' . $i => 'yes') : null)) . '</td>';
				$i++;
			}
			
			echo '</tr>';
		}
		
		echo '<tr><td colspan="7" class="submit">' . input_submit('subm', _('Opslaan')) . '</td></tr>
		</table></form>';
		
		echo '<h2>' . _('Rechten toevoegen') . '</h2>
		<form name="fforum" action="forum.php?admin=rights&forum=' . $forum->get('id') . '" method="post">
		' . input_hidden('submforumrightsnieuw', 'yes');
		
		render_member_selection($model, true);
	}
	
	function get_forum_values($model) {
		$forums = $model->get(false);
		$values = array();
		
		foreach ($forums as $f)
			$values[$f->get('id')] = $f->get('name');
		
		return $values;		
	}
	
	function render_admin_forum_selection($model, $forum) {
		$values = get_forum_values($model);
		
		echo '<form action="forum.php" method="get">' . input_hidden('admin', 'rights') .
		_('Selecteer forum') . ': ' . select_field('forum', $values, $forum ? array('forum' => $forum->get('id')) : null, 'onChange', 'javascript:submit()') .
		' ' . input_submit('', _('Selecteren')) . '</form>';
	}
	
	function render_admin_rights($model, $forum, $params) {
		echo '<h2>' . _('Rechten') . '</h2>';		
		render_admin_forum_selection($model, $forum);

		if ($forum)
			render_admin_rights_forum($model, $forum, $params);
	}
	
	function render_admin_groups($model, $params) {
		echo '<h2>' . _('Groepen') . '</h2>';
		
		$groups = $model->get_groups();
		
		if (count($groups) > 0) {
			echo '
			<form method="post" action="forum.php?admin=groups">
			' . input_hidden('submforumgroups', 'yes') . '
			<table class="moderate">
				<tr class="header">
					<td class="delete">' . image('delete_small.png', 'D', _('Verwijder geselecteerde groepen')) . '</td>
					<td>' . _('Naam') . '</td>
					<td>' . _('Leden') . '</td>
				</tr>';

			foreach ($groups as $group) {
				echo '<tr>
					<td>' . input_hidden('group_' . $group->get('id'), 'yes') . input_checkbox('del_' . $group->get('id'), null) . '</td>
					<td>' . input_text('name_' . $group->get('id'), $group->data, 'field', 'name', 'errors', $params['errors']) . '</td>
					<td class="text_right">';
				
				$members = $model->get_group_members($group->get('id'));
				$mems = array();

				foreach ($members as $member) {
					$mems[] = $model->get_acl_name($member) . ' (<a href="forum.php?admin=groups&delmember=' . $member->get('id') . '">' . _('verwijder') . '</a>)';
				}
				
				echo implode('<br>', $mems);
				
				echo '</td>
				</tr>';
			}
			
			echo '<tr><td colspan="3" class="submit">' . input_submit('subm', _('Opslaan')) . '</td></tr>
			</table></form>';
		} else {
			echo '<div>' . _('Er zijn nog geen groepen') . '</div>';
		}
		
		echo '<h2>' . _('Nieuwe groep') . '</h2>
		<form method="post" action="forum.php?admin=groups">
		' . input_hidden('submforumgroupsnieuw', 'yes') . '
		<table class="default">
			<tr>
				<td class="small">' . label(_('Naam'), 'name', $params['errors'], true) . ':</td>
				<td>' . input_text('name', null, 'errors', $params['errors']) . '</td>
			</tr>
			<tr>
				<td colspan="2" class="submit">' . input_submit('subm', _('Groep toevoegen')) . '</td>
			</tr>
		</table>
		</form>';

		if (count($groups) == 0)
			return;
		
		echo '<h2>' . _('Leden toevoegen') . '</h2>
		<form name="fforum" method="post" action="forum.php?admin=groups">
		' . input_hidden('submforumgroupsmembers', 'yes');
		
		render_member_selection($model, false);
	}
	
	function render_admin_special($model, $params) {
		echo '<h2>' . _('Speciale forums') . '</h2>';
		
		$values = get_forum_values($model);
		$values = array(0 => 'Geen') + $values;
		$special = array('poll' => _('Polls'), 'news' => _('Mededelingen'), 'weblog' => _('Weblog'));
		$config_model = get_model('DataModelConfiguratie');

		echo '<form action="forum.php?admin=special" method="post">' . input_hidden('submforumspecial', 'yes') . '
		<table class="default">';
		
		foreach ($special as $key => $name) {
			$value = $config_model->get_value($key . '_forum');
			
			if ($value === null)
				continue;

			echo '<tr>
				<td class="small">' . $name . ':</td>
				<td>' . select_field($key, $values, array($key => intval($value))) . '</td>
				</tr>';
		}
		
		echo '<tr><td class="submit" colspan="2">' . input_submit('subm', _('Opslaan')) . '</td></tr>
		</table>';
	}
	
	function view_admin($model, $iter, $params = null) {
		echo '<h1>' . _('Forum') . ' :: ' . _('Admin') . '</h1>
			<div class="messageBox">';
		render_admin_menu($params['sub']);
		
		switch ($params['sub']) {
			case 'forums':
				render_admin_forums($model, $params);
			break;
			case 'rights':
				render_admin_rights($model, $iter, $params);
			break;
			case 'groups':
				render_admin_groups($model, $params);
			break;
			case 'special':
				render_admin_special($model, $params);
			break;
		}
		echo '</div>';
	}
	
	function view_mod_message($model, $iter, $params = null) {
		echo '<div class="right"><a href="javascript:message_preview();">' . image('preview.png', _('voorbeeld'), _('Voorbeeld tonen'), 'class="button" id="message_preview"'), '</a></div>
		<h1>' . _('Forum') . ' :: ' . _('Wijzig bericht') . '</h1>
		<form name="forum" action="forum.php" method="post">
		<table class="forum">';

		echo input_hidden('submforummodmessage', 'yes');
		echo input_hidden('message_id', $iter->id);
		echo input_hidden('page', $params['page']);

		$author = $model->get_author_info($iter);
		echo table_row(label(_('Auteur'), '', null, true) . ':', $author['name']);
		echo table_row(label(_('Datum'), '', null, true) . ':', $iter->datum);
		
		if ($iter->is_first_message())
		{
			$thread = $model->get_thread($iter->thread);
			echo table_row(label(_('Onderwerp'), 'subject', $params['errors'], true) . ':', input_text('subject', $thread->data, 'maxlength', '250'));
		}

		echo '<tr><td colspan="2">
		<div id="message_content">
		' . textarea_field('message', $iter->data, $params['errors']) . '
		</div>
		<div id="message_preview_content">
		</div>
		</td></tr>';
		echo '<tr><td colspan="2" class="submit">' . input_submit('subm', _('Bericht wijzigen'), 'button', 'onClick', 'if (sending) return false; sending = true;') . '</td></tr>';
		echo '</table></form>
		<script type="text/javascript">
			var sending = false;
		</script>';	
		
		render_preview_message_javascript();
	}
	
	function view_del_message($model, $iter, $params = null) {
		echo '<h1>' . _('Forum') . ' :: ' . _('Bericht verwijderen') . '</h1>
		<div class="error_message">Weet je zeker dat je dit bericht wilt verwijderen?';
		
		if ($iter->is_first_message())
			echo ' Let op! Als je dit bericht verwijderd wordt de het hele topic verwijderd!!!';
		
		echo '</div>
		<form method="post">
			' . input_hidden('submforumdelmessage', 'yes') . '
			' . input_hidden('message_id', $iter->id) . '
			' . input_hidden('page', $params['page']) . '
		<table class="full">
			<tr>
				<td>' . input_button('Nee', 'history.go(-1)') . '</td>
				<td class="right">' . input_submit('subm', 'Ja') . '</td>
			</tr>
		</table>
		</form>';
	}
	
	function view_preview($model, $iter, $params = null) {
		echo markup_clean(markup_parse($params['message']));
	}
?>
