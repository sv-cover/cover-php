<?php
	require_once 'include/search.php';
	require_once 'include/data/DataModel.php';
	require_once 'include/policies/policy.php';

	class DataIterPhoto extends DataIter
	{
		const EXIF_ORIENTATION_180 = 3;
		const EXIF_ORIENTATION_90_RIGHT = 6;
		const EXIF_ORIENTATION_90_LEFT = 8;

		const LANDSCAPE = 'landscape';
		const PORTRAIT = 'portrait';
		const SQUARE = 'square';

		public function get_size()
		{
			return array($this->get('width'), $this->get('height'));
		}

		public function get_scaled_size($max_width = null, $max_height = null)
		{
			$size = $this->get_size();

			if ($max_width) {
				$width = $max_width;
				$height = round($max_width * ($size[1] / $size[0]));
			}
			
			if (!$max_width || ($max_height && $height > $max_height)) {
				$height = $max_height;
				$width = round($max_height * ($size[0] / $size[1]));
			}

			return array($width, $height, $width / $size[0]);
		}

		public function get_orientation()
		{
			list($width, $height) = $this->get_size();

			if ($width == $height)
				return self::SQUARE;
			if ($width > $height)
				return self::LANDSCAPE;
			else
				return self::PORTRAIT;
		}

		public function compute_size()
		{
			if (!$this->file_exists())
				throw new NotFoundException("Could not find original file {$this->get('filepath')}");

			if ($exif_data = $this->get_exif_data()) {
				$size = [
					'width' => $exif_data['COMPUTED']['Width'],
					'height' => $exif_data['COMPUTED']['Height']
				];

				if (isset($exif_data['Orientation'])
					&& ($exif_data['Orientation'] == self::EXIF_ORIENTATION_90_LEFT
						|| $exif_data['Orientation'] == self::EXIF_ORIENTATION_90_RIGHT))
					list($size['width'], $size['height']) = [$size['height'], $size['width']];

				return $size;
			}
			else if ($size = @getimagesize($this->get_full_path()))
				return [
					'width' => $size[0],
					'height' => $size[1]
				];
			else
				throw new RuntimeException("Could not determine image dimensions of photo {$this->get('filepath')}");
		}

		public function compute_hash()
		{
			if (!$this->file_exists())
				throw new NotFoundException("Could not find original file {$this->get('filepath')}");

			return crc32_file($this->get_full_path());
		}

		public function compute_created_on_timestamp()
		{
			if (!$this->file_exists())
				throw new NotFoundException("Could not find original file {$this->get('filepath')}");

			$exif_data = $this->get_exif_data();

			return strftime('%Y-%m-%d %H:%M:%S', isset($exif_data['DateTimeOriginal'])
				? strtotime($exif_data['DateTimeOriginal'])
				: $exif_data['FileDateTime']);
		}

		public function original_has_changed()
		{
			return $this->compute_hash() == $this->get('filehash');
		}

		public function get_book()
		{
			return $this->model->get_book($this->get('boek'));
		}

		public function get_full_path()
		{
			return path_concat(get_config_value('path_to_photos'), $this->get('filepath'));
		}

		public function get_url($width = null, $height = null)
		{
			$url = get_config_value('url_to_scaled_photo', 'fotoboek.php?view=scaled');

			$params = array('photo' => $this->get_id());

			if ($width)
				$params['width'] = (int) $width;

			if ($height)
				$params['height'] = (int) $height;

			return edit_url($url, $params);
		}

		public function file_exists()
		{
			return file_exists($this->get_full_path());
		}

		public function get_resource($width = null, $height = null, $skip_cache = false)
		{
			if (!$this->file_exists())
				throw new NotFoundException("Could not find original file ({$this->get('filepath')}) of photo {$this->get_id()}.");
			
			// Special case of no width and height -> use original file
			if (!$width && !$height)
				return fopen($this->get_full_path(), 'rb');

			$scaled_path = sprintf(get_config_value('path_to_scaled_photo'), $this->get_id(), $width, $height);

			list($scaled_width, $scaled_height, $scale) = $this->get_scaled_size($width, $height);

			// If we are upscaling, just use the original image
			if ($scale > 1.0)
				$scaled_path = $this->get_full_path();

			// If we are using a scaled image but it doesn't exist, create it :D
			elseif (!file_exists($scaled_path)
				|| filesize($scaled_path) === 0
				|| $skip_cache)
			{
				if (!file_exists(dirname($scaled_path)))
					mkdir(dirname($scaled_path), 0777, true);
				
				$fhandle = fopen($scaled_path, 'wb');
				$imagick = new Imagick();
				$imagick->readImage($this->get_full_path());

				// Is it a GIF image? Scale each frame individually 
				if ($imagick->getImageFormat() == 'GIF')
				{
					$gifmagick = $imagick->coalesceImages();

					do {
						$gifmagick->resizeImage($scaled_width, $scaled_height, Imagick::FILTER_BOX, 1);
					} while ($gifmagick->nextImage());

					$imagick = $gifmagick->deconstructImages();
					$imagick->writeImagesFile($fhandle);
				}
				else
				{
					// Rotate the image according to the EXIF data
					switch($imagick->getImageOrientation())
					{
						case imagick::ORIENTATION_BOTTOMRIGHT:
							$imagick->rotateimage('#000', 180); // rotate 180 degrees 
							break; 

						case imagick::ORIENTATION_RIGHTTOP:
							$imagick->rotateimage('#000', 90); // rotate 90 degrees CW 
							break; 

						case imagick::ORIENTATION_LEFTBOTTOM:
							$imagick->rotateimage('#000', -90); // rotate 90 degrees CCW 
							break;
					}

					// Scale the image
					$imagick->scaleImage($scaled_width, $scaled_height);

					// Strip EXIF data
					$imagick->stripImage();

					// Write the image as a progressive JPEG
					$imagick->setImageFormat('jpg');
					$imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE);
					$imagick->writeImageFile($fhandle);
				}

				$imagick->destroy();
				fclose($fhandle);
			}
			
			return fopen($scaled_path, 'rb');
		}

		public function get_exif_data()
		{
			return exif_read_data($this->get_full_path());
		}

		public function get_file_size()
		{
			return filesize($this->get_full_path());
		}
	}

	class DataIterPhotobook extends DataIter implements SearchResult
	{
		public function get_books($metadata = null)
		{
			return $this->model->get_children($this, $metadata);
		}

		public function get_photos()
		{
			return $this->model->get_photos($this);
		}

		public function has_photo(DataIterPhoto $needle)
		{
			$photos = $this->get_photos();

			foreach ($photos as $photo)
				if ($photo->get_id() == $needle->get_id())
					return true;

			return false;
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

		public function get_absolute_url()
		{
			return sprintf('fotoboek.php?book=%s', urlencode($this->get_id()));
		}

		public function get_key_photos($limit)
		{
			$photos = $this->model->get_photos_recursive($this);

			if (!count($photos))
				return null;

			$likes_model = get_model("DataModelFotoboekLikes");
			$likes = $likes_model->count_for_photos($photos);

			usort($photos, function(DataIterPhoto $left, DataIterPhoto $right) use ($likes) {
				return $likes[$right->get_id()] - $likes[$left->get_id()];
			});

			return array_slice($photos, 0, $limit);
		}
	}

	class DataIterRootPhotobook extends DataIterPhotobook
	{
		public function get_books($metadata = null)
		{
			$books = parent::get_books($metadata);

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

		const READ_STATUS = 1;
		const NUM_BOOKS = 2;
		const NUM_PHOTOS = 4;

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
		public function get_book($id)
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

			if ($row === null)
				throw new DataIterNotFoundException($id, $this);

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

		public function get_root_book()
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
		public function get_random_book($count = 10)
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
					c.visibility <= %d
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
		public function get_previous_book(DataIterPhotobook $book)
		{
			$parent = $book->get_parent();

			if (!$parent) return null;

			$children = $parent->get_books();

			$index = array_usearch($book, $children, ['DataIter', 'is_same']);

			return $index !== null && isset($children[$index - 1])
				? $children[$index - 1]
				: null;
		}
		
		/**
		  * Get the book after a certain book
		  * @book a #DataIter representing a book
		  *
		  * @result a #DataIter
		  */
		public function get_next_book(DataIterPhotobook $book)
		{
			$parent = $book->get_parent();

			if (!$parent) return null;

			$children = $parent->get_books();

			$index = array_usearch($book, $children, ['DataIter', 'is_same']);

			return $index !== null && isset($children[$index + 1])
				? $children[$index + 1]
				: null;
		}
		
		/**
		  * Get all the books in a certain book
		  * @book a #DataIter representing a book
		  *
		  * @result an array of #DataIter
		  */
		public function get_children(DataIterPhotobook $book, $metadata = null)
		{
			// TODO When we use PHP 5.6 this can be put as default parameter. For now, PHP does not support 'evaluated' expressions there.
			if ($metadata === null)
				$metadata = self::READ_STATUS | self::NUM_BOOKS | self::NUM_PHOTOS;

			// TODO not query the book and photo counts if their flags are not passed to $metadata.
			
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
				foto_boeken.sort_index ASC NULLS FIRST,
				date DESC,
				foto_boeken.id';

			if (get_config_value('enable_photos_read_status', true) && logged_in() && $metadata & self::READ_STATUS)
			{
				$select = sprintf('
					WITH RECURSIVE book_children (id, date, last_update, visibility, parents) AS (
						SELECT id, date, last_update, visibility, ARRAY[0] FROM foto_boeken WHERE parent = %d
					UNION ALL
						SELECT f_b.id, f_b.date, f_b.last_update, f_b.visibility, b_c.parents || f_b.parent
						FROM book_children b_c, foto_boeken f_b
						WHERE b_c.id = f_b.parent
				)
				', $book->get_id()) . $select;

				$select .= ",
					f_b_read_status.read_status";

				$joins .= sprintf("
					LEFT JOIN (
						SELECT
							foto_boeken.id,
							CASE
								WHEN
									COUNT(nullif(
										foto_boeken.date > '%1\$d-08-23' AND -- only photo books from just before I started
										(f_b_v.last_visit < foto_boeken.last_update OR f_b_v.last_visit IS NULL), false)) -- and which I didn't visit yet
									+ COUNT(nullif(b_c.id IS NOT NULL AND (
										b_c.date >= '%1\$d-08-23' AND
										(f_b_c_v.last_visit < b_c.last_update OR f_b_c_v.last_visit IS NULL)
									), false)) > 0 
								THEN 'unread'
								ELSE 'read'
							END read_status
						FROM
							foto_boeken
						LEFT JOIN book_children b_c ON
							b_c.visibility <= %2\$d
							AND foto_boeken.id = ANY(b_c.parents)
						LEFT JOIN foto_boeken_visit f_b_v ON
							f_b_v.boek_id = foto_boeken.id
							AND f_b_v.lid_id = %3\$d
						LEFT JOIN foto_boeken_visit f_b_c_v ON
							f_b_c_v.boek_id = b_c.id
							AND f_b_c_v.lid_id = %3\$d
						GROUP BY
							foto_boeken.id
					) as f_b_read_status ON
						f_b_read_status.id = foto_boeken.id",
						logged_in('beginjaar'),
						get_policy($this)->get_access_level(),
						logged_in('id'));
				
				$group_by .= ",
					f_b_read_status.read_status";
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
		public function get_random_photos($num)
		{
			$rows = $this->db->query(sprintf("
					SELECT
						f.*,
						DATE_PART('year', foto_boeken.date) AS fotoboek_jaar,
						foto_boeken.titel as fotoboek_titel
					FROM 
						(SELECT
							fotos.id
						FROM
							fotos
						WHERE
							fotos.boek IN (
								SELECT
									foto_boeken.id
								FROM
									foto_boeken
								WHERE
									foto_boeken.visibility = %d
							)
						ORDER BY
							RANDOM()
						LIMIT %d) as f_ids
					LEFT JOIN fotos f ON
						f.id = f_ids.id
					LEFT JOIN foto_boeken ON
						foto_boeken.id = f.boek
					GROUP BY
						f.id,
						foto_boeken.date,
						foto_boeken.titel",
						self::VISIBILITY_PUBLIC,
						$num));

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
			$query = "
				SELECT
					fotos.*,
					COUNT(DISTINCT foto_reacties.id) AS num_reacties
				FROM
					fotos
				LEFT JOIN foto_reacties ON
					foto_reacties.foto = fotos.id
				WHERE
					boek = {$book->get_id()}
				GROUP BY
					fotos.id
				ORDER BY
					sort_index ASC NULLS FIRST,
					created_on ASC,
					added_on ASC";

			$rows = $this->db->query($query);
			
			return $this->_rows_to_iters($rows);
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
					f.id
				HAVING
					COUNT(f.id) > 0',
					$book->get_id(),
					get_policy($this)->get_access_level()); // BAD DEPENDENCY!

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
		public function get_parents(DataIterPhotobook $book)
		{
			$result = array();

			while ($book = $book->get_parent())
				$result[] = $book;
			
			return array_reverse($result);
		}
		
		/**
		  * Delete a photo. This will automatically delete any comments on
		  * and faces tagged in the photo due to database table constraints.
		  *
		  * @iter a #DataIter representing a photo;
		  * @result whether or not the delete was successful
		  */
		public function delete(DataIterPhoto $iter)
		{
			$result = parent::delete($iter);
			
			// Remove scaled versions of the image from the scaled image cache
			$filled_in_filter = preg_replace('/%d/', $iter->get_id(), get_config_value('path_to_scaled_photo'), 1);

			$filter = str_replace('%d', '*', $filled_in_filter);
			foreach (glob($filter) as $scaled_image_path)
				unlink($scaled_image_path);
			
			return $result;
		}

		/**
		 * Insert a photo into the database. Width, height and filehash are
		 * caculated if they are not already set.
		 *
		 * @param DataIterPhoto $photo to insert
		 * @return int|boolean the photo id on success, or false on failure
		 */
		public function insert(DataIterPhoto $iter)
		{
			// Determine width and height of the new image
			if (!$iter->has('width') || !$iter->has('height'))
				$iter->set_all($iter->compute_size());

			// Determine the CRC32 file hash, used for detecting changes later on
			if (!$iter->has('filehash'))
				$iter->set('filehash', $iter->compute_hash());

			if (!$iter->has('created_on'))
				$iter->set('created_on', $iter->compute_created_on_timestamp());

			if (!$iter->has('added_on'))
				$iter->set_literal('added_on', 'NOW()');

			return parent::insert($iter);
		}

		/**
		  * Insert a book
		  * @iter a #DataIter representing a book
		  *
		  * @result whether or not the insert was successful
		  */
		public function insert_book(DataIterPhotobook $iter)
		{
			return $this->_insert('foto_boeken', $iter, true);
		}

		/**
		  * Delete a book. This will also delete all photos
		  * and subbooks and all accompanying face tags, 
		  * comments and scaled images in the cache.
		  *
		  * @param DataIterPhotobook $iter representing a book
		  * @return boolean whether or not the delete was successful
		  */		
		public function delete_book(DataIterPhotobook $iter)
		{
			if (!is_numeric($iter->get_id()))
				throw new InvalidArgumentException('You can only delete real books');

			foreach ($iter->get_books() as $child)
				$this->delete_book($child);
			
			foreach ($iter->get_photos() as $photo)
				$this->delete($photo);
			
			$result = $this->_delete('foto_boeken', $iter);
			
			return $result;
		}

		/**
		  * Update a book
		  *
		  * @param DataIterPhotobook $iter representing a book
		  * @return boolean whether or not the update was successful
		  */		
		public function update_book(DataIterPhotobook $iter)
		{
			return $this->_update('foto_boeken', $iter);
		}

		/**
		 * Mark a photo book as read for a certain member.
		 *
		 * @param int $lid_id id of the DataIterMember
		 * @param DataIterPhotobook $book book to mark as read
		 */
		public function mark_read($lid_id, DataIterPhotobook $book)
		{
			if (!get_config_value('enable_photos_read_status', true))
				return;

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

		/**
		 * Marks all photo books that are children of the passed in book as
		 * read for a certain member.
		 * 
		 * @param int $lid_id id of the DataIterMember
		 * @param DataIterPhotobook $book parent book of which the children
		 * 	must be marked as read
		 */
		protected function mark_children_read($lid_id, DataIterPhotobook $book)
		{
			if (!get_config_value('enable_photos_read_status', true))
				return;
			
			$query = sprintf('
				WITH RECURSIVE book_children (id, visibility, parents) AS (
						SELECT id, visibility, ARRAY[0] FROM foto_boeken WHERE parent = %2$d
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

		/**
		 * Mark a photo book and all its children as read for a certain member.
		 * 
		 * @param int $lid_id id of the DataIterMember
		 * @param DataIterPhotobook $book book to mark as read
		 */
		public function mark_read_recursively($lid_id, DataIterPhotobook $book)
		{
			$this->mark_read($lid_id, $book);

			$this->mark_children_read($lid_id, $book);
		}
	}
