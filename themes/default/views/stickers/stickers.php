<?php
	
class StickersView extends View
{
	protected $__file = __FILE__;

	public function __construct()
	{
		$this->model = get_model('DataModelStickers');
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

		foreach ($iters as $iter)
		{
			$sticker = array(
				'id' => $iter->get('id'),
				'label' => $iter->get('label'),
				'omschrijving' => $iter->get('omschrijving'),
				'lat' => $iter->get('lat'),
				'lng' => $iter->get('lng'),
				'foto' => $iter->get('foto') ? 'stickers.php?photo=' . $iter->get('id') : null,
				'toegevoegd_op' => $iter->get('toegevoegd_op'),
				'toegevoegd_door_id' => $iter->get('toegevoegd_door'),
				'toegevoegd_door_naam' => $iter->get('toegevoegd_door')
					? htmlentities(member_full_name($iter->getIter('toegevoegd_door'), false, true))
					: null,
				'editable' => $this->model->memberCanEditSticker($iter)
			);

			$stickers[] = $sticker;
		}

		return json_encode($stickers);
	}
}
