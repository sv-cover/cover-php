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

		public function __construct(DataIter $photo)
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
		public function __construct(DataIter $photo)
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

	class ControllerFotoboek extends Controller {
		var $model = null;

		function ControllerFotoboek() {
			$this->model = get_model('DataModelFotoboek');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('Fotoboek')));
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
		
		function _process_photo_description($photo) {
			if (!$this->_page_prepare())
				return;
				
			$beschrijving = get_post('beschrijving');
			
			if ($beschrijving === null || strlen($beschrijving) > 255) {
				$this->get_content('foto', $photo, array('errors' => array('beschrijving')));
				return;
			}
			
			$photo->set('beschrijving', get_post('beschrijving'));
			$this->model->update($photo);
			
			header('Location: fotoboek.php?photo=' . $photo->get('id'));
			exit();
		}
		
		function _process_next_slide($id) {
			ob_end_clean();

			$photo = $this->model->get_iter($id);
			
			if (!$photo)
				exit();
			
			$next = $this->model->get_next_photo($photo);
			
			if (!$next)
				exit();
			
			echo $next->get('id') . "\n" . $next->get('url');
			exit();
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
		
		function _process_fotoboek_nieuw($book) {
			if (!$this->_page_prepare())
				return;

			$data = $this->_check_fotoboek_values($errors);

			if (count($errors) > 0) {
				$this->get_content('fotoboek', $book, array('errors' => $errors, 'errortype' => 'nieuw'));
				return;
			}
			
			$data['parent'] = $book ? intval($book->get('id')) : 0;

			$iter = new DataIter($this->model, -1, $data);
			$new_book_id = $this->model->insert_book($iter);
			
			header('Location: fotoboek.php?book=' . $new_book_id);
		}
		
		function _process_fotoboek_edit($book) {
			if (!$this->_page_prepare())
				return;

			$data = $this->_check_fotoboek_values($errors);

			if (count($errors) > 0) {
				$this->get_content('edit_fotoboek', $book, array('errors' => $errors));
				return;
			}

			$book->set_all($data);
			$this->model->update_book($book);
			
			header('Location: fotoboek.php?book=' . $book->get('parent'));		
		}
		
		function _process_fotoboek_fotos($book) {
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
				$iter = new DataIter($this->model, -1, array(
						'boek' => $book->get('id'),
						'url' => $urls[$i],
						'thumburl' => $thumbs[$i],
						'added_on' => 'NOW()'),
						array('added_on'));
				
				$this->model->insert($iter);
			}

			/* Delete composite thumbnail for the book so it will
			   get rerendered */
			$this->model->delete_book_thumb($book);
			header('Location: fotoboek.php?book=' . $book->get('id'));
		}
		
		function _process_fetch_urls($path) {
			ob_end_clean();

			$photos = '/home/student/fotocie/www/fotos/' . $path;

			if (!file_exists($photos)) {
				echo "0\r" . __('Het opgegeven pad bestaat niet');
				exit();
			}
			
			if (!preg_match('/^fotos(.*)$/', $path, $matches)) {
				echo "0\r" . __('Het opgegeven pad is niet in het juiste formaat');
				exit();
			}
			
			$thumbnails = '/home/student/fotocie/www/thumbnails/thumbnails' . $matches[1];
			
			if (!file_exists($thumbnails)) {
				echo "0\r" . __('De thumbnails voor het opgegeven pad kunnen niet worden gevonden');
				exit();
			}
			
			$urls = array();
			$thumbnail_urls = array();
			
			if ($dh = @opendir($photos)) {
				while (($file = readdir($dh)) !== false) {
					if (!is_file($photos . '/' . $file))
						continue;
					
					if (!file_exists($thumbnails . '/' . $file)) {
						echo "0\r" . sprintf(__('De thumbnail voor %s bestaat niet'), $file);
						closedir($dh);
						exit();
					}
					
					$urls[] = 'http://www.ai.rug.nl/~fotocie/fotos/fotos' . $matches[1] . '/' . $file;
					$thumbnail_urls[] = 'http://www.ai.rug.nl/~fotocie/thumbnails/thumbnails' . $matches[1] . '/' . $file;
				}

				closedir($dh);
			} else {
				echo "0\r" . __('De directory met foto\'s kon niet worden geopend om te lezen');
				exit();
			}
			
			echo "1\r" . implode("\n", $urls) . "\r" . implode("\n", $thumbnail_urls);
			exit();
		}
		
		function _del_book($book) {
			if (!$book)
				return;

			$children = $this->model->get_children($book);
			
			if ($children) {
				/* Delete all children */
				foreach ($children as $child)
					$this->_del_book($child);
			}
			
			/* Remove book */
			$this->model->delete_book($book);
		}
		
		function _process_del_book($book) {
			if (!$this->_page_prepare())
				return;
			
			$this->_del_book($book);

			header('Location: fotoboek.php?book=' . ($book->get('parent') ? $book->get('parent') : ''));
			exit();
		}
		
		function _process_fotoboek_del_fotos($book) {
			if (!$this->_page_prepare())
				return;
			
			foreach ($_POST as $key => $value) {
				if (strncmp($key, 'del_', 4) != 0)
					continue;
				
				$id = substr($key, 4);
				$photo = $this->model->get_iter($id);
				
				if ($photo)				
					$this->model->delete($photo);
			}
			
			/* Remove composite thumbnail for the book so it will
			   get rerendered */
			$this->model->delete_book_thumb($book);
			header('Location: fotoboek.php?book=' . ($book ? $book->get('id') : ''));
		}
		
		function _build_book_thumb($book) {
			static $back = null;
			static $front = null;
			static $width = 40;
			static $height = 30;
			static $left = 10;
			static $top = 34;
			static $space = 4;

			if ($back == null) /* Create background image */
				$back = imagecreatefrompng(get_theme_data('images/book_back.png'));

			if ($front == null) /* Create foreground image */
				$front = imagecreatefrompng(get_theme_data('images/book_front.png'));
	
			$composite = imagecreatetruecolor(100, 76);
			
			imagesavealpha($back, true);
			imagesavealpha($front, true);

			imagepalettecopy($composite, $back);
			imagealphablending($composite, false);
			imagesavealpha($composite, true);
			imagecopy($composite, $back, 0, 0, 0, 0, imagesx($back), imagesy($back));

			if ($book) {
				/* Get 2 photos */
				$photos = $this->model->get_photos($book, 2, true);
			} else {
				$photos = null;
			}
			
			/* Create a layer of photos randomly choosen from the book */
			if ($photos) {
				$current = $left;

				foreach ($photos as $photo) {
					$thumb = imagecreatefromjpeg($photo->get('thumburl'));
					
					if (!$thumb)
						continue;

					imagecopyresampled($composite, $thumb, $current, $top, 0, 0, 40, 30, imagesx($thumb), imagesy($thumb));
	 				$current += $width + $space;
	 				imagedestroy($thumb);
				}
			}
			
			/* Copy/merge the front in */
			imagealphablending($composite, true);
			imagesavealpha($composite, true);
			imagecopy($composite, $front, 1, 19, 0, 0, imagesx($front), imagesy($front));

			/* Get the png data */
			ob_start();
			imagepng($composite);
			$image = ob_get_contents();
			ob_end_clean();
			
			return $image;
		}
		
		function _refresh_book_thumb($book, $iter) {
			$png = $this->_build_book_thumb($book);
			
			$iter->set_literal('image', "'" . pg_escape_bytea($png) . "'");
			$iter->set_literal('generated', "('now'::text)::timestamp(6) without time zone");

			$this->model->update_book_thumbnail($iter);
			$iter = $this->model->get_book_thumbnail($book);
			
			return $iter;
		}
		
		function _create_book_thumb($book) {
			$png = $this->_build_book_thumb($book);
			$iter = new DataIter($this->model, -1, array(
					'theme' => get_theme(),
					'boek' => $book ? $book->get('id') : 0));
			
			$iter->set_literal('image', "'" . pg_escape_bytea($png) . "'");
			
			$this->model->insert_book_thumbnail($iter);
			$iter = $this->model->get_book_thumbnail($book);

			return $iter;
		}
		
		function _process_book_thumb($id) {
			/* Try to fetch the thumbnail from the db */
			ob_end_clean();
			$book = $this->model->get_book($id, logged_in());
			
			if ($book)
				$thumb = $this->model->get_book_thumbnail($book);
			else
				$thumb = null;
			
			if (!$thumb) {
				/* Test if the book has any photos */
				if ($this->model->get_num_photos($book)) {
					$thumb = $this->_create_book_thumb($book);
				} else {
					/* Try to get the default thumbnail */
					$thumb = $this->model->get_book_thumbnail(null);
					
					if (!$thumb)
						$thumb = $this->_create_book_thumb(null);
				}
			} elseif (intval($thumb->get('since')) > 3600 * 24 * 7) {
				/* Refresh the thumb */
				$thumb = $this->_refresh_book_thumb($book, $thumb);
			}
			
			header("Content-Type: image/png");
			echo pg_unescape_bytea($thumb->get('image'));
			exit();
		}

		protected function _process_mark_read(DataIter $book)
		{
			if (logged_in())
				$this->model->mark_read_recursively(logged_in('id'), $book);

			$this->redirect(sprintf('fotoboek.php?book=%d', $book->get_id()));
		}

		protected function _view_edit_book($book) {
			if (!$this->_page_prepare())
				return;

			$this->get_content('edit_fotoboek', $book);
		}

		protected function _view_photo(DataIter $photo)
		{
			$reactie_controller = new ControllerFotoboekReacties($photo);
			$reacties = $reactie_controller->run_embedded();
			$this->get_content('foto', $photo, compact('reacties'));
		}

		protected function _run_likes(DataIter $photo)
		{
			$likes_controller = new ControllerFotoboekLikes($photo);
			$likes_controller->run();
		}
		
		function run_impl() {
			if (isset($_GET['book_thumb'])) {
				$this->_process_book_thumb($_GET['book_thumb']);
				return;
			}

			if (isset($_GET['next_slide'])) {
				$this->_process_next_slide($_GET['next_slide']);
				return;
			}

			if (isset($_GET['fetch_urls'])) {
				$this->_process_fetch_urls($_GET['fetch_urls']);
				return;
			}
			
			if (isset($_GET['book']) && $_GET['book']) {
				$book = $this->model->get_book($_GET['book'], logged_in());
				
				if (!$book) {
					$this->get_content('book_not_found');
					return;
				}
			} else if (isset($_GET['photo']) && $_GET['photo']) {
				$photo = $this->model->get_iter($_GET['photo']);
				$book = $photo -> get('boek');
				if (!$photo || (logged_in() == false && ($book <= 833 || $book == 938 || $book == 836))) {
					$this->get_content('photo_not_found');
					return;
				}
			} else {
				$photo = null;
				$book = null;
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
				else {
					if ($book && logged_in())
						$this->model->mark_read(logged_in('id'), $book);

					$this->get_content('fotoboek', $book);
				}
			} elseif (isset($_POST['submfotobeschrijving']))
				$this->_process_photo_description($photo);
			elseif (isset($_GET['module']) && $_GET['module'] == 'likes')
				$this->_run_likes($photo);
			else
				$this->_view_photo($photo);
		}
	}
	
	$controller = new ControllerFotoboek();
	$controller->run();
?>
