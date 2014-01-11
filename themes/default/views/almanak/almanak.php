<?php
	require_once('member.php');
	require_once('csv.php');
	
	class AlmanakView extends View {
		protected $__file = __FILE__;

		function almanak_info($model, $iter) {
			$photo = "foto.php?get_thumb&lid_id=" . $model->get_photo_id($iter);

			if ($model->is_private($iter, 'naam')) {
				$format = '<em>%s</em>';
				$name = __('onbekend');
			}
			else
			{
				$format = '%s';
				$name = member_full_name($iter);
			}
			
			return sprintf('
				<a href="profiel.php?lid=%d">
					<img width="100" height="150" src="%s" alt="%s">
					<span class="name">%s</span>
				</a>',
					$iter->get('id'),
					htmlspecialchars($photo, ENT_QUOTES),
					sprintf(__('foto van %s'), htmlspecialchars($name, ENT_QUOTES)),
					sprintf($format, htmlspecialchars($name)));
		}
	}
?>
