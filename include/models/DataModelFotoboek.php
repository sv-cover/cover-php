<?php
	require_once 'include/search.php';
	require_once 'include/data/DataModel.php';
	require_once 'include/policies/policy.php';

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

	class DataIterPhotobook extends DataIter implements SearchResult
	{
		public function get_books()
		{
			return $this->model->get_children($this);
		}

		public function get_photos()
		{
			return $this->model->get_photos($this);
		}

		public function count_books()
		{
			return $this->get('num_books');
		}

		public function count_photos()
		{
			return $this->get('num_photos');
		}

		public function get_next_photo(DataIterPhoto $current, $num = -1)
		{
			$photos = $this->get_photos();

			foreach ($photos as $index => $photo)
				if ($photo->get_id() == $current->get_id())
					break;

			if (count($photos) == $index + 1)
				return array();

			return array_slice($photos, $index + 1, min(max($num, 0), count($photos) - $index));
		}

		public function get_previous_photo(DataIterPhoto $current, $num = -1)
		{
			$photos = $this->get_photos();

			foreach ($photos as $index => $photo)
				if ($photo->get_id() == $current->get_id())
					break;

			if ($index === 0)
				return array();

			return array_reverse(array_slice($photos,
				max($index - max($num, 0), 0),
				min(max($num, 0), $index)));
		}

		public function get_parent()
		{
			return $this->model->get_book($this->get('parent'));
		}

		public function get_next_book()
		{
			return $this->model->get_next_book($this);
		}

		public function get_previous_book()
		{
			return $this->model->get_previous_book($this);
		}

		public function get_search_relevance()
		{
			$date = DateTime::createFromFormat('d-m-Y', $this->get('datum'));

			$recency = $date
				? (1.0 / (time() - $date->getTimestamp()))
				: 0.0;

			return 0.7 + $recency;
		}

		public function get_search_type()
		{
			return 'fotoboek';
		}
	}

	class DataIterRootPhotobook extends DataIterPhotobook
	{
		public function get_books()
		{
			$books = parent::get_books();

			if (logged_in()) {
				$books[] = get_model('DataModelFotoboekLikes')->get_book(logged_in_member());
				$books[] = get_model('DataModelFotoboekFaces')->get_book(array(logged_in_member()));
			}
			
			return $books;
		}

		public function count_books()
		{
			return parent::count_books() + (logged_in() ? 2 : 0);
		}

		public function get_next_book()
		{
			return null;
		}

		public function get_previous_book()
		{
			return null;
		}

		public function get_parent()
		{
			return null;
		}
	}

	/**
	  * A class implementing photo data
	  */
	class DataModelFotoboek extends DataModel
	{
		const VISIBILITY_PUBLIC = 0;
		const VISIBILITY_MEMBERS = 1;
		const VISIBILITY_ACTIVE_MEMBERS = 2;
		const VISIBILITY_PHOTOCEE = 3;

		public $dataiter = 'DataIterPhoto';

		public function __construct($db)
		{
			parent::__construct($db, 'fotos');
		}
		
		/**
		  * Get a photo book
		  * @id the id of the book
		  *
		  * @result a #DataIter
		  */
		function get_book($id)
		{
			if ($id == 0)
				return $this->get_root_book();

			$q = sprintf("
					SELECT 
						f_b.*,
						COUNT(DISTINCT f.id) as num_photos,
						COUNT(DISTINCT c_f_b.id) as num_books,
						(TRIM(to_char(DATE_PART('day', f_b.date), '00')) || '-' ||
						 TRIM(to_char(DATE_PART('month', f_b.date), '00')) || '-' ||
						 DATE_PART('year', f_b.date)) AS datum
					FROM 
						foto_boeken f_b
					LEFT JOIN fotos f ON
						f.boek = f_b.id
					LEFT JOIN foto_boeken c_f_b ON
						c_f_b.parent = f_b.id
					WHERE 
						f_b.id = %d
					GROUP BY
						f_b.id
					", $id);
			
			$row = $this->db->query_first($q);

			return $this->_row_to_iter($row, 'DataIterPhotobook');
		}

		public function search($keywords, $limit = null)
		{
			$sql_atoms = array_map(function($keyword) {
				return sprintf("f_b.titel ILIKE '%%%s%%'", $this->db->escape_string($keyword));
			}, parse_search_query($keywords));

			$query = "SELECT 
					f_b.*,
					COUNT(DISTINCT f.id) as num_photos,
					COUNT(DISTINCT c_f_b.id) as num_books,
					(TRIM(to_char(DATE_PART('day', f_b.date), '00')) || '-' ||
					 TRIM(to_char(DATE_PART('month', f_b.date), '00')) || '-' ||
					 DATE_PART('year', f_b.date)) AS datum
				FROM 
					foto_boeken f_b
				LEFT JOIN fotos f ON
					f.boek = f_b.id
				LEFT JOIN foto_boeken c_f_b ON
					c_f_b.parent = f_b.id
				WHERE 
					" . implode(' AND ', $sql_atoms) . "
				GROUP BY
					f_b.id
				ORDER BY
					f_b.date DESC";

			if ($limit !== null)
				$query .= sprintf(' LIMIT %d', $limit);

			return $this->_rows_to_iters($this->db->query($query), 'DataIterPhotobook');
		}

		function get_root_book()
		{
			$num_books = $this->db->query_value('SELECT COUNT(id) FROM foto_boeken WHERE parent = 0');
			
			$num_photos = $this->db->query_value('SELECT COUNT(id) FROM fotos WHERE boek = 0');

			return new DataIterRootPhotobook($this, 0, array(
				'titel' => __('Fotoboek'),
				'num_books' => $num_books,
				'num_photos' => $num_photos
			));
		}

		/**
		  * Get a random photo book
		  * @count the number of latest photo books to choose from
		  *
		  * @result a #DataIter
		  */
		function get_random_book($count = 10)
		{
			$q = sprintf("
				SELECT 
					c.id
				FROM 
					foto_boeken c
				LEFT JOIN
					fotos f
					ON f.boek = c.id
				WHERE
					c.visiblity <= %d
					AND c.date IS NOT NULL
				GROUP BY
					c.id
				HAVING
					COUNT(f.id) > 0
				ORDER BY
					c.date DESC
				LIMIT %d",
				get_policy($this)->get_access_level(),
				intval($count));

			// Select the last $count books
			$rows = $this->db->query($q);

			// Pick a random fotoboek
			$book = $rows[rand(0, count($rows) - 1)];

			return $this->get_book($book['id']);
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
						id
					FROM 
						foto_boeken
					WHERE 
						parent = " . $book->get('parent') . " AND
						visibility <= " . get_policy($this)->get_access_level() . " AND
						((date > '" . $this->db->escape_string($book->get('date')) . "' AND
						id <> " . $book->get('id') . ") OR (
						date = '" . $this->db->escape_string($book->get('date')) . "' AND
						id < " . $book->get('id') . "))
					ORDER BY 
						date ASC, 
						id
					LIMIT 1");
			
			return $row ? $this->get_book($row['id']) : null;
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
						id
					FROM 
						foto_boeken
					WHERE 
						parent = " . $book->get('parent') . " AND
						visibility <= " . get_policy($this)->get_access_level() . " AND
						((date < '" . $this->db->escape_string($book->get('date')) . "' AND
						id <> " . $book->get('id') . ") OR (
						date = '" . $this->db->escape_string($book->get('date')) . "' AND
						id > " . $book->get('id') . "))
					ORDER BY 
						date DESC, 
						id
					LIMIT 1");

			return $row ? $this->get_book($row['id']) : null;
		}
		
		/**
		  * Get all the books in a certain book
		  * @book a #DataIter representing a book
		  *
		  * @result an array of #DataIter
		  */
		function get_children(DataIterPhotobook $book) {
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
				foto_boeken.visibility <= %d
				AND foto_boeken.parent = %d',
				get_policy($this)->get_access_level(),
				$book->get_id());

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
					WITH RECURSIVE book_children (id, date, visibility, parents) AS (
						SELECT id, date, visibility, ARRAY[0] FROM foto_boeken WHERE parent = %d
					UNION ALL
						SELECT f_b.id, f_b.date, f_b.visibility, b_c.parents || f_b.parent
						FROM book_children b_c, foto_boeken f_b
						WHERE b_c.id = f_b.parent
				)
				', $book->get_id()) . $select;

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
						b_c.visibility <= %d
						AND foto_boeken.id = ANY(b_c.parents)
					LEFT JOIN foto_boeken_visit f_b_v ON
						f_b_v.boek_id = foto_boeken.id
						AND f_b_v.lid_id = %d
					LEFT JOIN foto_boeken_visit f_b_c_v ON
						f_b_c_v.boek_id = b_c.id
						AND f_b_c_v.lid_id = %2$d',
						get_policy($this)->get_access_level(),
						logged_in('id'));
			}
			else
			{
				$select .= ',
					\'read\' as read_status';
			}

			$rows = $this->db->query("$select $from $joins $where $group_by $order_by");

			return $this->_rows_to_iters($rows, 'DataIterPhotobook');
		}

		protected function _generate_query($where)
		{
			return "
				SELECT
					{$this->table}.*,
					COUNT(DISTINCT foto_reacties.id) AS num_reacties
				FROM
					{$this->table}
				LEFT JOIN foto_reacties ON
					foto_reacties.foto = {$this->table}.id
				" . ($where ? 'WHERE ' . $where : '') . "
				GROUP BY
					{$this->table}.id
				ORDER BY
					{$this->table}.id ASC";
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
						fotos.*,
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
		public function get_photos(DataIterPhotobook $book)
		{
			return $this->find(sprintf('boek = %d', $book->get_id()));
		}

		public function get_photos_recursive(DataIterPhotobook $book, $max = 0, $random = false)
		{
			$query = sprintf('
				WITH RECURSIVE book_children (id, visibility, parents) AS (
						SELECT id, visibility, ARRAY[id] FROM foto_boeken WHERE id = %d
					UNION ALL
						SELECT f_b.id,  f_b.visibility, b_c.parents || f_b.parent
						FROM book_children b_c, foto_boeken f_b
						WHERE b_c.id = f_b.parent
				)
				SELECT
					f.*
				FROM
					book_children b_c
				LEFT JOIN fotos f ON
					f.boek = b_c.id
				WHERE
					b_c.visibility <= %d
				GROUP BY
					f.id',
					get_policy($this)->get_access_level(),
					$book->get_id());

			if ($random)
				$query .= ' ORDER BY RANDOM()';

			if ($max > 0)
				$query .= sprintf(' LIMIT %d', $max);

			$rows = $this->db->query($query);
			
			return $this->_rows_to_iters($rows, 'DataIterPhoto');
		}
		
		/**
		  * Get all the parents of a book
		  * @book a #DataIter representing a book
		  *
		  * @result an array of #DataIter
		  */
		function get_parents(DataIterPhotobook $book) {
			$result = array();

			while ($book = $book->get_parent())
				$result[] = $book;
			
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
			$result = $result && $this->db->delete('foto_reacties', 'foto = ' . intval($iter->get_id()));

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
				WITH RECURSIVE book_children (id, visiblity, parents) AS (
						SELECT id, visiblity, ARRAY[0] FROM foto_boeken WHERE parent = %2$d
					UNION ALL
						SELECT f_b.id, f_b.visibility, b_c.parents || f_b.parent
						FROM book_children b_c, foto_boeken f_b
						WHERE b_c.id = f_b.parent
				)
				INSERT INTO foto_boeken_visit (lid_id, boek_id, last_visit)
				SELECT %1$d, b_c.id, NOW() FROM book_children b_c
				WHERE 
					b_c.visibility <= %3$d
					AND NOT EXISTS (
						SELECT 1
						FROM foto_boeken_visit
						WHERE lid_id = %1$d AND boek_id = b_c.id)',
				$lid_id,
				$book->get_id(),
				get_policy($this)->get_access_level());

			$this->db->query($query);
		}

		public function mark_read_recursively($lid_id, DataIterPhotobook $book)
		{
			$this->mark_read($lid_id, $book);

			$this->mark_children_read($lid_id, $book);
		}
	}
?>
