<?php
require_once 'include/init.php';
require_once 'include/controllers/Controller.php';

class ControllerWeblog extends Controller
{
	public function __construct()
	{
		$this->model = get_model('DataModelForum');

		$this->view = View::byName('weblog', $this);
	}
	
	protected function run_impl()
	{
		$config_model = get_model('DataModelConfiguratie');
		$forumid = $config_model->get_value('weblog_forum');
		
		if ($forumid === null)
			throw new RuntimeException('weblog_forum setting is missing.');
		
		$forum = $this->model->get_iter($forumid);

		$posts_per_page = 5;

		$page = isset($_GET['page']) ? intval($_GET['page']) : 0;

		if (!$forum)
			$iters = null;
		else
			$iters = $forum->get_threads($page * $posts_per_page, $posts_per_page);

		return $this->view->render_index($iters, $page);
	}
}

$controller = new ControllerWeblog();
$controller->run();
