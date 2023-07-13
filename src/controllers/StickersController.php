<?php
namespace App\Controller;

use App\Form\StickerType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

require_once 'src/framework/controllers/ControllerCRUDForm.php';

class StickersController extends \ControllerCRUDForm
{
	protected $view_name = 'stickers';
	protected $form_type = StickerType::class;

	public function __construct($request, $router)
	{
		$this->model = \get_model('DataModelSticker');

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

		return $this->generate_url('stickers', $parameters);
	}

	protected function _process_create(\DataIter $iter, FormInterface $form)
	{
		$iter['toegevoegd_op'] = date('Y-m-d');
		$iter['toegevoegd_door'] = get_identity()->get('id');

		return parent::_process_create($iter, $form);
	}

	public function new_iter()
	{
		$iter = $this->model->new_iter();

		if (!empty($_GET['lat']))
			$iter['lat'] = $_GET['lat'];

		if (!empty($_GET['lng']))
			$iter['lng'] = $_GET['lng'];

		return $iter;
	}

	public function run_read(\DataIter $iter)
	{
		return $this->view->redirect($this->generate_url('stickers', [
			'point' => $iter['id'],
			'lat' => $iter['lat'],
			'lng' => $iter['lng'],
		]));
	}

	public function run_photo(\DataIter $iter)
	{
		$thumbnail = !empty($_GET['thumbnail']);

		if ($thumbnail)
			return $this->view->render_photo_thumbnail($iter);
		else
			return $this->view->render_photo($iter);
	}

	protected function run_add_photo(\DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_update($iter))
			throw new \UnauthorizedException(__("You're not allowed to upload a photo for this sticker."));

		$form = $this->createFormBuilder()
			->add('photo', FileType::class, [
				'label' => __('Photo'),
				'help' => __('Add a photo to this sticker. Only JPEG-images are allowed.'),
				'constraints' => [
					new Assert\Image([
						'maxSize' => ini_get('upload_max_filesize'),
						'mimeTypes' => [
							'image/jpeg',
						],
						'mimeTypesMessage' => __('Please upload a valid JPEG-image.'),
						'sizeNotDetectedMessage' => __('The uploaded file doesn’t appear to be an image.'),
					])
				],
				'attr' => [
					'accept' => 'image/jpeg',
				],
			])
			->add('submit', SubmitType::class)
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			$file = $form['photo']->getData();

			// Set the new photo
			$this->model->setPhoto($iter, fopen($file->getPathname(), 'rb'));

			// Delete the old one from the cache
			$this->view->delete_thumbnail($iter);

			// Ensure we're redirecting to point
			$next_url = edit_url($this->get_referrer() ?? $this->generate_url('stickers'), ['point' => $iter['id']]);
			return $this->view->redirect($next_url);
		}

		return $this->view->render('add_photo.twig', ['iter' => $iter, 'form' => $form->createView()]);
	}

	protected function run_geojson()
	{
		$features = [];

		$policy = \get_policy($this->model());

		foreach ($this->model->get() as $iter)
		{
			if ($policy->user_can_read($iter))
				$features[] = [
					'type' => 'Feature',
					'geometry' => [
						'type' => 'Point',
						'coordinates' => [
							$iter['lng'],
							$iter['lat']
						]
					],
					'properties' => [
						'id' => $iter['id'],
						'label' => $iter['label'],
						'description' => $iter['omschrijving'],
						'photo_url' => $iter['foto'] ? $this->generate_url('stickers', ['view' => 'photo', 'id' => $iter->get_id()]) : null,
						'added_on' => $iter['toegevoegd_op'],
						'added_by_url' => $iter['toegevoegd_door'] ? $this->generate_url('profile', ['lid' => $iter['toegevoegd_door']]) : null,
						'added_by_name' => $iter['toegevoegd_door']
							? member_full_name($iter['member'], BE_PERSONAL)
							: null,
						'editable' => $policy->user_can_update($iter),
						'add_photo_url' => $policy->user_can_update($iter) ? $this->generate_url('stickers', ['view' => 'add_photo', 'id' => $iter->get_id()]) : null,
						'delete_url' => $policy->user_can_delete($iter) ? $this->generate_url('stickers', ['view' => 'delete', 'id' => $iter->get_id()]) : null,
					]
				];
		}

		return $this->view->render_json([
			'type' => 'FeatureCollection',
			'features' => $features,
		]);
	}

}
