<?php
	
class StickersView extends View
{
	protected $__file = __FILE__;

	public function getLocation()
	{
		if (isset($_GET['sticker']))
		{
			$model = get_model('DataModelStickers');
			$sticker = $model->get_iter($_GET['sticker']);
			return sprintf('%f, %f', $sticker->get('lat'), $sticker->get('lng'));
		}
		else
			return '53.20, 6.56'; // Groningen
	}
}
