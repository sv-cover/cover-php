<?php

class SearchView extends View
{
	public function photobook_summary(DataIterPhotobook $book)
	{
		$parts = [];

		if ($book['num_books'] > 0)
			$parts[] = __N('%d boek', '%d boeken', $book['num_books']);

		if ($book['num_photos'] > 0)
			$parts[] = __N('%d foto', '%d foto\'s', $book['num_photos']);

		return sprintf(__('Fotoboek met %s gemaakt op %s.'), implode_human($parts), $book['date']);
	}
}
