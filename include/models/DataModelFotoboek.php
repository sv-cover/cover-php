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
				$q .= " AND titel NOT ILIKE 'chantagemap%' AND titel NOT ILIKE 'download grote foto''s%'";
				// en je mag ook de foto's van voorgaande jaren niet zien! Dit doen we lekker op ID :'). 833 is het eerste album van 2012, die mag op dit moment zichtbaar zijn
				$q .= " AND id >= 833;";
			}
			$row = $this->db->query_first($q);
			return $this->_row_to_iter($row);
		}

		/**
		  * Get a random photo book
		  * @count the number of latest photo books to choose from
		  *
		  * @result a #DataIter
		  */
		function get_random_book($count = 10) {
			$q = "
				SELECT 
					c.*, 
					(TRIM(to_char(DATE_PART('day', c.date), '00')) || '-' ||
					 TRIM(to_char(DATE_PART('month', c.date), '00')) || '-' ||
					 DATE_PART('year', c.date)) AS datum
				FROM 
					foto_boeken c
				LEFT JOIN
					foto_boeken p
					ON p.parent = c.id
				WHERE
					c.titel NOT ILIKE 'chantagemap%'
					AND c.titel NOT ILIKE 'download grote foto''s%'
					AND p.id IS NULL
					AND c.date IS NOT NULL
				ORDER BY
					c.date DESC
				LIMIT " . intval($count);

			// Select the last 10 books
			$rows = $this->db->query($q);

			// Pick a random fotoboek
			$book = $rows[rand(0, count($rows) - 1)];

			return $this->_row_to_iter($book);
		}
		
		/**
		  * Get the photo book thumbnail
		  * @book a #DataIter representing a book
		  *
		  * @result a #DataIter
		  */
		function get_book_thumbnail($book) {
			$row = $this->db->query_first(	"
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
		function get_previous_photo(DataIter $photo, $num = -1) {
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
		function get_next_photo(DataIter $photo, $num = -1) {
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
		function get_previous_book(DataIter $book) {
			if (!$book || !$book->has('date'))
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
		function get_next_book(DataIter $book) {
			if (!$book || !$book->has('date'))
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
			
			$select = 'SELECT
				foto_boeken.*, 
				COUNT(foto_boeken.id) AS num_photos, 
				fotos.boek AS has_photos';

			$from = 'FROM
				foto_boeken';

			$joins = '
				LEFT JOIN fotos ON
					foto_boeken.id = fotos.boek';

			$where = sprintf('WHERE
				foto_boeken.parent = %d', $parent);

			$group_by = 'GROUP BY
				fotos.boek, 
				foto_boeken.id, 
				foto_boeken.parent, 
				foto_boeken.beschrijving, 
				foto_boeken.date, 
				foto_boeken.titel, 
				foto_boeken.fotograaf';

			$order_by = 'ORDER BY
				date DESC,
				foto_boeken.id';

			if (logged_in())
			{
				$select = sprintf('
					WITH RECURSIVE book_children (id, date, parents) AS (
						SELECT id, date, ARRAY[0] FROM foto_boeken WHERE parent = %d
					UNION ALL
						SELECT f_b.id, f_b.date, b_c.parents || f_b.parent
						FROM book_children b_c, foto_boeken f_b
						WHERE b_c.id = f_b.parent
				)
				', $parent) . $select;

				$select .= sprintf(",
					CASE
						WHEN
							COUNT(nullif(
								foto_boeken.date > '%1\$d-08-23' AND -- only photo books from just before I started
								f_b_v.last_visit IS NULL, false)) -- and which I didn't visit yet
							+ COUNT(nullif(b_c.id IS NOT NULL AND (
								b_c.date >= '%1\$d-08-23' AND
								f_b_c_v.last_visit IS NULL
							), false)) > 0 
						THEN 'unread'
						ELSE 'read'
					END read_status", logged_in('beginjaar'));

				$joins .= sprintf('
					LEFT JOIN book_children b_c ON
						foto_boeken.id = ANY(b_c.parents)
					LEFT JOIN foto_boeken_visit f_b_v ON
						f_b_v.boek_id = foto_boeken.id
						AND f_b_v.lid_id = %d
					LEFT JOIN foto_boeken_visit f_b_c_v ON
						f_b_c_v.boek_id = b_c.id
						AND f_b_c_v.lid_id = %1$d', logged_in('id'));
			}
			else
			{
				$select .= ',
					\'read\' as read_status';
			}

			$rows = $this->db->query("$select $from $joins $where $group_by $order_by");

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
						fotos.url,
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
		function get_photos(DataIter $book = null, $max = 0, $random = false) {
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
		  * Insert a book
		  * @iter a #DataIter representing a book
		  *
		  * @result whether or not the insert was successful
		  */
		function insert_book($iter) {
			return $this->_insert('foto_boeken', $iter, true);
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

		public function mark_read($lid_id, DataIter $book)
		{
			try {
				$this->db->insert('foto_boeken_visit',
					array(
						'lid_id' => $lid_id,
						'boek_id' => $book->get_id(),
						'last_visit' => 'NOW()'
					),
					array('last_visit'));
			} catch (Exception $e) {
				$this->db->update('foto_boeken_visit',
					array('last_visit' => 'NOW()'),
					sprintf('lid_id = %d AND boek_id = %d', $lid_id, $book->get_id()),
					array('last_visit'));
			}
		}

		protected function mark_children_read($lid_id, DataIter $book)
		{
			$query = sprintf('
				WITH RECURSIVE book_children (id, titel, parents) AS (
						SELECT id, titel, ARRAY[0] FROM foto_boeken WHERE parent = %2$d
					UNION ALL
						SELECT f_b.id, f_b.titel, b_c.parents || f_b.parent
						FROM book_children b_c, foto_boeken f_b
						WHERE b_c.id = f_b.parent
				)
				INSERT INTO foto_boeken_visit (lid_id, boek_id, last_visit)
				SELECT %1$d, b_c.id, NOW() FROM book_children b_c
				WHERE NOT EXISTS (
						SELECT 1
						FROM foto_boeken_visit
						WHERE lid_id = %1$d AND boek_id = b_c.id)', $lid_id, $book->get_id());

			$this->db->query($query);
		}

		public function mark_read_recursively($lid_id, DataIter $book)
		{
			$this->mark_read($lid_id, $book);

			$this->mark_children_read($lid_id, $book);
		}
	}
?>
