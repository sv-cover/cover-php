<?php
namespace App\Controller;

require_once 'src/controllers/PhotoBooksController.php';
require_once 'src/framework/controllers/Controller.php';

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class PhotoPrivacyController extends \Controller
{
	use PhotoBookRouteHelper;

	protected $view_name = 'photos';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelPhotobookPrivacy');

		parent::__construct($request, $router);
	}

	protected function run_impl()
	{
		if (!get_auth()->logged_in())
			throw new \UnauthorizedException();

		$member = get_identity()->member();

		$photo =$this->get_photo();
		if (!$photo)
			throw new \RuntimeException('You cannot access the photo auxiliary functions without also selecting a photo');

		$data = [
			'visibility' => $this->model->is_visible($photo, $member) ? 'visible' : 'hidden',
		];

		$form = $this->createFormBuilder($data)
			->add('visibility', ChoiceType::class, [
				'label' => __('Visibility of this photo'),
				'choices'  => [
					__('Show photo in my personal photo album') => 'visible',
					__('Hide from my personal photo album') => 'hidden',
				],
				'expanded' => true,
			])
			->add('submit', SubmitType::class, ['label' => __('Change visibility')])
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			if ($form['visibility']->getData() == 'hidden')
				$this->model->mark_hidden($photo, $member);
			else
				$this->model->mark_visible($photo, $member);

			return $this->view->redirect($this->generate_url('photos.photo', [
				'photo' => $photo['id'],
				'book' => $photo['scope']['id'],
			]));
		}

		return $this->view->render('privacy.twig', [
			'photo' => $photo,
			'form' => $form->createView(),
		]);
	}
}
