<?php
	require_once('data/DataModel.php');

	class DataIterPhoto extends DataIter
	{
		public function get_size()
		{
			if (!$this->get('width') || !$this->get('height'))
			{
				$data = @getimagesize($this->get('url'));

				if (!$data)
					return null;

				$this->set('width', $data[0]);
				$this->set('height', $data[1]);
				$this->model->update($this);
			}

			return array($this->get('width'), $this->get('height'));
		}

		public function get_thumb_size()
		{
			if (!$this->get('thumbwidth') || !$this->get('thumbheight'))
			{
				$data = @getimagesize($this->get('thumburl'));

				if (!$data)
					return null;

				$this->set('thumbwidth', $data[0]);
				$this->set('thumbheight', $data[1]);
				$this->model->update($this);
			}

			return array($this->get('thumbwidth'), $this->get('thumbheight'));
		}

		public function get_book()
		{
			return $this->model->get_book($this->get('boek'));
		}
	}

	class DataIterPhotobook extends DataIter
	{
		public function get_photos($max = 0, $random = false)
		{
			return $this->model->get_photos($this, $max, $random);
		}
	}

	/**
	  * A class implementing photo data
	  */
	class DataModelFotoboek extends DataModel
	{
		public $dataiter = 'DataIterPhoto';

		function DataModelFotoboek($db) {
			parent::DataModel($db, 'fotos');
		}
		
		/**
		  * Get a photo book
		  * @id the id of the book
		  *
		  * @result a #DataIter
		  */
		function get_book($id)
		{
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
			
			$row = $this->db->query_first($q);

			return $this->_row_to_iter($row, 'DataIterPhotobook');
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

			return $this->_row_to_iter($book, 'DataIterPhotobook');
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
		function get_previous_photo(DataIterPhoto $photo, $num = -1) {
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
				return $this->_rows_to_iters($rows, 'DataIterPhoto');
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
		function get_next_photo(DataIterPhoto $photo, $num = -1) {
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
				return $this->_rows_to_iters($rows, 'DataIterPhoto');		
		}
		
		/**
		  * Get the book before a certain book
		  * @book a #DataIter representing a book
		  *
		  * @result a #DataIter
		  */
		function get_previous_book(DataIterPhotobook $book) {
			if (!$book->has('date'))
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
			
			return $this->_row_to_iter($row, 'DataIterPhotobook');
		}
		
		/**
		  * Get the book after a certain book
		  * @book a #DataIter representing a book
		  *
		  * @result a #DataIter
		  */
		function get_next_book(DataIterPhotobook $book) {
			if (!$book->has('date'))
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

			return $this->_row_to_iter($row, 'DataIterPhotobook');
		}
		
		/**
		  * Get the parent of a book
		  * @book a #DataIter representing a book
		  *
		  * @result a #DataIter or null if the book has no parent
		  */
		function get_parent(DataIterPhotobook $book) {
			if (!$book->get('parent'))
				return null;
			
			$row = $this->db->query_first('
					SELECT 
						*
					FROM 
						foto_boeken
					WHERE 
						id = ' . $book->get('parent'));
			
			return $this->_row_to_iter($row, 'DataIterPhotobook');
		}
		
		/**
		  * Get all the books in a certain book
		  * @book a #DataIter representing a book
		  *
		  * @result an array of #DataIter
		  */
		function get_children(DataIterPhotobook $book = null) {
			if ($book === null)
				$parent = 0;
			else
				$parent = $book->get('id');
			
			$select = 'SELECT
				foto_boeken.*, 
				COUNT(DISTINCT fotos.id) AS num_photos, 
				COUNT(DISTINCT child_books.id) as num_books';

			$from = 'FROM
				foto_boeken';

			$joins = '
				LEFT JOIN fotos ON
					foto_boeken.id = fotos.boek
				LEFT JOIN foto_boeken child_books ON
					child_books.parent = foto_boeken.id';

			$where = sprintf('WHERE
				foto_boeken.parent = %d', $parent);

			$group_by = 'GROUP BY
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

			return $this->_rows_to_iters($rows, 'DataIterPhotobook');
		}
		
		/**
		  * Get the number of books in a book
		  * @book a #DataIter representing a book
		  *
		  * @result the number of books
		  */
		function get_num_children(DataIterPhotobook $book = null) {
			if ($book === null)
				$parent = 0;
			else
				$parent = $book->get('id');
			
			return $this->db->query_value(sprintf('
					SELECT 
						COUNT(*) 
					FROM 
						foto_boeken 
					WHERE 
						parent = %d', $parent));
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

			return $this->_rows_to_iters($rows, 'DataIterPhoto');		
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
		function get_photos(DataIterPhotobook $book = null, $max = 0, $random = false) {
			if ($book === null)
				$id = 0;
			else
				$id = $book->get('id');
			
			$rows = $this->db->query('
					SELECT fotos.*, 
						COUNT(DISTINCT foto_reacties.id) AS num_reacties' . 
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
						fotos.beschrijving
					ORDER BY ' . ($random ? 'ord' : 'fotos.id ASC') . '
					' . ($max != 0 ? ('LIMIT ' . $max) : ''));

			return $this->_rows_to_iters($rows, 'DataIterPhoto');
		}

		function get_photos_recursive(DataIterPhotobook $book = null, $max = 0, $random = false)
		{
			$id = $book === null ? 0 : $book->get_id();

			$query = sprintf('
				WITH RECURSIVE book_children (id, parents) AS (
						SELECT id, ARRAY[id] FROM foto_boeken WHERE id = %d
					UNION ALL
						SELECT f_b.id,  b_c.parents || f_b.parent
						FROM book_children b_c, foto_boeken f_b
						WHERE b_c.id = f_b.parent
				)
				SELECT
					f.*
				FROM
					book_children b_c
				LEFT JOIN fotos f ON
					f.boek = b_c.id
				GROUP BY
					f.id', $id);

			if ($random)
				$query .= ' ORDER BY RANDOM()';

			if ($max > 0)
				$query .= sprintf(' LIMIT %d', $max);

			$rows = $this->db->query($query);
			
			return $this->_rows_to_iters($rows, 'DataIterPhoto');
		}
		
		/**
		  * Get the number of photos in a book
		  * @book a #DataIter representing a book
		  *
		  * @result the number of photos
		  */
		function get_num_photos(DataIterPhotobook $book = null) {
			if ($book === null)
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
		function get_parents(DataIterPhotobook $book = null) {
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
		function delete(DataIterPhoto $iter) {
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
		function insert_book(DataIterPhotobook $iter) {
			return $this->_insert('foto_boeken', $iter, true);
		}

		/**
		  * Delete a book. This will automatically remove all the
		  * photos in the book.
		  * @iter a #DataIter representing a book
		  *
		  * @result whether or not the delete was successful
		  */		
		function delete_book(DataIterPhotobook $iter) {
			$result = $this->_delete('foto_boeken', $iter);
			
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
		function update_book(DataIterPhotobook $iter) {
			return $this->_update('foto_boeken', $iter);
		}

		public function mark_read($lid_id, DataIterPhotobook $book)
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

		protected function mark_children_read($lid_id, DataIterPhotobook $book)
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

		public function mark_read_recursively($lid_id, DataIterPhotobook $book)
		{
			$this->mark_read($lid_id, $book);

			$this->mark_children_read($lid_id, $book);
		}
	}
?>
