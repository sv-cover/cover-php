<?php

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'controllers/ControllerCRUD.php';

class ControllerAnnouncements extends ControllerCRUD
{
	public function __construct()
	{
		parent::__construct();
		
		$this->model = get_model('DataModelAnnouncement');
	}
	
/* protected */ function get_content($view, $iter = null, $params = null)
	{
		$this->run_header(array('title' => $iter instanceof DataIter ? $iter->get('subject') : __('Mededelingen')));
		run_view('announcements::' . $view, $this->model, $iter, $params);
		$this->run_footer();
	}

	protected function _validate(array $data, array &$errors)
	{
		if (!member_in_commissie($data['committee']))
			$errors[] = 'committee';

		if (strlen($data['subject']) == 0)
			$errors[] = 'subject';

		if (strlen($data['message']) == 0)
			$errors[] = 'message';
	}
	
	protected function _create($data, array &$errors)
	{
		$this->_validate($data, $errors);
		
		if (count($errors) > 0)
			return false;

		$data = array(
			'subject' => trim($data['subject']),
			'message' => trim($data['message']),
			'committee' => intval($data['committee']),
			'visibility' => intval($data['visibility'])
		);

		return parent::_create($data, $errors);
	}

	protected function _update(DataIter $announcement, $data, array &$errors)
	{
		$this->_validate($data, $errors);

		if (count($errors) > 0)
			return false;

		$data = array(
			'subject' => trim($data['subject']),
			'message' => trim($data['message']),
			'committee' => intval($data['committee']),
			'visibility' => intval($data['visibility'])
		);

		return parent::_update($announcement, $data, $errors);
	}

	public function run_embedded()
	{
		run_view('announcements::index', $this->model, $this->model->get_latest(), array());
	}
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
	$controller = new ControllerAnnouncements();
	$controller->run();
}
