<?php
	require_once 'include/init.php';
	require_once 'include/member.php';
	require_once 'include/form.php';
	require_once 'include/http.php';
	require_once 'include/controllers/Controller.php';
	require_once 'include/controllers/ControllerCRUD.php';
	
	class ControllerFotoboekReacties extends ControllerCRUD
	{
		protected $photo;

		protected $_var_view = 'comment_view';

		protected $_var_id = 'comment_id';

		public function __construct(DataIterPhoto $photo)
		{
			$this->_var_view = 'comment_view';

			$this->photo = $photo;

			$this->model = get_model('DataModelFotoboekReacties');
		}

		protected function _get_default_view_params()
		{
			return array_merge(parent::_get_default_view_params(),
				array('empty_iter' => $this->_create_iter()));
		}

		protected function _create_iter()
		{
			$iter = parent::_create_iter();
			$iter->set('foto', $this->photo->get('id'));
			$iter->set('auteur', logged_in('id'));
			return $iter;
		}

		protected function _index()
		{
			return $this->model->get_for_photo($this->photo);
		}

		public function link(array $arguments)
		{
			$arguments['photo'] = $this->photo->get('id');

			return parent::link($arguments);
		}

		public function link_to_index()
		{
			return $this->link(array());
		}

		public function link_to_read(DataIter $iter)
		{
			return sprintf('%s#comment%d', $this->link_to_index(), $iter->get_id());
		}

		public function link_to_create()
		{
			return parent::link_to_create() . '#comment-form';
		}

		public function link_to_update(DataIter $iter)
		{
			return parent::link_to_update($iter) . '#comment-form';
		}

		public function link_to_delete(DataIter $iter)
		{
			return parent::link_to_delete($iter) . '#confirm-delete-comment-form';
		}
	}

	class ControllerFotoboekLikes extends Controller
	{
		public function __construct(DataIterPhoto $photo)
		{
			$this->photo = $photo;

			$this->model = get_model('DataModelFotoboekLikes');
		}

		public function run()
		{
			if (logged_in() && isset($_POST['action']) && $_POST['action'] == 'toggle')
				$this->model->toggle($this->photo, logged_in('id'));

			if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
				header('Content-Type: application/json');
				echo json_encode(array(
					'liked' => logged_in() && $this->model->is_liked($this->photo, logged_in('id')),
					'likes' => count($this->model->get_for_photo($this->photo))
				));
			}
			else
				$this->redirect('fotoboek.php?photo=' . $this->photo->get_id());
		}
	}

	class ControllerFotoboekFaces extends ControllerCRUD
	{
		protected $_var_view = 'faces_view';

		protected $_var_id = 'face_id';

		public function __construct(DataIterPhoto $photo)
		{
			$this->photo = $photo;

			$this->model = get_model('DataModelFotoboekFaces');
		}

		protected function _create($data, array &$errors)
		{
			$data['foto_id'] = $this->photo->get_id();
			$data['tagged_by'] = logged_in('id');

			return parent::_create($data, $errors);
		}

		protected function _update(DataIter $iter, $data, array &$errors)
		{
			// Also update who changed it.
			$data['tagged_by'] = logged_in('id');

			// Only a custom label XOR a lid_id can be assigned to a tag
			if (isset($data['custom_label']))
				$data['lid_id'] = null;
			elseif (isset($data['lid_id']))
				$data['custom_label'] = null;

			return parent::_update($iter, $data, $errors);
		}

		protected function _index()
		{
			return $this->model->get_for_photo($this->photo);
		}

		public function link(array $arguments)
		{
			$arguments['photo'] = $this->photo->get_id();

			$arguments['module'] = 'faces';

			return parent::link($arguments);
		}
	}

	class ControllerFotoboekPrivacy extends Controller
	{
		protected $photo;

		public function __construct(DataIterPhoto $photo)
		{
			$this->photo = $photo;

			$this->model = get_model('DataModelFotoboekPrivacy');
		}

		protected function get_content($view, $params)
		{
			$this->run_header(array('title' => __('Zichtbaarheid foto')));
			run_view($view, null, null, $params);
			$this->run_footer();
		}

		protected function run_impl()
		{
			if (!logged_in())
				throw new UnauthorizedException();

			$response = array();

			if ($_SERVER['REQUEST_METHOD'] == 'POST')
				if ($_POST['visibility'] == 'hidden')
					$this->model->mark_hidden($this->photo, logged_in_member());
				else
					$this->model->mark_visible($this->photo, logged_in_member());
			
			$response['photo'] = $this->photo;
			$response['visibility'] = $this->model->is_visible($this->photo, logged_in_member()) ? 'visible' : 'hidden';

			$this->get_content('fotoboek::privacy', $response);
		}
	}

	class ControllerFotoboek extends Controller
	{
		protected $policy;

		protected $faces_controller;

		protected $likes_controller;

		protected $privacy_controller;

		public function __construct()
		{
			$this->model = get_model('DataModelFotoboek');

			$this->policy = get_policy($this->model);
		}
		
		protected function get_content($view, $iter = null, $params = null)
		{
			if ($iter instanceof DataIterPhotobook && $iter->has('titel'))
				$title = $iter->get('titel');
			elseif ($iter instanceof DataIterPhoto)
				$title = $iter->get_book()->get('titel');
			else
				$title = __('Fotoboek');

			$params = array_merge(
				array(
					'faces_controller' => $this->faces_controller,
					'likes_controller' => $this->likes_controller,
					'privacy_controller' => $this->privacy_controller),
				$params ?: array()
			);

			$this->run_header(compact('title'));
			run_view('fotoboek::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		/* Helper functions for _check_foto_values */

		public function _check_titel($name, $value)
		{
			return strlen($value) > 1 && strlen($value) < 256 ? $value : false;
		}

		public function _check_date($name, $value)
		{
			return preg_match('/^(?<day>\d{1,2})[ -\/](?<month>\d{1,2})[ -\/](?<year>\d{4})$/', $value, $match)
				|| preg_match('/^(?<year>\d{4})[ -\/](?<month>\d{1,2})[ -\/](?<day>\d{1,2})$/', $value, $match)
				? sprintf('%04d-%02d-%02d', $match['year'], $match['month'], $match['day'])
				: null;
		}

		public function _check_fotograaf($name, $value)
		{			
			return strlen($value) < 256 ? $value : false;
		}

		public function _check_visibility($name, $value)
		{
			return in_array($value, array(
				DataModelFotoboek::VISIBILITY_PUBLIC,
				DataModelFotoboek::VISIBILITY_MEMBERS,
				DataModelFotoboek::VISIBILITY_ACTIVE_MEMBERS,
				DataModelFotoboek::VISIBILITY_PHOTOCEE
			)) ? $value : false;
		}
		
		protected function _check_fotoboek_values(&$errors)
		{
			$data = check_values(array(
				array('name' => 'titel', 'function' => array($this, '_check_titel')),
				array('name' => 'date', 'function' => array($this, '_check_date')),
				array('name' => 'fotograaf', 'function' => array($this, '_check_fotograaf')),
				array('name' => 'visibility', 'function' => array($this, '_check_visibility'))),
				$errors);
			
			if (count($errors) == 0)
				$data['beschrijving'] = $_POST['beschrijving'];
			
			return $data;
		}

		/* View functions */
		
		private function _view_create_book(DataIterPhotobook $parent)
		{
			if (!$this->policy->user_can_create())
				throw new UnauthorizedException();

			if (!ctype_digit((string) $parent->get_id()))
				throw new RuntimeException('Cannot add books to generated books');

			$errors = array();

			$iter = new DataIterPhotobook($this->model, -1, array('parent' => $parent->get_id()));

			if ($_SERVER['REQUEST_METHOD'] == 'POST')
			{
				$data = $this->_check_fotoboek_values($errors);
				$data['parent'] = $parent->get_id();

				$iter = new DataIterPhotobook($this->model, -1, $data);
					
				if (count($errors) === 0)
				{
					$new_book_id = $this->model->insert_book($iter);
					return $this->redirect('fotoboek.php?book=' . $new_book_id);
				}
			}

			return $this->get_content('form_photobook', $iter, compact('parent', 'errors'));
		}
		
		private function _view_update_book(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();

			$errors = array();

			if ($_SERVER['REQUEST_METHOD'] == 'POST')
			{
				$data = $this->_check_fotoboek_values($errors);

				if (count($errors) == 0)
				{
					$book->set_all($data);
					$this->model->update_book($book);

					$this->redirect('fotoboek.php?book=' . $book->get_id());
				}
			}
			
			return $this->get_content('form_photobook', $book, array('errors' => $errors));
		}

		private function _view_update_photo_order(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();

			if (!isset($_POST['order']))
				throw new RuntimeException('Order parameter missing');

			$photos = $book->get_photos();

			foreach ($photos as $photo)
			{
				$index = array_search($photo->get_id(), $_POST['order']);

				if ($index === false)
					continue;

				$photo->set('sort_index', $index);
				$this->model->update($photo);
			}
		}

		private function _view_update_book_order(DataIterPhotobook $parent)
		{
			if (!$this->policy->user_can_update($parent))
				throw new UnauthorizedException();

			if (!isset($_POST['order']))
				throw new RuntimeException('Order parameter missing');

			$books = $parent->get_books();

			foreach ($books as $book)
			{
				$index = array_search($book->get_id(), $_POST['order']);

				if ($index === false)
					continue;

				$book->set('sort_index', $index);
				$this->model->update_book($book);
			}
		}

		private function _view_update_photo(DataIterPhoto $photo)
		{
			if (!$this->policy->user_can_update($photo->get_book()))
				throw new UnauthorizedException();

			if ($_SERVER['REQUEST_METHOD'] == 'POST')
			{
				$photo->set('beschrijving', $_POST['beschrijving']);
				$this->model->update($photo);
				$this->redirect('fotoboek.php?photo=' . $photo->get_id());
			}
			
			return $this->redirect('fotoboek.php?photo=' . $photo->get_id());
		}

		private function _view_list_photos(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();

			$photos_in_album = $book->get_photos();
			
			$folder = path_concat(get_config_value('path_to_photos'), $_GET['path']);

			$iter = is_dir($folder) ? new FilesystemIterator($folder) : array();

			$out = new HTTPEventStream();
			$out->start();
			
			foreach ($iter as $full_path)
			{
				if (!preg_match('/\.(je?pg|gif)$/i', $full_path))
					continue;

				$id = null;

				$description = '';

				$file_path = path_subtract($full_path, get_config_value('path_to_photos'));

				// Find existing photo
				foreach ($photos_in_album as $photo) {
					if ($photo->get('filepath') == $file_path) {
						$id = $photo->get_id();
						$description = $photo->get('beschrijving');
						break;
					}
				}

				$exif_data = @exif_read_data($full_path);

				if ($exif_data === false)
					$exif_data = array('FileDateTime' => filemtime($full_path));

				if ($exif_thumbnail = exif_thumbnail($full_path, $th_width, $th_height, $th_image_type))
					$thumbnail = encode_data_uri(image_type_to_mime_type($th_image_type), $exif_thumbnail);
				else
					$thumbnail = null;

				$out->event('photo', json_encode(array(
					'id' => $id,
					'description' => (string) $description,
					'path' => $file_path,
					'created_on' => strftime('%Y-%m-%d %H:%M:%S',
						isset($exif_data['DateTimeOriginal'])
							? strtotime($exif_data['DateTimeOriginal'])
							: $exif_data['FileDateTime']),
					'thumbnail' => $thumbnail,
				)));
			}

			$out->event('end');
		}

		private function _view_list_folders(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();
			
			if (isset($_GET['path']))
				$path = path_concat(get_config_value('path_to_photos'), $_GET['path']);
			else
				$path = get_config_value('path_to_photos');

			$entries = array();

			foreach (new FilesystemIterator($path) as $entry)
				if (is_dir($entry))
					$entries[] = path_subtract($entry, get_config_value('path_to_photos'));

			sort($entries);
			return $this->_send_json($entries);
		}
		
		private function _view_add_photos(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();
			
			if (isset($_POST['photo']))
			{
				$new_photos = array();

				$errors = array();

				foreach ($_POST['photo'] as $photo)
				{
					if (!isset($photo['add']))
						continue;
				
					try {
						$iter = new DataIterPhoto($this->model, -1, array(
							'boek' => $book->get_id(),
							'beschrijving' => $photo['description'],
							'filepath' => $photo['path']));

						if (!$iter->file_exists())
							throw new Exception("File not found");

						$id = $this->model->insert($iter);
						
						$new_photos[] = new DataIterPhoto($this->model, $id, $iter->data);
					} catch (Exception $e) {
						$errors[] = $e->getMessage();
					}
				}

				if (count($new_photos))
				{
					// Update photo book last_update timestamp
					$book->set_literal('last_update', 'NOW()');
					$this->model->update_book($book);

					// Update faces
					$face_model = get_model('DataModelFotoboekFaces');
					$face_model->refresh_faces($new_photos);
				}
				
				if (count($errors) == 0)
					$this->redirect('fotoboek.php?book=' . $book->get_id());
				else {
					$_SESSION['add_photos_errors'] = $errors;
					$this->redirect('fotoboek.php?book=' . $book->get_id() . '&view=add_photos');
				}
			}

			return $this->get_content('add_photos', $book);
		}
		
		protected function _view_delete_book(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_delete($book))
				throw new UnauthorizedException();

			if (!empty($_POST['confirm_delete'])
				&& $_POST['confirm_delete'] == $book->get('titel'))
			{
				$this->model->delete_book($book);

				return $this->redirect('fotoboek.php?book=' . $book->get('parent'));
			}
			
			$this->get_content('confirm_delete', $book);
		}
		
		protected function _view_delete_photos(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();
			
			foreach ($_POST['photo'] as $id)
				if ($photo = $this->model->get_iter($id))
					$this->model->delete($photo);
			
			return $this->redirect('fotoboek.php?book=' . $book->get_id());
		}

		protected function _view_mark_read(DataIterPhotobook $book)
		{
			if (logged_in())
				$this->model->mark_read_recursively(logged_in('id'), $book);

			$this->redirect(sprintf('fotoboek.php?book=%d', $book->get_id()));
		}

		protected function _view_edit_book(DataIterPhotobook $book) 
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();

			$this->get_content('edit_fotoboek', $book);
		}

		protected function _view_download_photo(DataIterPhoto $photo)
		{
			// For now require login for these originals
			if (!get_identity()->member_is_active())
				throw new UnauthorizedException();

			if (!$photo->file_exists())
				throw new NotFoundException('Could not find original file');

			if (preg_match('/\.(jpg|gif)$/i', $photo->get('filepath'), $match))
				header('Content-Type: image/' . strtolower($match[1]));

			header('Content-Disposition: attachment; filename="' . addslashes(basename($photo->get('filepath'))) . '"');
			header('Content-Length: ' . filesize($photo->get_full_path()));

			$fh = $photo->get_resource();
			fpassthru($fh);
			fclose($fh);
		}

		protected function _view_download_book(DataIterPhotobook $root_book)
		{
			if (!get_identity()->member_is_active())
				throw new UnauthorizedException();

			if (!$this->policy->user_can_read($root_book))
				throw new UnauthorizedException();

			if ($root_book instanceof DataIterRootPhotobook)
				throw new InvalidArgumentException("Let's not try to download ALL of Cover's photos at once.");

			// Disable all output buffering
			while (ob_get_level() > 0)
				ob_end_clean();

			// Disable PHP's time limit
			set_time_limit(0);

			// Make sure we stop when the user is no longer listening
			ignore_user_abort(false);

			$books = array($root_book);

			// Make a list of all the books to be added to the zip
			// but filter out the books I can't read.
			for ($i = 0; $i < count($books); ++$i)
				foreach ($books[$i]->get_books(0) as $child)
					if ($this->policy->user_can_read($child))
						$books[] = $child;
			
			// Turn that list into a hashtable linking book id to book instance.
			$books = array_combine(
				array_map(curry_call_method('get_id'), $books),
				$books);

			// Set up the output zip stream and just handle all files as large files
			// (meaning no compression, streaming stead of reading into memory.)
			$zip = new ZipStream\ZipStream(sanitize_filename($root_book->get('titel')) . '.zip', [
				ZipStream\ZipStream::OPTION_LARGE_FILE_SIZE => 1,
				ZipStream\ZipStream::OPTION_LARGE_FILE_METHOD => 'store',
				ZipStream\ZipStream::OPTION_OUTPUT_STREAM => fopen('php://output', 'wb')]);

			// Now for each book find all photos and add them to the zip stream
			foreach ($books as $book)
			{
				// Create a path back to the root book to find a good file name
				$book_ancestors = [$book];

				while (end($book_ancestors)->get_id() != $root_book->get_id()
					&& end($book_ancestors)->has('parent')
					&& isset($books[end($book_ancestors)->get('parent')]))
					$book_ancestors[] = $books[end($book_ancestors)->get('parent')];
				
				// TODO: add book date in front of filename for sort order
				$book_path = implode('/',
					array_map('sanitize_filename',
						array_map(
							curry_call_method('get', 'titel'),
							array_reverse($book_ancestors))));

				foreach ($book->get_photos() as $photo)
				{
					// Skip originals we cannot find in this output. Very bad indeed, but not
					// something that should block downloading of the others.
					if (!$photo->file_exists())
						continue;

					// Let's just assume that the filename the photo already has is sane and safe
					$photo_path = $book_path . '/' . basename($photo->get('filepath'));

					// Add meta data to the zip file if available
					$metadata = array();

					if ($photo->has('created_on'))
						$metadata['time'] = strtotime($photo->get('created_on'));
					else
						$metadata['time'] = filectime($photo->get_full_path());
					
					if ($photo->has('beschrijving'))
						$metadata['comment'] = $photo->get('beschrijving');

					// And finally add the photo to the actual stream
					$zip->addFileFromPath(
						$photo_path,
						$photo->get_full_path(),
						$metadata);
				}
			}

			$zip->finish();
		}

		protected function _view_confirm_download_book(DataIterPhotobook $root_book)
		{
			$books = array($root_book);

			// Make a list of all the books to be added to the zip
			// but filter out the books I can't read.
			for ($i = 0; $i < count($books); ++$i)
				foreach ($books[$i]->get_books(0) as $child)
					if ($this->policy->user_can_read($child))
						$books[] = $child;

			$total_photos = 0;
			$total_file_size = 0;

			foreach ($books as $book)
				foreach ($book->get_photos() as $photo)
					if ($photo->file_exists())
					{
						$total_photos += 1;
						$total_file_size += $photo->get_file_size();
					}

			return $this->get_content('confirm_download_book', $root_book, compact('total_photos', 'total_file_size'));
		}

		protected function _view_scaled_photo(DataIterPhoto $photo)
		{
			if (!$this->policy->user_can_read($photo->get_book()))
				throw new UnauthorizedException();

			$width = isset($_GET['width']) ? min($_GET['width'], 1600) : null;
			$height = isset($_GET['height']) ? min($_GET['height'], 1600) : null;

			// First open the resource because this could throw a 404 exception with
			// the appropriate headers.
			$fhandle = $photo->get_resource($width, $height);
			
			header('Pragma: public');
			header('Cache-Control: max-age=86400');
			header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
			
			if (substr($photo->get('filepath'), -3, 3) == 'gif')
				header('Content-Type: image/gif');
			else
				header('Content-Type: image/jpeg');
			
			fpassthru($fhandle);
			fclose($fhandle);
		}

		protected function _view_read_photo(DataIterPhoto $photo, DataIterPhotobook $book)
		{
			$photos = $book->get_photos();

			$current_index = array_usearch($photo, $photos, ['DataIter', 'is_same']);

			$reactie_controller = new ControllerFotoboekReacties($photo);
			$reacties = $reactie_controller->run_embedded();

			return $this->get_content('foto', $photo, compact('book', 'reacties'));
		}

		protected function _view_read_book(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_read($book))
				throw new UnauthorizedException();

			if (logged_in())
				$this->model->mark_read(logged_in('id'), $book);

			return $this->get_content('fotoboek', $book);
		}

		protected function run_impl()
		{
			if (isset($_GET['view']) && $_GET['view'] == 'competition')
				return $this->get_content('competition');

			$photo = null;
			$book = null;

			// Single photo page
			if (isset($_GET['photo']) && $_GET['photo']) {
				$photo = $this->model->get_iter($_GET['photo']);
				$book = $photo->get_book();
			}
			// Book index page
			else if (isset($_GET['book'])
				&& ctype_digit($_GET['book'])
				&& intval($_GET['book']) > 0) {
				$book = $this->model->get_book($_GET['book']);
			}
			// Likes book page
			elseif (isset($_GET['book']) && $_GET['book'] == 'liked') {
				$book = get_model('DataModelFotoboekLikes')->get_book(logged_in_member());
			}
			// All photos who a certain member is (or mutiple are) tagged in page
			elseif (isset($_GET['book']) && preg_match('/^member_(\d+(?:_\d+)*)$/', $_GET['book'], $match)) {
				$members = array();

				foreach (explode('_', $match[1]) as $member_id)
					$members[] = get_model('DataModelMember')->get_iter($member_id);

				$book = get_model('DataModelFotoboekFaces')->get_book($members);
			}
			// And otherwise the root book index page
			else {
				$book = $this->model->get_root_book();
			}

			// If there is a photo, also initialize the appropriate auxiliary controllers 
			if ($photo) {
				$this->likes_controller = new ControllerFotoboekLikes($photo);
				$this->faces_controller = new ControllerFotoboekFaces($photo);
				$this->privacy_controller = new ControllerFotoboekPrivacy($photo);
			}

			// Choose the correct view
			if (isset($_GET['module'])) {
				if (!$photo)
					throw new RuntimeException('You cannot access the photo auxiliary functions without also selecting a photo');

				switch ($_GET['module']) {
					case 'likes':
						return $this->likes_controller->run();
					case 'faces':
						return $this->faces_controller->run();
					case 'privacy':
						return $this->privacy_controller->run();
				}
			}
			
			switch (isset($_GET['view']) ? $_GET['view'] : null)
			{
				case 'add_book':
					return $this->_view_create_book($book);

				case 'update_book':
					return $this->_view_update_book($book);

				case 'delete_book':
					return $this->_view_delete_book($book);

				case 'mark_book_read':
					return $this->_view_mark_read($book);

				case 'add_photos':
					return $this->_view_add_photos($book);

				case 'update_photo':
					return $this->_view_update_photo($photo);

				case 'update_photo_order':
					return $this->_view_update_photo_order($book);

				case 'update_book_order':
					return $this->_view_update_book_order($book);

				case 'delete_photos':
					return $this->_view_delete_photos($book);

				case 'add_photos_list_folders':
					return $this->_view_list_folders($book);

				case 'add_photos_list_photos':
					return $this->_view_list_photos($book);

				case 'download':
					return $this->_view_download_photo($photo);

				case 'download_book':
					return $this->_view_download_book($book);

				case 'confirm_download_book':
					return $this->_view_confirm_download_book($book);

				case 'scaled':
					return $this->_view_scaled_photo($photo);

				default:
					if ($photo)
						return $this->_view_read_photo($photo, $book);
					else
						return $this->_view_read_book($book);
			}
		}
	}
	
	$controller = new ControllerFotoboek();
	$controller->run();
?>
