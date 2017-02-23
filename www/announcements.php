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
		if (!get_identity()->member_in_committee($data['committee_id'])
			&& !get_identity()->member_in_committee(COMMISSIE_BESTUUR))
			$errors[] = 'committee_id';

		if (strlen($data['subject']) == 0)
			$errors[] = 'subject';

		if (strlen($data['message']) == 0)
			$errors[] = 'message';

		return count($errors) === 0;
	}

	public function run_preview()
	{
		return markup_parse($_POST['message']);
	}
}

$controller = new ControllerAnnouncements();
$controller->run();
