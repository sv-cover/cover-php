<?php

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'include/controllers/ControllerCRUD.php';

/**
 * Class ControllerAnnouncements
 * @property DataModelAnnouncement $model;
 */
class ControllerAnnouncements extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelAnnouncement');

		$this->view = View::byName('announcements', $this);
	}

	protected function _validate(DataIter $iter, array &$data, array &$errors)
	{
		if (!get_identity()->member_in_committee($data['committee_id']))
			$errors[] = 'committee_id';

		if (strlen($data['subject']) == 0)
			$errors[] = 'subject';

		if (strlen($data['message']) == 0)
			$errors[] = 'message';
	}
	
	protected function _create(DataIter $iter, array $data, array &$errors)
	{
		$this->_validate($data, $errors);
		
		if (count($errors) > 0)
			return false;

		$data = array(
			'subject' => trim($data['subject']),
			'message' => trim($data['message']),
			'committee_id' => intval($data['committee_id']),
			'visibility' => intval($data['visibility'])
		);

		return parent::_create($iter, $data, $errors);
	}

	protected function _update(DataIter $announcement, array $data, array &$errors)
	{
		$this->_validate($announcement, $data, $errors);

		if (count($errors) > 0)
			return false;

		$data = array(
			'subject' => trim($data['subject']),
			'message' => trim($data['message']),
			'committee_id' => intval($data['committee_id']),
			'visibility' => intval($data['visibility'])
		);

		return parent::_update($announcement, $data, $errors);
	}
}

$controller = new ControllerAnnouncements();
$controller->run();
