<?php
	require_once 'src/framework/markup.php';

	use App\Controller\PhotoCommentsController;

	class FotoboekView extends CRUDView
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

		public function render_competition()
		{
			$taggers = get_db()->query('
				SELECT
					l.id,
					l.voornaam,
					COUNT(f_f.id) tags,
					(SELECT
						fav_l.voornaam
					FROM
						foto_faces fav_faces
					LEFT JOIN leden fav_l ON
						fav_l.id = fav_faces.lid_id
					WHERE
						fav_faces.tagged_by = l.id
					GROUP BY
						fav_l.id
					ORDER BY
						COUNT(fav_l.id) DESC
					LIMIT 1) favorite
				FROM
					foto_faces f_f
				LEFT JOIN leden l ON
					l.id = f_f.tagged_by
				WHERE
					f_f.lid_id IS NOT NULL
				GROUP BY
					l.id
				ORDER BY
					tags DESC');

			$tagged = get_db()->query('
				SELECT
					l.id,
					l.voornaam,
					COUNT(f_f.id) tags
				FROM
					foto_faces f_f
				LEFT JOIN leden l ON
					l.id = f_f.lid_id
				WHERE
					f_f.lid_id IS NOT NULL
				GROUP BY
					l.id
				HAVING
					COUNT(f_f.id) > 50
				ORDER BY
					tags DESC');

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

		public function path(DataIterPhotobook $book, DataIterPhoto $photo = null)
		{
			$model = $this->controller->model();

			$parents = array_merge($model->get_parents($book), array($book));

			$path = array();

			for ($i = 0; $i < count($parents); ++$i)
			{
				if ($i + 1 < count($parents))
					$anchor = sprintf('#book_%s', $parents[$i + 1]->get_id());
				elseif ($i + 1 == count($parents) && $photo)
					$anchor = sprintf('#photo_%d', $photo->get_id());
				else
					$anchor = '';

				if (get_policy($parents[$i])->user_can_read($parents[$i]))
					$path[] = sprintf('<li%s><a href="%s%s"%s>%s</a></li>',
						$i == count($parents) - 1 ? ' class="is-active"' : '',
						$this->controller->generate_url('photo', ['book' => $parents[$i]->get_id()]),
						$anchor,
						$i == count($parents) - 1 ? ' aria-current="page"' : '',
						markup_format_text($parents[$i]['titel']));
				else
					$path[] = markup_format_text($parents[$i]['titel']);
			}

			return $path;
		}

		public function summary(DataIterPhotobook $book)
		{
			$subtitle = array();

			if ($book['num_books'] > 0)
				$subtitle[] = sprintf(_ngettext('%d book', '%d books', $book['num_books']), $book['num_books']);

			if ($book['num_photos'] > 0)
				$subtitle[] = sprintf(_ngettext('%d photo', '%d photos', $book['num_photos']), $book['num_photos']);
			
			if (count($subtitle) > 0)
				return implode_human($subtitle);
			else
				return '';
		}

		public function slides(DataIterPhotobook $book, DataIterPhoto $photo, $count)
		{
			$prev = $book->get_previous_photo($photo, $count);
			$next = $book->get_next_photo($photo, $count);

			while (count($prev) < $count)
				array_push($prev, '');

			while (count($next) < $count)
				array_push($next, '');

			return array_merge(
				array_values(array_reverse($prev)),
				array($photo),
				array_values($next));
		}

		public function comment_controller_for_photo(DataIterPhoto $photo)
		{
			return new PhotoCommentsController($photo, $this->controller->get_request(), $this->controller->get_router());
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

		public function is_person(DataIterPhotobookFace $face)
		{
			return (bool) $face['lid_id'];
		}
	}
