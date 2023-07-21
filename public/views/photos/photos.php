<?php
require_once 'src/framework/markup.php';

class PhotosView extends CRUDView
{	
	/**
	 * Render methods, called from the controller
	 */

	public function render_privacy(DataIterPhoto $photo, $visibility)
	{
		return $this->render('privacy.twig', compact('photo', 'visibility'));
	}

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
