<?php

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'controllers/Controller.php';

class ControllerAnnouncements extends Controller
{
	public function __construct()
	{
		$this->model = get_model('DataModelAnnouncement');
	}
	
	/* protected */ function get_content($view, $iter = null, $params = null)
	{
		$this->run_header(array('title' => $iter instanceof DataIter ? $iter->get('subject') : __('Mededelingen')));
		run_view('announcements::' . $view, $this->model, $iter, $params);
		$this->run_footer();
	}
	
	protected function _create_announcement($data)
	{
		if (!$this->model->member_can_create_announcements())
			throw new Exception('You are not allowed to add announcements.');

		if (!member_in_commissie($data['committee']))
			throw new Exception('Cannot create an announcement for a committee you are not a member of.');

		$iter = new DataIter($this->model, -1, array(
			'subject' => trim($data['subject']),
			'message' => trim($data['message']),
			'committee' => intval($data['committee']),
			'visibility' => intval($data['visibility'])
		));

		return $this->model->insert($iter, true);
	}

	protected function _update_announcement(DataIter $announcement, $data)
	{
		if (!$this->model->member_can_update_announcement($announcement))
			throw new Exception('You are not allowed to edit this announcement.');

		if (!member_in_commissie($data['committee']))
			throw new Exception('Cannot create an announcement for a committee you are not a member of.');

		$announcement->set('subject', trim($data['subject']));
		$announcement->set('message', trim($data['message']));
		$announcement->set('committee', intval($data['committee']));
		$announcement->set('visibility', intval($data['visibility']));
		$this->model->update($announcement);
	}

	protected function _delete_announcement(DataIter $announcement)
	{
		if (!$this->model->member_can_delete_announcement($announcement))
			throw new Exception('You are not allowed to edit this announcement.');

		$this->model->delete($announcement);
	}

	protected function _add_announcement()
	{
		if (!$this->model->member_can_create_announcements())
			throw new Exception('You are not allowed to add announcements.');

		return $this->get_content('form', new DataIter($this->model, null, array()));
	}

	protected function _edit_announcement(DataIter $announcement)
	{
		if (!$this->model->member_can_update_announcement($announcement))
			throw new Exception('You are not allowed to edit this announcement.');

		return $this->get_content('form', $announcement);
	}
	
	/* protected */ function run_impl()
	{
		if (!empty($_GET['announcement_id']))
		{
			$announcement = $this->model->get_iter($_GET['announcement_id']);

			if (!$announcement)
				return run_view('common::not_found');

			if ($_SERVER['REQUEST_METHOD'] == 'GET')
				$this->_edit_announcement($announcement);
			elseif ($_SERVER['REQUEST_METHOD'] == 'POST')
				$this->_update_announcement($announcement, $_POST);
			elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE')
				$this->_delete_announcement($announcement);
		}
		else if ($_SERVER['REQUEST_METHOD'] == 'POST')
			$this->_create_announcement($_POST);
		else
			$this->_add_announcement();
	}

	public function run_embedded()
	{
		run_view('announcements::announcements', $this->model, $this->model->get_latest(), array());
	}
}

if ($_SERVER['SCRIPT_FILENAME'] == __FILE__) {
	$controller = new ControllerAnnouncements();
	$controller->run();
}
