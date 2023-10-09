<?php
namespace App\Controller;

use App\Form\AnnouncementType;

require_once 'src/framework/controllers/ControllerCRUDForm.php';

/**
 * Class ControllerAnnouncements
 * @property DataModelAnnouncement $model;
 */
class AnnouncementsController extends \ControllerCRUDForm
{
	protected $view_name = 'announcements';
	protected $form_type = AnnouncementType::class;

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelAnnouncement');

		parent::__construct($request, $router);
	}

	public function path(string $view, \DataIter $iter = null, bool $json = false)
	{
		$parameters = [
			'view' => $view,
		];

		if (isset($iter))
		{
			$parameters['id'] = $iter->get_id();

			if ($json)
				$parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));
		}

		return $this->generate_url('announcements', $parameters);
	}
}
