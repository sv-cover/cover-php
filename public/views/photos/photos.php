<?php
require_once 'src/framework/markup.php';

class PhotosView extends CRUDFormView
{
	/**
	 * Helper functions, called from the templates
	 */

	public function recent_comments($count)
	{
		$model = get_model('DataModelPhotobookReactie');
		return $model->get_latest($count);
	}

	public function thumbnail_photos(DataIterPhotobook $book, $count)
	{
		$model = get_model('DataModelPhotobook');
		return $model->get_photos_recursive($book, $count, true, 0.69);
	}
}
