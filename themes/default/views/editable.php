<?php
	require_once('include/editable.php');
	require_once('form.php');
	require_once('markup.php');
	require_once('member.php');

	function echo_editable_page($iter, $page) {
		if (member_in_commissie($iter->get('owner')) || member_in_commissie(COMMISSIE_BESTUUR)) {
			$link = '<a href="' . add_request(get_request(), 'editable_edit=' . $iter->get('id')) . '">' . image('edit.png', __('bewerken'), __('Pagina bewerken')) . '</a>';
			
			//if title with markup <h1>Title</h1> is found in page
			if (preg_match('/^\<h1\>(.*?)\<\/h1\>/i', $page)) {
				//replace the <h1> tag with a bar_header div and editable button, echo the resulting page
				echo preg_replace('/^\<h1\>(.*?)\<\/h1\>/i', '<div class="pageheading"><div class="controls inline">' . $link . '</div><h1>$1</h1></div><div class="messageBox">', $page);
				echo '</div>';
			} else {
				//title is not found, echo the page as a whole
				echo '<div class="bar"><div class="text_right">' . $link . '</div></div>';
				echo '<div class="messageBox">'.$page.'</div>';
			}
		} else {
			//member is not authorised to edit the page, echo the page as a whole
			
			//if title with markup <h1> is found, place outside the messageBox class
			if (preg_match('/^\<h1\>(.*?)\<\/h1\>/i', $page)) {
				echo preg_replace('/^\<h1\>(.*?)\<\/h1\>/i', '<h1>$1</h1><div class="messageBox">', $page);
				echo '</div>';
			} else {
				//echo the page as a whole, inside the messageBox
				echo '<div class="messageBox">'.$page.'</div>';
			}
		}
	}
	
	function view_something_went_wrong($model, $iter, $params = null) {
		echo '<h1>' . __('Fout') . '</h1>
		<div class="error_message">' . $params['message'] . '</div>';
	}

	function view_editable($model, $iter, $params = null) {
		if (!$iter) {
			echo '<span class="error">' . __('Deze pagina bestaat niet') . '</span>';
			return;
		}

		$pagenr = $params['pagenr'];
		$pages = $params['pages'];
		$page = $pages[$pagenr];

		$self = get_request('editable_pagenr');
		echo_editable_page($iter, $page);
		if (count($pages) == 1)
			return;

		echo '<hr/>
		<div class="text_center">' . ($pagenr != 0 ? ('<a href="' . add_request($self, 'editable_pagenr=' . ($pagenr - 1)) . '">' . image('previous.png', __('vorige'), __('Ga naar de vorige pagina'), 'class="bottom"') . '</a> ') : '');

		for ($i = 0; $i < count($pages); $i++) {
			if ($i == $pagenr)
				echo "<b>" . ($i + 1) . "</b> ";
			else
				echo '<a href="' . add_request($self, 'editable_pagenr=' . $i) . '">' . ($i + 1) . '</a> ';
		}

		echo ($pagenr != count($pages) - 1 ? ('<a href="' . add_request($self, 'editable_pagenr=' . ($pagenr + 1)) . '">' . image('next.png', __('volgende'), __('Ga naar de volgende pagina'), 'class="bottom"')) : '') . '</div>';
	}

	function view_read_only($model, $iter, $params = null) {
		echo '<h1>' . $iter->get('titel') . '</h1>
		<p><span class="error">' . __('Deze pagina kan niet door jou worden bewerkt.') . '</span></p>';
	}	

	function view_edit($model, $iter, $params = null) {
		$self = get_request('editable_edit');

		echo '<h1>' . __('Bewerken') . ' - ' . $iter->get('titel') . '</h1>';
		echo '<div class="messageBox">';
		echo '<div class="control-bar">
			<a href="' . $self . '" class="right">' . image('close.png', __('sluiten'), __('Sluiten'), 'class="top"') . '</a>
			<a href="' . add_request($self, 'editable_add=' . $iter->get('id') . '&editable_language=' . $params['language']) . '">' . image('new.png', __('nieuw'), __('Voeg pagina toe na deze pagina'), 'class="button"') . '</a>
			<a href="javascript:submit_form(\'editable\', true);">' . image('save.png',  __('opslaan'), __('Sla pagina op'), 'class="button"') . '</a>
			<a href="javascript:reset_form(\'editable\');">' . image('revert.png', __('herstellen'), __('Herstel pagina'), 'class="button"') . '</a>
			<a href="javascript:editable_preview();">' . image('preview.png', __('voorbeeld'), __('Voorbeeld tonen'), 'class="button" id="editable_preview"'), '</a>
			<a href="' . add_request($self, 'editable_del&editable_language=' . $params['language']) . '">' . image('delete.png', __('verwijderen'), __('Verwijder pagina'), 'class="button"'), '</a>
			<form name="editable_language" action="' . get_request() . '" method="post" class="inline">
				' . __('Taal') . ': ' . select_field('editable_language', i18n_get_languages(), array('editable_language' => $params['language']), 'onChange', 'javascript:change_language();') . '
			</form>
		</div>
		<div id="editable_content">
		';
		
		if (!isset($_GET['editable_pagenr']))
			$pagenr = 0;
		else
			$pagenr = intval($_GET['editable_pagenr']);

		if (!in_array('content_' . $params['language'], array_keys($iter->data)))
			$field = 'content';
		else
			$field = 'content_' . $params['language'];
		
		$pages = editable_split_pages($iter->get($field));
		$pagenr = max(0, min(count($pages) - 1, $pagenr));
		$content = $pages[$pagenr];

		echo '<form name="editable" method="post" action="' . $self . '"><p>';
		echo input_hidden('editable_id', $iter->get_id());
		echo input_hidden('submeditable', $iter->get_id());
		echo input_hidden('editable_pagenr', $pagenr);
		echo input_hidden('editable_language', $params['language']);
		
		echo textarea_field($field, array($field => $content), null, 'class', 'editable', 'formatter', 'markup_format_text');
		
		echo '</p></form>';

		if (count($pages) != 1) {
			echo '<div class="text_center">[ ';
			$self = get_request('editable_pagenr');			

			for ($i = 0; $i < count($pages); $i++) {
				if ($i == $pagenr)
					echo "<b>" . ($i + 1) . "</b> ";
				else
					echo '<a href="' . add_request($self, 'editable_pagenr=' . $i . '&editable_language=' . $params['language']) . '">' . ($i + 1) . '</a> ';
			}
			
			echo ']</div>';
		}
		
		echo '</div>
		<div class="editable_preview" id="editable_preview_content">
		</div>
		</div>
		
		<script type="text/javascript">
			var editable_preview_request = null;
			var editable_loading = 0;

			function editable_preview_done() {
				divpreview = document.getElementById("editable_preview_content");
				editable_preview_request = null;

				divpreview.innerHTML = this.get_response();
			}
			
			function editable_preview_loading() {
				if (!editable_preview_request) {
					editable_loading = 0;
					return;
				}

				var divpreview = document.getElementById("editable_preview_content");
				var text = "' . __('Bezig met laden') . '";

				if (editable_loading == 4)
					editable_loading = 0;
				
				for (n = 0; n < editable_loading; n++)
					text += ".";
				
				editable_loading++;
				
				divpreview.innerHTML = "<span class=\"bold\">" + text + "</span>";

				if (editable_preview_request)
					setTimeout("editable_preview_loading();", 500);
			}
			
			function editable_request_preview() {
				editable_preview_request = new Connection();
				editable_preview_request.on_task_finished = editable_preview_done;

				editable_preview_request.post("editable", "show.php?preview");
				
				editable_preview_loading();
			}
			
			function editable_cancel_preview() {
				if (editable_preview_request) {
					editable_preview_request.abort();
					editable_preview_request = null;
				}
			}
			
			function editable_preview() {
				var img = document.getElementById("editable_preview");
				var preview;
				
				div = document.getElementById("editable_content");
				divpreview = document.getElementById("editable_preview_content");
				
				if (img.src.match("preview.png$")) {
					img.src = "themes/' . get_theme() . '/images/edit.png";
					img.title = "' . __('Voorbeeld sluiten') . '";
					img.alt = "' . __('voorbeeld sluiten') . '";
					
					div.style.display = "none";
					divpreview.style.display = "block";
					
					editable_request_preview();
				} else {
					img.src = "themes/' . get_theme() . '/images/preview.png";
					img.title = "' . __('Voorbeeld tonen') . '";
					img.alt = "' . __('voorbeeld') . '";
					
					div.style.display = "block";
					divpreview.style.display = "none";

					editable_cancel_preview();
					divpreview.innerHTML = "";
				}
			}
			
			function change_language() {
				document.editable_language.submit();				
			}
		</script>';
	}
?>
