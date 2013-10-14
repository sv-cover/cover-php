<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing photo data
	  */
	class DataModelFotoboek extends DataModel {
		function DataModelFotoboek($db) {
			parent::DataModel($db, 'fotos');
		}
		
		/**
		  * Get a photo book
		  * @id the id of the book
		  *
		  * @result a #DataIter
		  */
		function get_book($id, $logged_in = false) {
			$q = "
					SELECT 
						*, 
						(TRIM(to_char(DATE_PART('day', date), '00')) || '-' ||
						 TRIM(to_char(DATE_PART('month', date), '00')) || '-' ||
						 DATE_PART('year', date)) AS datum
					FROM 
						foto_boeken
					WHERE 
						id = " . intval($id);
			if(!$logged_in) {
				// Als je niet ingelogd bent dan mag je de chantagemap niet zien!
				$q .= " AND titel NOT ILIKE 'chantagemap%' AND titel NOT ILIKE 'download grote foto\'s%'";
				// en je mag ook de foto's van voorgaande jaren niet zien! Dit doen we lekker op ID :'). 833 is het eerste album van 2012, die mag op dit moment zichtbaar zijn
				$q .= " AND id >= 833;";
			}
			$row = $this->db->query_first($q);
			return $this->_row_to_iter($row);
		}
		
		/**
		  * Get the photo book thumbnail
		  * @book a #DataIter representing a book
		  *
		  * @result a #DataIter
		  */
		function get_book_thumbnail($book) {
			$row = $this->db->query_first("
					SELECT
						*,
						(DATE_PART('epoch', CURRENT_TIMESTAMP) - DATE_PART('epoch', generated)) AS since
					FROM
						foto_boeken_thumb
					WHERE
						boek = " . ($book ? $book->get('id') : 0) . " AND
						theme = '" . $this->escape_string(get_theme()) . "'");

			return $this->_row_to_iter($row);
		}
		
		/**
		  * Get replies to a photo
		  * @photo a #DataIter representing a photo
		  *
		  * @result an array of #DataIter
		  */
		function get_reacties($photo) {
			$rows = $this->db->query("
					SELECT 
						*,
						DATE_PART('dow', date) AS dagnaam, 
						DATE_PART('day', date) AS datum, 
						DATE_PART('month', date) AS maand, 
						DATE_PART('hours', date) AS uur, 
						DATE_PART('minutes', date) AS minuut
					FROM 
						foto_reacties
					WHERE 
						foto = " . $photo->get('id') . "
					ORDER BY 
						date");
			
			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get a certain number of photos previous to a photo
		  * @photo a #DataIter representing the photo
		  * @num optional; the number of photos to get previous to 
		  * photo. The default value is -1 which means that only one
		  * previous photo is being looked up and that it returns
		  * that photo instead of an array of photos
		  *
		  * @result an array of #DataIter or just a single #DataIter if
		  * num is -1
		  */
		function get_previous_photo($photo, $num = -1) {
			$rows = $this->db->query("
					SELECT 
						*
					FROM 
						fotos
					WHERE 
						boek = " . $photo->get('boek') . " AND
						id < " . $photo->get('id') . "
					ORDER BY 
						id DESC
					LIMIT " . ($num != -1 ? intval($num) : 1));
			
			if ($num == -1)
				if ($rows && count($rows) != 0) {
					return $this->_row_to_iter($rows[0]);
				} else {
					return null;
				}
			else
				return $this->_rows_to_iters($rows);	
		}
		
		/**
		  * Get a certain number of photos next to a photo
		  * @photo a #DataIter representing the photo
		  * @num optional; the number of photos to get next to 
		  * photo. The default value is -1 which means that only one
		  * next photo is being looked up and that it returns
		  * that photo instead of an array of photos
		  *
		  * @result an array of #DataIter or just a single #DataIter if
		  * num is -1
		  */
		function get_next_photo($photo, $num = -1) {
			$rows = $this->db->query("
					SELECT 
						*
					FROM 
						fotos
					WHERE 
						boek = " . $photo->get('boek') . " AND
						id > " . $photo->get('id') . "
					ORDER BY 
						id ASC
					LIMIT " . ($num != -1 ? intval($num) : 1));
			
			if ($num == -1)
				if ($rows && count($rows) != 0) {
					return $this->_row_to_iter($rows[0]);
				} else {
					return null;
				}
			else
				return $this->_rows_to_iters($rows);		
		}
		
		/**
		  * Get the book before a certain book
		  * @book a #DataIter representing a book
		  *
		  * @result a #DataIter
		  */
		function get_previous_book($book) {
			if (!$book)
				return null;

			$row = $this->db->query_first("
					SELECT 
						*
					FROM 
						foto_boeken
					WHERE 
						parent = " . $book->get('parent') . " AND
						((date > '" . $this->escape_string($book->get('date')) . "' AND
						id <> " . $book->get('id') . ") OR (
						date = '" . $this->escape_string($book->get('date')) . "' AND
						id < " . $book->get('id') . "))
					ORDER BY 
						date ASC, 
						id
					LIMIT 1");
			
			return $this->_row_to_iter($row);
		}
		
		/**
		  * Get the book after a certain book
		  * @book a #DataIter representing a book
		  *
		  * @result a #DataIter
		  */
		function get_next_book($book) {
			if (!$book)
				return null;

			$row = $this->db->query_first("
					SELECT 
						*
					FROM 
						foto_boeken
					WHERE 
						parent = " . $book->get('parent') . " AND
						((date < '" . $this->escape_string($book->get('date')) . "' AND
						id <> " . $book->get('id') . ") OR (
						date = '" . $this->escape_string($book->get('date')) . "' AND
						id > " . $book->get('id') . "))
					ORDER BY 
						date DESC, 
						id
					LIMIT 1");

			return $this->_row_to_iter($row);
		}
		
		/**
		  * Get the parent of a book
		  * @book a #DataIter representing a book
		  *
		  * @result a #DataIter or null if the book has no parent
		  */
		function get_parent($book) {
			if (!$book)
				return null;

			if (!is_object($book))
				$book = $this->get_book(intval($book));
			
			if (!$book->get('parent'))
				return null;
			
			$row = $this->db->query_first('
					SELECT 
						*
					FROM 
						foto_boeken
					WHERE 
						id = ' . $book->get('parent'));
			
			return $this->_row_to_iter($row);
		}
		
		/**
		  * Get all the books in a certain book
		  * @book a #DataIter representing a book
		  *
		  * @result an array of #DataIter
		  */
		function get_children($book) {
			if (!$book)
				$parent = 0;
			else
				$parent = $book->get('id');
			
			$rows = $this->db->query('
					SELECT 
						foto_boeken.*, 
						COUNT(foto_boeken.id) AS num_photos, 
						fotos.boek AS has_photos
					FROM 
						foto_boeken
					LEFT JOIN fotos ON (foto_boeken.id = fotos.boek)
					WHERE 
						foto_boeken.parent = ' . $parent . '
					GROUP BY fotos.boek, 
						foto_boeken.id, 
						foto_boeken.parent, 
						foto_boeken.beschrijving, 
						foto_boeken.date, 
						foto_boeken.titel, 
						foto_boeken.fotograaf
					ORDER BY date DESC, foto_boeken.id');

			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get the number of books in a book
		  * @book a #DataIter representing a book
		  *
		  * @result the number of books
		  */
		function get_num_children($book) {
			if (!$book)
				$parent = 0;
			else
				$parent = $book->get('id');
			
			return $this->db->query_value('
					SELECT 
						COUNT(*) 
					FROM 
						foto_boeken 
					WHERE 
						parent = ' . $parent);
		}
		
		/**
		  * Get a certain number of randomly selected photos
		  * @num the number of random photos to select
		  *
		  * @result an array of #DataIter
		  */
		function get_random_photos($num) {
			$rows = $this->db->query("
					SELECT 
						fotos.thumburl, 
						fotos.id,
						fotos.boek,
						DATE_PART('year', foto_boeken.date) AS jaar,
						foto_boeken.titel
					FROM 
						fotos,
						foto_boeken
					WHERE
						fotos.boek = foto_boeken.id
					ORDER BY 
						RANDOM()
					LIMIT " . intval($num));

			return $this->_rows_to_iters($rows);		
		}
		
		/**
		  * Get photos in a book
		  * @book a #DataIter representing a book
		  * @max optional; the maximum number of photos to get (specify
		  * 0 for no maximum)
		  * @random optional; whether to order the photos randomly
		  *
		  * @result an array of #DataIter
		  */
		function get_photos($book, $max = 0, $random = false) {
			if (!$book)
				$id = 0;
			else
				$id = $book->get('id');
			
			$rows = $this->db->query('
					SELECT fotos.*, 
						COUNT(fotos.id) AS num_reacties, 
						foto_reacties.foto AS reactie' . 
						($random ? ', RANDOM() AS ord' : '') . '
					FROM 
						fotos
					LEFT JOIN foto_reacties ON (foto_reacties.foto = fotos.id)
					WHERE 
						boek = ' . $id . '
					GROUP BY 
						fotos.id, 
						fotos.boek, 
						fotos.url, 
						fotos.thumburl, 
						fotos.beschrijving, 
						foto_reacties.foto
					ORDER BY ' . ($random ? 'ord' : 'fotos.id ASC') . '
					' . ($max != 0 ? ('LIMIT ' . $max) : ''));

			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get the number of photos in a book
		  * @book a #DataIter representing a book
		  *
		  * @result the number of photos
		  */
		function get_num_photos($book) {
			if (!$book)
				$parent = 0;
			else
				$parent = $book->get('id');
			
			return $this->db->query_value('
					SELECT 
						COUNT(*) 
					FROM 
						fotos 
					WHERE 
						boek = ' . $parent);
		}
		
		/**
		  * Recursively get all the parents of a book
		  * @book a #DataIter representing a book
		  * @result the resulting array with all the parents
		  */
		function _get_parents_real($book, &$result) {	
			if (!$book)
				return;
			
			$parent = $this->get_parent($book);
			$result[] = $parent;
			
			$this->_get_parents_real($parent, $result);
		}
		
		/**
		  * Get all the parents of a book
		  * @book a #DataIter representing a book
		  *
		  * @result an array of #DataIter
		  */
		function get_parents($book) {
			$result = array();
			
			$this->_get_parents_real($book, $result);
			return array_reverse($result);
		}
		
		/**
		  * Get a certain number of last replies
		  * @num the number of replies to get
		  *
		  * @result an array of #DataIter
		  */
		function get_last_reacties($num) {
			$rows = $this->db->query("
					SELECT
						foto_reacties.*,
						DATE_PART('dow', foto_reacties.date) AS dagnaam, 
						DATE_PART('day', foto_reacties.date) AS datum, 
						DATE_PART('month', foto_reacties.date) AS maand, 
						DATE_PART('hours', foto_reacties.date) AS uur, 
						DATE_PART('minutes', foto_reacties.date) AS minuut,
						fotos.beschrijving,
						fotos.boek,
						foto_boeken.titel
					FROM 
						foto_reacties,
						fotos,
						foto_boeken
					WHERE
						fotos.id = foto_reacties.foto AND
						fotos.boek = foto_boeken.id
					ORDER BY
						date DESC
					LIMIT " . intval($num));

			return $this->_rows_to_iters($rows);
		}

		/**
		  * Delete a photo. This will automatically delete any replies
		  * for the photo
		  * @iter a #DataIter representing a photo
		  *
		  * @result whether or not the delete was successful
		  */
		function delete($iter) {
			$result = parent::delete($iter);
			
			/* Delete all reacties */
			$result = $result && $this->db->delete('foto_reacties', 'foto = ' . intval($iter->get('id')));

			return $result;
		}

		/**
		  * Insert a reply
		  * @iter a #DataIter representing a reply
		  *
		  * @result whether or not the insert was successful
		  */
		function insert_reactie($iter) {
			return $this->_insert('foto_reacties', $iter, false);
		}

		/**
		  * Insert a book
		  * @iter a #DataIter representing a book
		  *
		  * @result whether or not the insert was successful
		  */
		function insert_book($iter) {
			return $this->_insert('foto_boeken', $iter, false);
		}

		/**
		  * Delete a book thumbnail
		  * @iter a #DataIter representing a book thumbnail
		  *
		  * @result whether or not the delete was successful
		  */
		function delete_book_thumb($book) {
			return $this->db->delete('foto_boeken_thumb', 'boek = ' . $book->get('id'));
		}

		/**
		  * Delete a book. This will automatically remove all the
		  * photos in the book as well as the book thumbnail
		  * @iter a #DataIter representing a book
		  *
		  * @result whether or not the delete was successful
		  */		
		function delete_book($iter) {
			$result = $this->_delete('foto_boeken', $iter);
			
			$this->delete_book_thumb($iter);
			$photos = $this->get_photos($iter);
			
			foreach ($photos as $photo)
				$this->delete($photo);
			
			return $result;
		}

		/**
		  * Update a book
		  * @iter a #DataIter representing a book
		  *
		  * @result whether or not the update was successful
		  */		
		function update_book($iter) {
			return $this->_update('foto_boeken', $iter);
		}

		/**
		  * Insert a book thumbnail
		  * @iter a #DataIter representing a book thumbnail
		  *
		  * @result whether or not the insert was successful
		  */
		function insert_book_thumbnail($iter) {
			return $this->_insert('foto_boeken_thumb', $iter);
		}

		/**
		  * Update a book thumbnail
		  * @iter a #DataIter representing a book thumbnail
		  *
		  * @result whether or not the update was successful
		  */	
		function update_book_thumbnail($iter) {
			return $this->db->update('foto_boeken_thumb', $iter->get_changed_values(), 
					'boek = ' . $iter->get('boek') . ' AND
					theme = \'' . $iter->get('theme') . '\'', 
					$iter->get_literals());
		}
	}
?>
