<?php
	require_once 'include/member.php';
	require_once 'include/csv.php';
	
	class AlmanakView extends View {
		protected $__file = __FILE__;

		function almanak_info($model, $iter) {
			$photo = "foto.php?get_thumb&lid_id=" . $iter->get_id();

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
					<img width="100" height="150" src="%s" alt="%s">
					<span class="name">%s</span>
					%s
				</a>',
					$iter->get('id'),
					implode(' ', $classes),
					markup_format_attribute($photo),
					markup_format_attribute(sprintf(__('foto van %s'), $name)),
					markup_format_text($name),
					$status ? sprintf('<span class="status">(%s)</span>', markup_format_text($status)) : '');
		}
	}
