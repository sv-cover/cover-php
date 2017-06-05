<?php
	require_once 'include/init.php';
	require_once 'include/member.php';
	require_once 'include/form.php';
	require_once 'include/http.php';
	require_once 'include/controllers/Controller.php';
	require_once 'include/controllers/ControllerCRUD.php';
	
	class ControllerFotoboekComments extends ControllerCRUD
	{
		protected $_var_view = 'comment_view';

		protected $_var_id = 'comment_id';

		protected $photo;

		public function __construct(DataIterPhoto $photo)
		{
			$this->photo = $photo;

			$this->model = get_model('DataModelPhotobookReactie');

			$this->view = View::byName('fotoboekreacties', $this);
		}

		public function new_iter()
		{
			$iter = parent::new_iter();
			$iter->set('foto', $this->photo->get_id());
			$iter->set('auteur', get_identity()->get('id'));
			return $iter;
		}

		protected function _index()
		{
			return $this->model->get_for_photo($this->photo);
		}

		public function link(array $arguments)
		{
			$arguments['photo'] = $this->photo['id'];

			$arguments['book'] = $this->photo['scope']['id'];

			$arguments['module'] = 'comments';

			return parent::link($arguments);
		}

		public function link_to_index()
		{
			return parent::link([
				'photo' => $this->photo['id'],
				'book' => $this->photo['scope']['id'],
			]);
		}

		public function link_to_read(DataIter $iter)
		{
			return sprintf('%s#comment%d', $this->link_to_index(), $iter->get_id());
		}

		public function link_to_like(DataIter $iter)
		{
			return $this->link([$this->_var_view => 'likes', $this->_var_id => $iter->get_id()]);
		}

		public function run_likes(DataIter $iter)
		{
			if (isset($_POST['action']))
			{
				switch ($_POST['action']) {
					case 'like':
						$iter->like(get_identity()->member());
						break;
					case 'unlike':
						$iter->unlike(get_identity()->member());
						break;
				}
			}

			return $this->run_read($iter);
		}
	}

	class ControllerFotoboekLikes extends Controller
	{
		public function __construct(DataIterPhoto $photo)
		{
			$this->photo = $photo;

			$this->model = get_model('DataModelPhotobookLike');

			$this->view = new View($this);
		}

		public function run()
		{
			if (logged_in() && isset($_POST['action']) && $_POST['action'] == 'toggle')
				$this->model->toggle($this->photo, logged_in('id'));

			if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
				return $this->view->render_json([
					'liked' => logged_in() && $this->model->is_liked($this->photo, logged_in('id')),
					'likes' => count($this->model->get_for_photo($this->photo))
				]);
			}
			else
				return $this->view->redirect('fotoboek.php?photo=' . $this->photo->get_id());
		}
	}

	class ControllerFotoboekFaces extends ControllerCRUD
	{
		protected $_var_view = 'faces_view';

		protected $_var_id = 'face_id';

		public function __construct(DataIterPhoto $photo)
		{
			$this->photo = $photo;

			$this->model = get_model('DataModelPhotobookFace');

			$this->view = new CRUDView($this);
		}

		protected function _create(DataIter $iter, array $data, array &$errors)
		{
			$data['foto_id'] = $this->photo->get_id();
			$data['tagged_by'] = logged_in('id');

			return parent::_create($iter, $data, $errors);
		}

		protected function _update(DataIter $iter, array $data, array &$errors)
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

			$this->model = get_model('DataModelPhotobookPrivacy');

			$this->view = View::byName('fotoboek', $this);
		}

		protected function run_impl()
		{
			if (!get_auth()->logged_in())
				throw new UnauthorizedException();

			$member = get_identity()->member();

			$response = array();

			if ($this->_form_is_submitted('privacy', $this->photo)) {
				if ($_POST['visibility'] == 'hidden')
					$this->model->mark_hidden($this->photo, $member);
				else
					$this->model->mark_visible($this->photo, $member);
			}
			
			return $this->view->render_privacy($this->photo, $this->model->is_visible($this->photo, $member) ? 'visible' : 'hidden');
		}
	}

	class ControllerFotoboek extends Controller
	{
		protected $policy;

		public $faces_controller;

		public $likes_controller;

		public $privacy_controller;

		public $comments_controller;

		public function __construct()
		{
			$this->model = get_model('DataModelPhotobook');

			$this->policy = get_policy($this->model);

			$this->view = View::byName('fotoboek', $this);
		}

		public function user_can_download_book(DataIterPhotobook $book)
		{
			return get_identity()->member_is_active()
				&& !($book instanceof DataIterRootPhotobook)
				&& $this->policy->user_can_read($book);
		}

		public function user_can_mark_as_read(DataIterPhotobook $book)
		{
			return // only logged in members can track their viewed photo books
				get_auth()->logged_in() 
				
				// and only if enabled
				&& get_config_value('enable_photos_read_status', true) 

				// and only if we actually are watching a book
				&& $book->get_id() 
				
				// which is not artificial (faces, likes) and has photos
				&& ctype_digit($book->get_id()) && $book['num_books'] > 0;
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
				DataModelPhotobook::VISIBILITY_PUBLIC,
				DataModelPhotobook::VISIBILITY_MEMBERS,
				DataModelPhotobook::VISIBILITY_ACTIVE_MEMBERS,
				DataModelPhotobook::VISIBILITY_PHOTOCEE
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
			$iter = $parent->new_book();

			if (!$this->policy->user_can_create($iter))
				throw new UnauthorizedException('You are not allowed to create new photo books inside this photo book.');

			$errors = array();

			if ($this->_form_is_submitted('create_book', $parent))
			{
				// TODO: Move this checking into the model layer..
				$data = $this->_check_fotoboek_values($errors);
				
				$iter->set_all($data);
					
				if (count($errors) === 0)
				{
					$new_book_id = $this->model->insert_book($iter);
					return $this->view->redirect('fotoboek.php?book=' . $new_book_id);
				}
			}

			return $this->view->render_create_photobook($iter, null, $errors);
		}
		
		private function _view_update_book(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();

			$errors = array();

			$success = null;

			if ($this->_form_is_submitted('update_book', $book))
			{
				$data = $this->_check_fotoboek_values($errors);

				$success = false;

				if (count($errors) == 0)
				{
					$book->set_all($data);
					$this->model->update_book($book);

					return $this->view->redirect('fotoboek.php?book=' . $book->get_id());
				}
			}
			
			return $this->view->render_update_photobook($book, $success, $errors);
		}

		private function _view_update_photo_order(DataIterPhotobook $book)
		{
			if (!$this->_form_is_submitted('update_photo_order', $book))
				throw new RuntimeException('Missing nonce');

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
			if (!$this->_form_is_submitted('update_book_order', $parent))
				throw new RuntimeException('Missing nonce');

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
			
			return $this->view->redirect('fotoboek.php?photo=' . $photo->get_id());
		}

		private function _view_list_photos(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();

			$photos_in_album = $book->get_photos();
			
			$folder = path_concat(get_config_value('path_to_photos'), $_GET['path']);

			$iter = is_dir($folder) ? new FilesystemIterator($folder) : array();

			// Here $out is actually producing the output to the browser. The $view is entirely ignored here.
			$out = new HTTPEventStream();
			$out->start();
			
			foreach ($iter as $full_path)
			{
				try {
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

					if ($exif_thumbnail = @exif_thumbnail($full_path, $th_width, $th_height, $th_image_type))
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
				} catch (\Exception $e) {
					$out->event('error', json_encode([
						'message' => $e->getMessage(),
						'file' => $e->getFile(),
						'line' => $e->getLine(),
						'trace' => $e->getTrace()
					]));
				}
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
			return $this->view->render_json($entries);
		}
		
		private function _view_add_photos(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();
			
			$errors = array();

			$success = null;

			if ($this->_form_is_submitted('add_photos', $book))
			{
				$photos = isset($_POST['photo']) ? $_POST['photo'] : [];
				
				$new_photos = array();

				foreach ($photos as $photo)
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
					$book['last_update'] = new DateTime();
					$this->model->update_book($book);

					// Update faces
					$face_model = get_model('DataModelPhotobookFace');
					$face_model->refresh_faces($new_photos);
				}
				
				if (count($errors) == 0)
					return $this->view->redirect('fotoboek.php?book=' . $book->get_id());
				else
					$success = false;
			}

			return $this->view->render_add_photos($book, $success, $errors);
		}
		
		protected function _view_delete_book(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_delete($book))
				throw new UnauthorizedException();

			$errors = array();

			if ($this->_form_is_submitted('delete', $book))
			{
				if ($_POST['confirm_delete'] == $book->get('titel')) {
					$this->model->delete_book($book);
					return $this->view->redirect('fotoboek.php?book=' . $book->get('parent_id'));
				}

				$errors[] = 'confirm_delete';
			}
			
			return $this->view->render_delete($book, false, $errors);
		}
		
		protected function _view_delete_photos(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();
			
			foreach ($_POST['photo'] as $id)
				if ($photo = $this->model->get_iter($id))
					$this->model->delete($photo);
			
			return $this->view->redirect('fotoboek.php?book=' . $book->get_id());
		}

		protected function _view_mark_read(DataIterPhotobook $book)
		{
			if (logged_in())
				$this->model->mark_read_recursively(logged_in('id'), $book);

			return $this->view->redirect(sprintf('fotoboek.php?book=%d', $book->get_id()));
		}

		protected function _view_download_photo(DataIterPhoto $photo)
		{
			// Note again that this function ignores the view completely and produces output on its own.

			// We don't want 'guests' to download our originals
			if (!get_auth()->logged_in())
				throw new UnauthorizedException();

			// Also, you need at least read access to this photo
			if (!get_policy($photo)->user_can_read($photo))
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
			// This function does not use the $view but produces its own output via ZipStream.

			if (!$this->user_can_download_book($root_book))
				throw new UnauthorizedException();

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
				foreach ($books[$i]['books_without_metadata'] as $child)
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
					&& end($book_ancestors)->has_value('parent_id')
					&& isset($books[end($book_ancestors)->get('parent_id')]))
					$book_ancestors[] = $books[end($book_ancestors)->get('parent_id')];
				
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

					// Skip photo's you cannot access
					if (!get_policy($photo)->user_can_read($photo))
						continue;

					// Let's just assume that the filename the photo already has is sane and safe
					$photo_path = $book_path . '/' . basename($photo->get('filepath'));

					// Add meta data to the zip file if available
					$metadata = array();

					if ($photo->has_value('created_on'))
						$metadata['time'] = strtotime($photo->get('created_on'));
					else
						$metadata['time'] = filectime($photo->get_full_path());
					
					if ($photo->has_value('beschrijving'))
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
			if (!$this->user_can_download_book($root_book))
				throw new UnauthorizedException();

			$books = array($root_book);

			// Make a list of all the books to be added to the zip
			// but filter out the books I can't read.
			for ($i = 0; $i < count($books); ++$i)
				foreach ($books[$i]['books_without_metadata'] as $child)
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

			return $this->view->render_download_photobook($root_book, $total_photos, $total_file_size);
		}

		protected function _view_scaled_photo(DataIterPhoto $photo)
		{
			if (!get_policy($photo)->user_can_read($photo))
				throw new UnauthorizedException('You may need to log in to view this photo');

			$width = isset($_GET['width']) ? min($_GET['width'], 1600) : null;
			$height = isset($_GET['height']) ? min($_GET['height'], 1600) : null;

			$cache_status = null;

			// First open the resource because this could throw a 404 exception with
			// the appropriate headers.
			$fhandle = $photo->get_resource($width, $height, !empty($_GET['skip_cache']), $cache_status);
			
			header('Pragma: public');
			header('Cache-Control: max-age=86400');
			header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
			header('X-Cache-Status: ' . $cache_status);
			
			if (substr($photo['filepath'], -3, 3) == 'gif')
				header('Content-Type: image/gif');
			else
				header('Content-Type: image/jpeg');
			
			fpassthru($fhandle);
			fclose($fhandle);
		}

		protected function _view_read_photo(DataIterPhoto $photo, DataIterPhotobook $book)
		{
			if (!get_policy($photo)->user_can_read($photo))
				throw new UnauthorizedException();

			$photos = $book->get_photos();

			$current_index = array_usearch($photo, $photos, ['DataIter', 'is_same']);

			return $this->view->render_photo($book, $photo);
		}

		protected function _view_read_book(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_read($book))
				throw new UnauthorizedException();

			$rendered_page = $this->view->render_photobook($book);

			if (logged_in())
				$this->model->mark_read(logged_in('id'), $book);

			return $rendered_page;
		}

		public function json_link_to_update_book_order(DataIterPhotobook $book)
		{
			$nonce = nonce_generate(nonce_action_name('update_book_order', [$book]));
			return $this->link(['view' => 'update_book_order', 'book' => $book['id'], '_nonce' => $nonce]);
		}

		public function json_link_to_update_photo_order(DataIterPhotobook $book)
		{
			$nonce = nonce_generate(nonce_action_name('update_photo_order', [$book]));
			return $this->link(['view' => 'update_photo_order', 'book' => $book['id'], '_nonce' => $nonce]);
		}

		protected function run_impl()
		{
			if (isset($_GET['view']) && $_GET['view'] == 'competition')
				return $this->view->render_competition();

			$photo = null;
			$book = null;

			// Single photo page
			if (isset($_GET['photo']) && $_GET['photo']) {
				$photo = $this->model->get_iter($_GET['photo']);
			}

			// Book index page
			if (isset($_GET['book'])
				&& ctype_digit($_GET['book'])
				&& intval($_GET['book']) > 0) {
				$book = $this->model->get_book($_GET['book']);
			}
			// Likes book page
			elseif (isset($_GET['book']) && $_GET['book'] == 'liked') {
				$book = get_model('DataModelPhotobookLike')->get_book(get_identity()->member());
			}
			// All photos who a certain member is (or mutiple are) tagged in page
			elseif (isset($_GET['book']) && preg_match('/^member_(\d+(?:_\d+)*)$/', $_GET['book'], $match)) {
				$members = array();

				foreach (explode('_', $match[1]) as $member_id)
					$members[] = get_model('DataModelMember')->get_iter($member_id);

				$book = get_model('DataModelPhotobookFace')->get_book($members);
			}
			// If there is a photo, then use the book of that one
			elseif ($photo) {
				$book = $photo->get_book();
			}
			// And otherwise the root book index page
			else {
				$book = $this->model->get_root_book();
			}

			if ($photo && $book)
				$photo['scope'] = $book;

			// If there is a photo, also initialize the appropriate auxiliary controllers 
			if ($photo) {
				$this->comments_controller = new ControllerFotoboekComments($photo);
				$this->likes_controller = new ControllerFotoboekLikes($photo);
				$this->faces_controller = new ControllerFotoboekFaces($photo);
				$this->privacy_controller = new ControllerFotoboekPrivacy($photo);
			}

			// Choose the correct view
			if (isset($_GET['module'])) {
				if (!$photo)
					throw new RuntimeException('You cannot access the photo auxiliary functions without also selecting a photo');

				switch ($_GET['module']) {
					case 'comments':
						return $this->comments_controller->run();
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
