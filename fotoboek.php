<?php
	include('include/init.php');
	include('controllers/Controller.php');
	include('controllers/ControllerCRUD.php');

	require_once('member.php');
	require_once('form.php');

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

			return parent::_create($data, $errors);
		}

		protected function _update(DataIter $iter, $data, array &$errors)
		{
			// If lid_id is being changed, also update who changed it.
			if (isset($data['lid_id']))
				$data['tagged_by'] = logged_in('id');

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

	class ControllerFotoboek extends Controller {
		var $model = null;

		protected $policy;

		protected $faces_controller;

		protected $likes_controller;

		function ControllerFotoboek() {
			$this->model = get_model('DataModelFotoboek');

			$this->policy = get_policy($this->model);
		}
		
		function get_content($view, $iter = null, $params = null) {
			if ($iter instanceof DataIterPhotobook)
				$title = $iter->get('titel');
			elseif ($iter instanceof DataIterPhoto)
				$title = $iter->get_book()->get('titel');
			else
				$title = __('Fotoboek');

			$params = array_merge(
				array(
					'faces_controller' => $this->faces_controller,
					'likes_controller' => $this->likes_controller),
				$params ?: array()
			);

			$this->run_header(compact('title'));
			run_view('fotoboek::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function _page_prepare($commissie = true) {
			if ($commissie && !member_in_commissie(COMMISSIE_FOTOCIE)) {
				$this->get_content('auth_common');
				return false;
			}
			
			if (!$commissie && !logged_in()) {
				$this->get_content('auth_common');
				return false;
			}
			
			return true;
		}
		
		function _process_photo_description(DataIterPhoto $photo)
		{
			if (!$this->_page_prepare())
				return;
				
			$beschrijving = get_post('beschrijving');
			
			if ($beschrijving === null || strlen($beschrijving) > 255) {
				$this->get_content('foto', $photo, array('errors' => array('beschrijving')));
				return;
			}
			
			$photo->set('beschrijving', get_post('beschrijving'));
			$this->model->update($photo);
			
			$this->redirect('fotoboek.php?photo=' . $photo->get('id'));
		}
		
		function _check_titel($name, $value) {
			if (!$value)
				return false;
			
			if (strlen($value) > 50)
				return false;
			
			return $value;
		}

		function _check_date($name, $value) {
			if (!$value)
				return null;

			$parts = explode('-', $value); // input should be day - month - year
			
			if (count($parts) != 3)
				return false;
			
			$order = array(2, 1, 0); // year - month - day
			$value = '';

			foreach ($order as $i) {
				if (!is_numeric($parts[$i]))
					return false;
				
				if ($value != '')
					$value .= '-';
					
				$value .= intval($parts[$i]);
			}
			
			return $value;
		}

		function _check_fotograaf($name, $value) {			
			if (strlen($value) > 25)
				return false;
			
			return $value;
		}
		
		function _check_fotoboek_values(&$errors) {
			$data = check_values(array(
					array('name' => 'titel', 'function' => array(&$this, '_check_titel')),
					array('name' => 'date', 'function' => array(&$this, '_check_date')),
					array('name' => 'fotograaf', 'function' => array(&$this, '_check_fotograaf'))),
					$errors);
			
			if (count($errors) == 0)
				$data['beschrijving'] = get_post('beschrijving');
			
			return $data;
		}
		
		function _process_fotoboek_nieuw(DataIterPhotobook $parent = null) {
			if (!$this->_page_prepare())
				return;

			$data = $this->_check_fotoboek_values($errors);

			if (count($errors) > 0) {
				$this->get_content('fotoboek', $parent, array('errors' => $errors, 'errortype' => 'nieuw'));
				return;
			}
			
			$data['parent'] = $parent ? intval($parent->get('id')) : 0;

			$iter = new DataIterPhotobook($this->model, -1, $data);
			$new_book_id = $this->model->insert_book($iter);
			
			$this->redirect('fotoboek.php?book=' . $new_book_id);
		}
		
		function _process_fotoboek_edit(DataIterPhotobook $book) {
			if (!$this->_page_prepare())
				return;

			$data = $this->_check_fotoboek_values($errors);

			if (count($errors) > 0) {
				$this->get_content('edit_fotoboek', $book, array('errors' => $errors));
				return;
			}

			$book->set_all($data);
			$this->model->update_book($book);
			
			$this->redirect('fotoboek.php?book=' . $book->get('parent'));		
		}
		
		function _process_fotoboek_fotos(DataIterPhotobook $book) {
			if (!$this->_page_prepare())
				return;
			
			$urls = str_replace("\r", '', explode("\n", get_post('urls')));
			$thumbs = str_replace("\r", '', explode("\n", get_post('thumbnail_urls')));
						
			if (!get_post('urls') || count($urls) != count($thumbs)) {
				$this->get_content('fotoboek', $book, array('errors' => array('urls', 'thumbnail_urls'), 'errortype' => 'fotos'));
				return;
			}
			
			for ($i = 0; $i < count($urls); $i++) {
				if ($urls[$i] == '' || $thumbs[$i] == '') {
					$this->get_content('fotoboek', $book, array('errors' => array('urls', 'thumbnail_urls'), 'errortype' => 'fotos'));
					return;				
				}
			}
			
			for ($i = 0; $i < count($urls); $i++) {
				$iter = new DataIterPhoto($this->model, -1, array(
						'boek' => $book->get('id'),
						'url' => $urls[$i],
						'thumburl' => $thumbs[$i],
						'added_on' => 'NOW()'),
						array('added_on'));
				
				$this->model->insert($iter);
			}

			/* Delete composite thumbnail for the book so it will
			   get rerendered */
			$this->redirect('fotoboek.php?book=' . $book->get('id'));
		}
		
		function _del_book(DataIterPhotobook $book)
		{
			$children = $this->model->get_children($book);
			
			if ($children) {
				/* Delete all children */
				foreach ($children as $child)
					$this->_del_book($child);
			}
			
			/* Remove book */
			$this->model->delete_book($book);
		}
		
		protected function _process_del_book(DataIterPhotobook $book)
		{
			if (!$this->_page_prepare())
				return;
			
			$this->_del_book($book);

			$this->redirect('fotoboek.php?book=' . $book->get('parent'));
		}
		
		protected function _process_fotoboek_del_fotos(DataIterPhotobook $book)
		{
			if (!$this->_page_prepare())
				return;
			
			foreach ($_POST['photo'] as $id)
				if ($photo = $this->model->get_iter($id))
					$this->model->delete($photo);
			
			$this->redirect('fotoboek.php?book=' . $book->get_id());
		}

		protected function _process_update_faces(DataIterPhotobook $book)
		{
			if (!$this->_page_prepare())
				return;

			if (!ctype_digit((string) $book->get_id()))
				throw new Exception('You can only recognise faces in real photo books');

			header('Content-Type: text/plain');

			// Disable output buffering
			while (ob_get_level())
				ob_end_clean();

			// Open the process
			$p = popen(get_config_value('path_to_python', 'python') . ' opt/facedetect/suggest_faces.py ' . $book->get_id() . ' 2>&1', 'r');

			if (!$p)
				throw new Exception("Could not start process");

			while (($buffer = fgets($p, 4096)) !== false)
			{
				echo "$buffer\n";
				ob_flush();
			}
		
			fclose($p);
		}
		
		protected function _process_mark_read(DataIterPhotobook $book)
		{
			if (logged_in())
				$this->model->mark_read_recursively(logged_in('id'), $book);

			$this->redirect(sprintf('fotoboek.php?book=%d', $book->get_id()));
		}

		protected function _view_edit_book(DataIterPhotobook $book) {
			if (!$this->_page_prepare())
				return;

			$this->get_content('edit_fotoboek', $book);
		}

		protected function _view_original(DataIterPhoto $photo)
		{
			// For now require login for these originals
			if (!logged_in())
				return $this->get_content('auth_common');

			$common_path = 'fotocie.svcover.nl/fotos/';

			if (($path = strstr($photo->get('url'), $common_path)) === false)
				throw new Exception('Could not determine path');

			$real_path = '/home/commissies/fotocie/fotosGroot/' . substr($path, strlen($common_path));

			if (!file_exists($real_path))
				throw new Exception('Could not find file: ' . $real_path);

			$fh = fopen($real_path, 'rb');

			if (!$fh)
				throw new Exception('Could not open file: ' . $real_path);

			if (preg_match('/\.(jpg|gif)$/i', $specific_path, $match))
				header('Content-Type: image/' . strtolower($match[1]));

			header('Content-Length: ' . filesize($real_path));

			fpassthru($fh);
			fclose($fh);
		}

		protected function _view_photo(DataIterPhoto $photo, DataIterPhotobook $book)
		{
			$reactie_controller = new ControllerFotoboekReacties($photo);
			$reacties = $reactie_controller->run_embedded();

			$this->get_content('foto', $photo, compact('book', 'reacties'));
		}

		function run_impl() {
			if (isset($_GET['view']) && $_GET['view'] == 'competition') {
				$this->get_content('competition');
				return;
			}
			if (isset($_GET['photo']) && $_GET['photo']) {
				$photo = $this->model->get_iter($_GET['photo']);
				$book = $photo->get_book();
				if (!$photo) {
					$this->get_content('photo_not_found');
					return;
				}
			} else if (isset($_GET['book'])
				&& ctype_digit($_GET['book'])
				&& intval($_GET['book']) > 0) {
				$book = $this->model->get_book($_GET['book']);
				
				if (!$book) {
					$this->get_content('book_not_found');
					return;
				}
			} else {
				$photo = null;
				$book = $this->model->get_root_book();
			}

			if (logged_in() && isset($_GET['book']) && $_GET['book'] == 'liked')
				$book = get_model('DataModelFotoboekLikes')->get_book(logged_in_member());

			elseif (isset($_GET['book']) && preg_match('/^member_(\d+)$/', $_GET['book'], $match)) {
				$member = get_model('DataModelMember')->get_iter($match[1]);
				$book = get_model('DataModelFotoboekFaces')->get_book($member);
			}

			if ($photo) {
				$this->likes_controller = new ControllerFotoboekLikes($photo);
				$this->faces_controller = new ControllerFotoboekFaces($photo);
			}
			
			if (!$photo) {
				if (isset($_POST['submfotoboeknieuw']))
					$this->_process_fotoboek_nieuw($book);
				elseif (isset($_POST['submfotoboekedit']))
					$this->_process_fotoboek_edit($book);
				elseif (isset($_POST['submfotoboekfotos']))
					$this->_process_fotoboek_fotos($book);
				elseif (isset($_POST['submfotoboekdelfotos']))
					$this->_process_fotoboek_del_fotos($book);
				elseif (isset($_POST['mark_read_recursively']))
					$this->_process_mark_read($book);
				elseif (isset($_GET['delbook']))
					$this->_process_del_book($book);
				elseif (isset($_GET['editbook']))
					$this->_view_edit_book($book);
				elseif (isset($_POST['update_faces']))
					$this->_process_update_faces($book);
				else {
					if (!$this->policy->user_can_read($book))
						return $this->get_content('book_not_found');

					if ($book && logged_in())
						$this->model->mark_read(logged_in('id'), $book);

					$this->get_content('fotoboek', $book);
				}
			}
			elseif (!$this->policy->user_can_read($book))
				$this->get_content('book_not_found');
			elseif (isset($_POST['submfotobeschrijving']))
				$this->_process_photo_description($photo);
			elseif (isset($_GET['module']) && $_GET['module'] == 'likes')
				$this->likes_controller->run();
			elseif (isset($_GET['module']) && $_GET['module'] == 'faces')
				$this->faces_controller->run();
			elseif (isset($_GET['view']) && $_GET['view'] == 'original')
				$this->_view_original($photo);
			else
				$this->_view_photo($photo, $book);
		}
	}
	
	$controller = new ControllerFotoboek();
	$controller->run();
?>
