<?php
	require_once 'include/markup.php';

	class FotoboekView extends CRUDView
	{
		/**
		 * Configuration
		 */

		public function stylesheets()
		{
			return array_merge(parent::stylesheets(), [
				get_theme_data('styles/fotoboek.css')
			]);
		}

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

		public function render_add_photos(DataIterPhotobook $book, $success, array $errors)
		{
			return $this->render('add_photos.twig', compact('book', 'success', 'errors'));
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
				DataModelPhotobook::VISIBILITY_PUBLIC => __('Publiek'),
				DataModelPhotobook::VISIBILITY_MEMBERS => __('Alleen ingelogde leden'),
				DataModelPhotobook::VISIBILITY_ACTIVE_MEMBERS => __('Alleen ingelogde actieve leden'),
				DataModelPhotobook::VISIBILITY_PHOTOCEE => __('Alleen ingelogde leden van de PhotoCee')
			);
		}

		public function book_thumbnail(DataIterPhotobook $book)
		{
			return 'fotoboek.php?book_thumb=' . $book->get('id');
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
					$path[] = sprintf('<a href="fotoboek.php?book=%s%s">%s</a>',
						urlencode($parents[$i]->get_id()),
						$anchor,
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
				$subtitle[] = sprintf(_ngettext('%d boek', '%d boeken', $book['num_books']), $book['num_books']);

			if ($book['num_photos'] > 0)
				$subtitle[] = sprintf(_ngettext('%d foto', '%d foto\'s', $book['num_photos']), $book['num_photos']);
			
			if (count($subtitle) > 0)
				return sprintf('<small class="fotoboek_highlight">(%s)</small>', markup_format_text(implode_human($subtitle)));
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
			return new ControllerFotoboekComments($photo);
		}

		public function recent_comments($count)
		{
			$model = get_model('DataModelPhotobookReactie');
			return $model->get_latest($count);
		}

		public function random_photos($count)
		{
			$model = get_model('DataModelPhotobook');
			return $model->get_random_photos($count);
		}

		public function is_person(DataIterPhotobookFace $face)
		{
			return (bool) $face['lid_id'];
		}
	}
