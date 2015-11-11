<?php
	
class StickersView extends CRUDView
{
	protected $__file = __FILE__;

	protected $model;

	public function __construct(ControllerCRUD $controller)
	{
		parent::__construct($controller);

		$this->model = get_model('DataModelStickers');
	}

	public function get_scripts()
	{
		return array_merge(parent::get_scripts(), [
			get_theme_data('data/stickers.js')
		]);
	}

	public function getLocation()
	{
		if (isset($_GET['sticker']))
		{
			$sticker = $this->model->get_iter($_GET['sticker']);
			return sprintf('%f, %f', $sticker->get('lat'), $sticker->get('lng'));
		}
		else
			return '53.20, 6.56'; // Groningen
	}

	public function encodeStickers($iters)
	{
		$stickers = array();

		$policy = get_policy($this->model);

		foreach ($iters as $iter)
		{
			$sticker = array(
				'id' => $iter->get('id'),
				'label' => $iter->get('label'),
				'omschrijving' => $iter->get('omschrijving'),
				'lat' => $iter->get('lat'),
				'lng' => $iter->get('lng'),
				'foto' => $iter->get('foto') ? $this->controller->link_to_photo($iter) : null,
				'toegevoegd_op' => $iter->get('toegevoegd_op'),
				'toegevoegd_door_id' => $iter->get('toegevoegd_door'),
				'toegevoegd_door_naam' => $iter->get('toegevoegd_door')
					? member_full_name($iter->getIter('toegevoegd_door'), false, true)
					: null,
				'editable' => $policy->user_can_update($iter)
			);

			$stickers[] = $sticker;
		}

		return json_encode($stickers);
	}
}
