<?php
require_once 'include/init.php';
require_once 'include/controllers/Controller.php';
	
class MessageController extends Controller
{
	protected $model;

	public function __construct()
	{
		$this->model = get_model('DataModelMessage');
	}

	protected function get_content($view, $iters = null, $params = null)
	{
		run_view('message::' . $view, $this->model, $iters, $params);
	}

	function run_impl()
	{
		if (!logged_in())
			die("Log in first");

		$placed = false;

		if (isset($_POST['message'])) {
			$iter = new DataIterMessage($this->model, -1, ['member_id' => logged_in('id'), 'message' => $_POST['message']]);
			$this->model->insert($iter);
			$placed = true;
		}

		$this->get_content('form', null, compact('placed'));
	}
}

$controller = new MessageController();
$controller->run();
