<?php
	require_once 'include/markup.php';

	class FotoboekView extends CRUDView
	{
		public function stylesheets()
		{
			return array_merge(parent::stylesheets(), [
				get_theme_data('styles/fotoboek.css')
			]);
		}

		public function book_thumbnail(DataIterPhotobook $book)
		{
			return 'fotoboek.php?book_thumb=' . $book->get('id');
		}

		public function render_privacy(DataIterPhoto $photo, $visibility)
		{
			return $this->render('privacy.twig', compact('photo', 'visibility'));
		}

		public function render_fotoboek(DataIterPhotobook $book)
		{
			return $this->render('fotoboek.twig', compact('book'));
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

				$path[] = sprintf('<a href="fotoboek.php?book=%s%s">%s</a>',
					urlencode($parents[$i]->get_id()),
					$anchor,
					markup_format_text($parents[$i]->get('titel')));
			}

			return $path;
		}

		public function navigation(DataIterPhotobook $book)
		{
			$nav = new stdClass();

			$nav->previous = $book->get_previous_book();
			$nav->parent = $book->get_parent();
			$nav->next = $book->get_next_book();

			return $nav;
		}

		public function summary(DataIterPhotobook $book)
		{
			$subtitle = array();

			if (($num = $book->count_books()) > 0)
				$subtitle[] = sprintf(_ngettext('%d boek', '%d boeken', $num), $num);

			if (($num = $book->count_photos()) > 0)
				$subtitle[] = sprintf(_ngettext('%d foto', '%d foto\'s', $num), $num);
			
			if (count($subtitle) > 0)
				return sprintf('<small class="fotoboek_highlight">(%s)</small>', markup_format_text(implode_human($subtitle)));
			else
				return '';
		}
	}
