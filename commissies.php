<?php
include 'include/init.php';
include 'controllers/ControllerCRUD.php';
include 'controllers/ControllerEditable.php';

class ControllerCommissies extends ControllerCRUD
{	
	public function __construct()
	{
		$this->model = get_model('DataModelCommissie');
	}

	protected function _index()
	{
		return $this->model->get(false);
	}

	protected function _read($id)
	{
		if (!ctype_digit($id))
			return $this->model->get_from_name($id);
		else
			return parent::_read($id);
	}

	public function link_to_iter(DataIter $iter, array $arguments)
	{
		return $this->link(array_merge(array('id' => $iter->get('login')), $arguments));
	}

	/*
	public function link(array $arguments)
	{
		$link = '/committees';

		if (isset($arguments['view'])) {
			$link .= '/' . $arguments['view'];
			unset($arguments['view']);
		}

		if (isset($arguments['id'])) {
			$link .= '/' . $arguments['id'];
			unset($arguments['id']);
		}

		if (!empty($arguments))
			$link .= '?' . http_build_query($arguments);

		return $link;
	}
	*/

	function get_content($view, $iter = null, $params = array())
	{
		$title = $iter instanceof DataIter
			? $iter->get('naam')
			: __('Commissies');

		$this->run_header(compact('title'));
		
		$params['controller'] = $this;
		run_view('commissies::' . $view, $this->model, $iter, $params);
		
		$this->run_footer();
	}
}

$controller = new ControllerCommissies();
$controller->run();

