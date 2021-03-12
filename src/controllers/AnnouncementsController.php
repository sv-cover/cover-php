<?php
namespace App\Controller;

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'include/controllers/ControllerCRUD.php';

/**
 * Class ControllerAnnouncements
 * @property DataModelAnnouncement $model;
 */
class AnnouncementsController extends \ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelAnnouncement');

		$this->view = \View::byName('announcements', $this);
	}

	public function run_preview()
	{
		return markup_parse($_POST['message']);
	}
}
