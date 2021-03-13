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
    protected $view_name = 'announcements';

    public function __construct($request, $router)
    {
		$this->model = get_model('DataModelAnnouncement');

        parent::__construct($request, $router);
	}

	public function run_preview()
	{
		return markup_parse($_POST['message']);
	}
}