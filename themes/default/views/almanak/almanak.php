<?php
	require_once('member.php');
	require_once('csv.php');
	
	class AlmanakView extends View {
		protected $__file = __FILE__;

		function almanak_info($model, $iter) {
			$photo = "foto.php?get_thumb&lid_id=" . $model->get_photo_id($iter);

			$name = member_full_name($iter, false, true);
			
			$classes = array();
			$status = '';

			switch ($iter->get('type'))
			{
				case MEMBER_STATUS_LID:
					$classes[] = 'status-lid';
					break;

				case MEMBER_STATUS_LID_ONZICHTBAAR:
					$classes[] = 'status-onzichtbaar';
					$status = __('Onzichtbaar');
					break;

				case MEMBER_STATUS_LID_AF:
					$classes[] = 'status-lid-af';
					$status = __('Lid af');
					break;

				case MEMBER_STATUS_ERELID:
					$classes[] = 'status-erelid';
					$status = __('Erelid');
					break;

				case MEMBER_STATUS_DONATEUR:
					$classes[] = 'status-donateur';
					$status = __('Donateur');
					break;
			}
			
			return sprintf('
				<a href="profiel.php?lid=%d" class="%s">
					<div class="photo">
						<img width="100" height="150" src="%s" alt="%s">
					</div>
					<span class="name">%s</span>
					%s
				</a>',
					$iter->get('id'),
					implode(' ', $classes),
					htmlspecialchars($photo, ENT_QUOTES),
					sprintf(__('foto van %s'), htmlspecialchars($name, ENT_QUOTES)),
					htmlspecialchars($name),
					$status ? sprintf('<span class="status">(%s)</span>', htmlspecialchars($status)) : '');
		}
	}
?>
