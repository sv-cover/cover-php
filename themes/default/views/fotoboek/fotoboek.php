<?php
	require_once('markup.php');

	class FotoboekView extends View {
		protected $__file = __FILE__;
	
		function get_book_thumbnail($model, $book) {
			return 'fotoboek.php?book_thumb=' . $book->get('id');
		}
	}
?>
