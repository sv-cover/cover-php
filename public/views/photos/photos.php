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

		public function render_photobook(DataIterPhotobook $book)
		{
			return $this->render('photobook.twig', compact('book'));
		}

		public function render_create_photobook(DataIterPhotobook $book, $success, array $errors)
		{
			return $this->render('photobook_form.twig', compact('book', 'errors'));
		}

		public function render_update_photobook(DataIterPhotobook $book, $success, array $errors)
		{
			return $this->render('photobook_form.twig', compact('book', 'errors'));
		}

		public function render_download_photobook(DataIterPhotobook $book, $total_photos, $total_file_size)
		{
			return $this->render('photobook_confirm_download.twig', compact('book', 'total_photos', 'total_file_size'));
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

		public function render_delete_photos(DataIterPhotobook $book, array $photos)
		{
			$action_url = $_SERVER['REQUEST_URI'];
			$ids = $_GET['photo_id'];
			return $this->render('confirm_delete_photos.twig', compact('book', 'photos', 'ids', 'action_url'));
		}

		public function render_competition(array $taggers, array $tagged)
		{
			return $this->render('competition.twig', compact('taggers', 'tagged'));
		}

		public function render_people(DataIterPhotobook $book, array $faces)
		{
			$clusters = ['null' => []];

			foreach ($faces as $face) {
				$cluster_id = $face['cluster_id'] ? strval($face['cluster_id']) : 'null';
				if (!isset($clusters[$cluster_id]))
					$clusters[$cluster_id] = [];

				$clusters[$cluster_id][] = $face;
			}

			return $this->render('people.twig', compact('book', 'clusters'));
		}

		public function render_slide(DataIterPhotobook $book, array $photos)
		{
			return $this->render('slide.twig', compact('book', 'photos'));
		}

		/**
		 * Helper functions, called from the templates
		 */

		public function visibility_options()
		{
			return array(
				DataModelPhotobook::VISIBILITY_PUBLIC => __('Public'),
				DataModelPhotobook::VISIBILITY_MEMBERS => __('Only logged in members'),
				DataModelPhotobook::VISIBILITY_ACTIVE_MEMBERS => __('Only logged in active members'),
				DataModelPhotobook::VISIBILITY_PHOTOCEE => __('Only logged in members of the PhotoCee')
			);
		}

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
