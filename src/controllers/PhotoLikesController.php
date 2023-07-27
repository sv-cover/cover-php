<?php
namespace App\Controller;

require_once 'src/controllers/PhotoBooksController.php';
require_once 'src/framework/controllers/Controller.php';

use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class PhotoLikesController extends \Controller
{
	use PhotoBookRouteHelper;

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelPhotobookLike');

		parent::__construct($request, $router);
	}

	public function run_impl()
	{
		if (!$this->get_photo())
			throw new \RuntimeException('You cannot access the photo auxiliary functions without also selecting a photo');

		$action = null;
		$response_json = false;

		$form = $this->createFormBuilder(null, ['csrf_token_id' => 'like_photo_' . $this->get_photo()->get_id()])
			->add('like', SubmitType::class)
			->add('unlike', SubmitType::class)
			->getForm();
		$form->handleRequest($this->get_request());

		if ($_SERVER["CONTENT_TYPE"] === 'application/json') {
			$response_json = true;
			$json = file_get_contents('php://input');
			$data = json_decode($json);
			if (isset($data->action))
				$action = $data->action;
		} elseif ($form->isSubmitted() && $form->isValid()) {
			$action = $form->get('like')->isClicked() ? 'like' : 'unlike';
		}

		if (get_auth()->logged_in() && isset($action)) {
			try {
				if ($action === 'like')
					$this->model->like($this->get_photo(), get_identity()->get('id'));
				elseif ($action === 'unlike')
					$this->model->unlike($this->get_photo(), get_identity()->get('id'));
			} catch (\Exception $e) {
				// Don't break duplicate requests
			}
		}

		if ($response_json)
			return $this->view->render_json([
				'liked' => get_auth()->logged_in() && $this->model->is_liked($this->get_photo(), get_identity()->get('id')),
				'likes' => count($this->model->get_for_photo($this->get_photo()))
			]);

		return $this->view->redirect($this->generate_url('photos.photo', [
			'photo' => $this->get_photo()['id'],
			'book' => $this->get_photo()['scope']['id'],
		]));
	}
}
