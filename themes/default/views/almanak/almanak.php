<?php
	require_once('member.php');
	require_once('csv.php');
	
	class AlmanakView extends View {
		protected $__file = __FILE__;

		function almanak_info($model, $iter) {
			$photo = "foto.php?get_thumb&lid_id=" . $model->get_photo_id($iter);

			if ($model->is_private($iter, 'naam'))
				$name = '<span class="italic">' . _('onbekend') . '</span>';
			else
				$name = member_full_name($iter);
			
			return array('<a href="profiel.php?lid=' . $iter->get('id') . '"><img width="100" height="150" src="' . $photo . '" alt="' . sprintf(_('foto van %s'), member_full_name($iter)) . '"></a>', $name);
		}
	}
?>
