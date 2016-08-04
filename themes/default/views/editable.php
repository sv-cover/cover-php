<?php
require_once 'include/editable.php';
require_once 'include/form.php';
require_once 'include/markup.php';
require_once 'include/member.php';

class EditableView extends View
{
	public function render_editable(DataIterEditable $iter, $params = null)
	{
		if (!$iter)
			return '<span class="error">' . __('Deze pagina bestaat niet') . '</span>';

		// Remove unnecessary breaks from the beginning of the page.
		return preg_replace('/^(\<br\/?\>\s*)+/i', '', $params['page']);
	}

	public function render_read_only(DataIterEditable $iter, $params = null)
	{
		return '<h1>' . $iter->get('titel') . '</h1>
		<p><span class="error">' . __('Deze pagina kan niet door jou worden bewerkt.') . '</span></p>';
	}

	public function render_edit(DataIterEditable $iter, $params = null) {
		$self = get_request('editable_edit');

		ob_start();

		echo '
		<div class="contenteditable" id="editable' . $iter->get('id') . '">
		<div class="control-bar">
			<a href="' . $self . '" class="right">' . image('close.png', __('sluiten'), __('Sluiten'), 'class="top"') . '</a>
			<a href="javascript:submit_form(\'editable\', true);">' . image('save.png',  __('opslaan'), __('Sla pagina op'), 'class="button"') . '</a>
			<a href="javascript:reset_form(\'editable\');">' . image('revert.png', __('herstellen'), __('Herstel pagina'), 'class="button"') . '</a>
			<a href="javascript:editable_preview();">' . image('preview.png', __('voorbeeld'), __('Voorbeeld tonen'), 'class="button" id="editable_preview"'), '</a>
			<form name="editable_language" action="' . get_request() . '" method="post" class="inline">
				' . __('Taal') . ': ' . select_field('editable_language', i18n_get_languages(), array('editable_language' => $params['language']), 'onChange', 'javascript:change_language();') . '
			</form>
		</div>
		<div id="editable_content">
		';
		
		if (!in_array('content_' . $params['language'], array_keys($iter->data)))
			$field = 'content';
		else
			$field = 'content_' . $params['language'];
		
		$content = $iter->get($field);

		echo '<form name="editable" method="post" action="' . $self . '"><p>';
		echo input_hidden('editable_id', $iter->get_id());
		echo input_hidden('submeditable', $iter->get_id());
		echo input_hidden('editable_language', $params['language']);
		
		echo textarea_field($field, array($field => $content), null, 'class', 'editable', 'formatter', 'markup_format_text');
		
		echo '</p></form>';

		echo '</div>
		<div class="editable_preview" id="editable_preview_content"></div>
		
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
		</script>
		</div>';

		return ob_get_clean();
	}
}