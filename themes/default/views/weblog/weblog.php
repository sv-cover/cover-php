<?
	require_once('markup.php');
 
	class WeblogView extends View {
		protected $__file = __FILE__;
		
		function _get_weblog_head($iter) {
			if ($iter->get('author_type') != 1)
				return 'none.png';

			if (file_exists('images/heads/' . $iter->get('author') . '.png'))
				return 'images/heads/' . $iter->get('author') . '.png';
			else
				return 'foto.php?lid_id=' . $iter->get('author') . '&get_thumb=circle&width=200';
		}
		
	}
?>
