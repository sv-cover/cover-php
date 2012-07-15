<?php
	if (!defined('IN_SITE'))
		return;

	require_once('controllers/Controller.php');
	require_once('form.php');

	class ControllerNews extends Controller {
		var $model = null;

		function ControllerNews() {
			$this->model = get_model('DataModelForum');
		}
		
		function get_content($view, $iter = null, $params = null) {
			run_view('news::' . $view, $this->model, $iter, $params);
		}
		
		function _view_news() {
			$config_model = get_model('DataModelConfiguratie');
			$id = $config_model->get_value('news_forum');
			$params = array();

			if ($id) {
				$params['forumid'] = $id;
				$forum = $this->model->get_iter($id);
				
				if ($forum)
					$iters = $forum->get_last_thread(-1, 5, false);
			}

			$this->get_content('news', $iters, $params);
		}
		
		function run_impl() {
			$this->_view_news();
		}
	}
?>
