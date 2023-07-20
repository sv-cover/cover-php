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

	public function render_photo(DataIterPhotobook $book, DataIterPhoto $photo)
	{
		$is_liked = get_auth()->logged_in() && get_model('DataModelPhotobookLike')->is_liked($photo, get_identity()->member()->get_id());

		return $this->render('single.twig', compact('book', 'photo', 'is_liked'));
	}

	public function render_update_photo(DataIterPhotobook $book, DataIterPhoto $photo, $success, array $errors)
	{
		return $this->render('photo_form.twig', compact('book', 'photo', 'errors'));
	}

	public function render_add_photos(DataIterPhotobook $book, $success, array $errors)
	{
		return $this->render('add_photos.twig', compact('book', 'success', 'errors'));
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
