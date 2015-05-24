<?php
	require_once 'include/init.php';
	require_once 'include/member.php';
	require_once 'include/form.php';
	require_once 'include/json.php';
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

		protected function _create($data, array &$errors)
		{
			$data['foto'] = $this->photo->get('id');
			$data['auteur'] = logged_in('id');

			return parent::_create($data, $errors);
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
			if ($iter instanceof DataIterPhotobook)
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

			if (!is_numeric($parent->get_id()))
				throw new RuntimeException('Cannot add books to generated books');

			$data = $this->_check_fotoboek_values($errors);
			$data['parent'] = $parent->get_id();

			$iter = new DataIterPhotobook($this->model, -1, $data);
				
			if (count($errors) === 0)
			{
				$new_book_id = $this->model->insert_book($iter);
				return $this->redirect('fotoboek.php?book=' . $new_book_id);
			}

			return $this->get_content('create_book', $iter, compact('parent'));
		}
		
		private function _view_update_book(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();

			$data = $this->_check_fotoboek_values($errors);

			if (count($errors) > 0)
				return $this->get_content('edit_fotoboek', $book, array('errors' => $errors));

			$book->set_all($data);
			$this->model->update_book($book);
			
			$this->redirect('fotoboek.php?book=' . $book->get('parent'));		
		}

		private function _view_list_photos(DataIterPhotobook $book)
		{
			// if (!$this->policy->user_can_update($book))
			// 	throw new UnauthorizedException();
			
			$folder = path_concat(get_config_value('path_to_photos'), $_GET['path']);

			$iter = is_dir($folder) ? new FilesystemIterator($folder) : array();

			ob_end_clean();
			ob_implicit_flush(true);

			header('Content-Type: text/event-stream');
			header('Cache-Control: no-cache');
			
			foreach ($iter as $file_path)
			{
				if (!preg_match('/\.(jpg|gif)$/i', $file_path))
					continue;

				$exif_data = @exif_read_data($file_path);

				if ($exif_data === false)
					$exif_data = array('FileDateTime' => filemtime($file_path));

				if ($exif_thumbnail = exif_thumbnail($file_path, $th_width, $th_height, $th_image_type))
					$thumbnail = encode_data_uri(image_type_to_mime_type($th_image_type), $exif_thumbnail);
				else
					$thumbnail = null;

				echo "event: photo\n";
				echo "data:", json_encode(array(
					'title' => '',
					'path' => path_subtract($file_path, get_config_value('path_to_photos')),
					'created_on' => strftime('%Y-%m-%d %H:%M:%S',
						isset($exif_data['DateTimeOriginal'])
							? strtotime($exif_data['DateTimeOriginal'])
							: $exif_data['FileDateTime']),
					'thumbnail' => $thumbnail,
				)), "\n\n";

				ob_flush();
			}

			echo "event: end\n";
			echo "data: \n\n";
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

			return $this->_send_json($entries);
		}
		
		private function _view_add_photos(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();
			
			if (isset($_POST['photo']))
			{
				$new_photos = array();

				foreach ($_POST['photo'] as $photo)
				{
					$file_path = path_concat(get_config_value('path_to_photos'), $photo['path']);

					$iter = new DataIterPhoto($this->model, -1, array(
							'boek' => $book->get_id(),
							'beschrijving' => $photo['title'],
							'filepath' => $photo['path'],
							'added_on' => 'NOW()'),
							array('added_on'));
					
					$id = $this->model->insert($iter);
					
					$new_photos[] = new DataIterPhoto($this->model, $id, $iter->data);
				}

				// Update photo book last_update timestamp
				$book->set_literal('last_update', 'NOW()');
				$this->model->update_book($book);

				// Update faces
				$face_model = get_model('DataModelFotoboekFaces');
				$face_model->refresh_faces($new_photos);
				
				$this->redirect('fotoboek.php?book=' . $book->get('id'));
			}

			return $this->get_content('add_photos', $book);
		}
		
		protected function _view_del_book(DataIterPhotobook $book)
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
		
		protected function _view_fotoboek_del_fotos(DataIterPhotobook $book)
		{
			if (!$this->policy->user_can_update($book))
				throw new UnauthorizedException();
			
			foreach ($_POST['photo'] as $id)
				if ($photo = $this->model->get_iter($id))
					$this->model->delete($photo);
			
			$this->redirect('fotoboek.php?book=' . $book->get_id());
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
			if (!logged_in())
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
					return $this->_view_add_book($book);

				case 'update_book':
					return $this->_view_update_book($book);

				case 'delete_book':
					return $this->_view_delete_book($book);

				case 'add_photos':
					return $this->_view_add_photos($book);

				case 'update_photo':
					return $this->_view_update_photo($photo);

				case 'delete_photo':
					return $this->_view_delete_photo($photo);

				case 'add_photos_list_folders':
					return $this->_view_list_folders($book);

				case 'add_photos_list_photos':
					return $this->_view_list_photos($book);

				case 'download':
					return $this->_view_download_photo($photo);

				case 'scaled':
					return $this->_view_scaled_photo($photo);

				default:
					if ($photo)
						return $this->_view_read_photo($photo, $book);
					else
						return $this->_view_read_book($book);
			}
			
			// if (!$this->policy->user_can_read($book))
			// 			return $this->get_content('book_not_found');

			// 		if ($book && logged_in())
			// 			$this->model->mark_read(logged_in('id'), $book);

			// 		$this->get_content('fotoboek', $book);
			// 	}
			// }
		}
	}
	
	$controller = new ControllerFotoboek();
	$controller->run();
?>
