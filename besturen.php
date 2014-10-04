<?php

require_once 'include/init.php';
require_once 'controllers/Controller.php';
require_once 'controllers/ControllerEditable.php';

class ControllerBesturen extends Controller
{
	public function ControllerBesturen()
	{
		$this->model = get_model('DataModelBesturen');
	}

	public function get_content($view, $iter = null, $params = null)
	{
		$this->run_header(array('title' => __('Besturen')));
		run_view('besturen::' . $view, $this->model, $iter, $params);
		$this->run_footer();
	}

	public function run_impl()
	{
		if (isset($_POST['action']) && $_POST['action'] == 'add')
			$this->_do_add();
		elseif (isset($_POST['action']) && $_POST['action'] == 'update')
			$this->_do_update();
		elseif (isset($_GET['add_bestuur']))
			$this->_view_add();
		else
			$this->_view_besturen();
	}

	public function _do_add()
	{
		if (!member_in_commissie(COMMISSIE_BESTUUR))
		{
			$this->get_content('auth_bestuur');
			return;
		}

		$editable_model = get_model('DataModelEditable');

		$data = array(
			'owner' => COMMISSIE_BESTUUR,
			'titel' => $_POST['naam']);

		$iter = new DataIter($editable_model, -1, $data);

		$page_id = $editable_model->insert($iter, true);

		$data = array(
			'naam' => $_POST['naam'],
			'login' => $_POST['login'],
			'nocaps' => strtolower($_POST['naam']),
			'page' => $page_id);

		$iter = new DataIter($this->model, -1, $data);

		$bestuur_id = $this->model->insert($iter, true);

		header('Location: besturen.php?editable_edit=' . $page_id . '#' . $bestuur_id);
	}

	public function _do_edit()
	{
		if (!member_in_commissie(COMMISSIE_BESTUUR))
		{
			$this->get_content('auth_bestuur');
			return;
		}

		$bestuur = $this->model->get_iter($_GET['id']);

		$bestuur->set('naam', $_POST['naam']);
		$bestuur->set('login', $_POST['login']);
		$bestuur->set('nocaps', strtolower($_POST['naam']));

		$this->model->update($bestuur);

		$editable_model = get_model('DataModelEditable');

		$editable = $editable_model->get_iter($bestuur->get('page'));
		$editable->set('titel', $_POST['naam']);
		
		$editable_model->update($editable);

		header('Location: besturen.php#' . $bestuur->get('id'));
	}

	public function _view_add()
	{
		$this->get_content('add');
	}

	public function _view_besturen()
	{
		// Find all the boards
		$iters = $this->model->get();

		// Sort then on their canonical names: $betuur->get('login')
		usort($iters, array($this, '_compare_bestuur'));
		
		$this->get_content('besturen', $iters);
	}

	public function _compare_bestuur($left, $right)
	{
		return -1 * strnatcmp($left->get('login'), $right->get('login'));
	}

	protected function capture(Controller $controller)
	{
		ob_start();
		
		$controller->run();
		
		return ob_get_clean();
	}
}

$controller = new ControllerBesturen();
$controller->run();
