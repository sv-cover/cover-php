<?php

class SearchView extends View
{
	public function render_index($query, $results)
	{
		$query_parts = parse_search_query($query);

		return $this->render('index.twig', compact('query', 'query_parts', 'results'));
	}

	public function photobook_summary(DataIterPhotobook $book)
	{
		$n_books = $book->count_books();
		$n_photos = $book->count_photos();

		$parts = [];

		if ($n_books > 0)
			$parts[] = __N('%d boek', '%d boeken', $n_books);

		if ($n_photos > 0)
			$parts[] = __N('%d foto', '%d foto\'s', $n_photos);

		return sprintf(__('Fotoboek met %s gemaakt op %s.'), implode_human($parts), $book['date']);
	}
}
