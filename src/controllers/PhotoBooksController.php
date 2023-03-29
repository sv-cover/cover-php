<?php
namespace App\Controller;

require_once 'src/framework/form.php';
require_once 'src/framework/http.php';
require_once 'src/framework/controllers/Controller.php';
require_once 'src/framework/controllers/ControllerCRUD.php';

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use ZipStream\ZipStream;


trait PhotoBookRouteHelper
{
	private $photo = false;
	private $book = false;

	private function init() {
		$photo = null;
		$book = null;

		$photo_id = $this->get_parameter('photo');
		$book_id = $this->get_parameter('book');

		$model = get_model('DataModelPhotobook');

		// Single photo page
		if (!empty($photo_id))
			$photo = $model->get_iter($photo_id);

		// Book index page
		if (!empty($book_id) && ctype_digit($book_id) && intval($book_id) > 0) {
			$book = $model->get_book($book_id);
		}
		// Likes book page
		elseif (!empty($book_id) && $book_id == 'liked') {
			$book = get_model('DataModelPhotobookLike')->get_book(get_identity()->member());
		}
		// All photos who a certain member is (or mutiple are) tagged in page
		elseif (!empty($book_id) && preg_match('/^member_(\d+(?:_\d+)*)$/', $book_id, $match)) {
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
			$book = $model->get_root_book();
		}

		try {
			if ($photo && $book)
				$photo['scope'] = $book;
		} catch (\LogicException $e) {
			// This occurs when $book is not the book that contains $photo.
			// So we redirect to $photo, and let that figure out $book.
			// No undefined state.
			throw new \RedirectException($this->generate_url(
				$this->request->attributes->get('_route'),
				array_merge(
					$this->request->attributes->get('_route_params'),
					['book' => $photo['boek']],
				),
			));
		}

		$this->photo = $photo;
		$this->book = $book;
	}

	protected function get_photo() {
		if ($this->photo === false)
			$this->init();
		return $this->photo;
	}

	protected function get_book() {
		if ($this->book === false)
			$this->init();
		return $this->book;
	}
}


class PhotoBooksController extends \Controller
{
	use PhotoBookRouteHelper;

	public $policy;
	
	protected $view_name = 'photos';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelPhotobook');

		$this->policy = get_policy($this->model);

		parent::__construct($request, $router);
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
			\DataModelPhotobook::VISIBILITY_PUBLIC,
			\DataModelPhotobook::VISIBILITY_MEMBERS,
			\DataModelPhotobook::VISIBILITY_ACTIVE_MEMBERS,
			\DataModelPhotobook::VISIBILITY_PHOTOCEE
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
	
	private function _view_create_book(\DataIterPhotobook $parent)
	{
		$iter = $parent->new_book();

		if (!$this->policy->user_can_create($iter))
			throw new \UnauthorizedException('You are not allowed to create new photo books inside this photo book.');

		$errors = array();

		if ($this->_form_is_submitted('create_book', $parent))
		{
			// TODO: Move this checking into the model layer..
			$data = $this->_check_fotoboek_values($errors);
			
			$iter->set_all($data);
				
			if (count($errors) === 0)
			{
				$new_book_id = $this->model->insert_book($iter);
				return $this->view->redirect($this->generate_url('photos', ['book' => $new_book_id]));
			}
		}

		return $this->view->render_create_photobook($iter, null, $errors);
	}
	
	private function _view_update_book(\DataIterPhotobook $book)
	{
		if (!$this->policy->user_can_update($book))
			throw new \UnauthorizedException();

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

				return $this->view->redirect($this->generate_url('photos', ['book' => $book->get_id()]));
			}
		}
		
		return $this->view->render_update_photobook($book, $success, $errors);
	}

	private function _view_update_photo_order(\DataIterPhotobook $book)
	{
		if (!$this->_form_is_submitted('update_photo_order', $book))
			throw new \RuntimeException('Missing nonce');

		if (!$this->policy->user_can_update($book))
			throw new \UnauthorizedException();

		if (!isset($_POST['order']))
			throw new \RuntimeException('Order parameter missing');

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

	private function _view_update_book_order(\DataIterPhotobook $parent)
	{
		if (!$this->_form_is_submitted('update_book_order', $parent))
			throw new \RuntimeException('Missing nonce');

		if (!$this->policy->user_can_update($parent))
			throw new \UnauthorizedException();

		if (!isset($_POST['order']))
			throw new \RuntimeException('Order parameter missing');

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

	private function _view_list_photos(\DataIterPhotobook $book)
	{
		if (!$this->policy->user_can_update($book))
			throw new \UnauthorizedException();

		$photos_in_album = $book->get_photos();
		
		$folder = path_concat(get_config_value('path_to_photos'), $_GET['path']);

		$iter = is_dir($folder) ? new \FilesystemIterator($folder) : array();

		// Here $out is actually producing the output to the browser. The $view is entirely ignored here.
		$out = new \HTTPEventStream();
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

	private function _view_list_folders(\DataIterPhotobook $book)
	{
		if (!$this->policy->user_can_update($book))
			throw new \UnauthorizedException();
		
		if (isset($_GET['path']))
			$path = path_concat(get_config_value('path_to_photos'), $_GET['path']);
		else
			$path = get_config_value('path_to_photos');

		$entries = array();

		foreach (new \FilesystemIterator($path) as $entry)
			if (is_dir($entry))
				$entries[] = path_subtract($entry, get_config_value('path_to_photos'));

		rsort($entries);
		return $this->view->render_json($entries);
	}
	
	private function _view_add_photos(\DataIterPhotobook $book)
	{
		if (!$this->policy->user_can_update($book))
			throw new \UnauthorizedException();
		
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
					$iter = new \DataIterPhoto($this->model, -1, array(
						'boek' => $book->get_id(),
						'beschrijving' => $photo['description'],
						'filepath' => $photo['path']));

					if (!$iter->file_exists())
						throw new \Exception("File not found");

					$id = $this->model->insert($iter);
					
					$new_photos[] = new \DataIterPhoto($this->model, $id, $iter->data);
				} catch (\Exception $e) {
					$errors[] = $e->getMessage();
				}
			}

			if (count($new_photos))
			{
				// Update photo book last_update timestamp
				$book['last_update'] = new \DateTime();
				$this->model->update_book($book);

				// Update faces (but re-run on all photos to align clusters)
				$face_model = get_model('DataModelPhotobookFace');
				$face_model->refresh_faces($book->get_photos());
			}
			
			if (count($errors) == 0)
				return $this->view->redirect($this->generate_url('photos', ['book' => $book->get_id()]));
			else
				$success = false;
		}

		return $this->view->render_add_photos($book, $success, $errors);
	}
	
	protected function _view_delete_book(\DataIterPhotobook $book)
	{
		if (!$this->policy->user_can_delete($book))
			throw new \UnauthorizedException();

		$errors = array();

		if ($this->_form_is_submitted('delete', $book))
		{
			if ($_POST['confirm_delete'] == $book->get('titel')) {
				$this->model->delete_book($book);
				return $this->view->redirect($this->generate_url('photos', ['book' => $book->get('parent_id')]));
			}

			$errors[] = 'confirm_delete';
		}
		
		return $this->view->render_delete($book, false, $errors);
	}
	
	protected function _view_delete_photos(\DataIterPhotobook $book)
	{
		if (!$this->policy->user_can_update($book))
			throw new \UnauthorizedException();

		if (!isset($_GET['photo_id']))
			throw new \RuntimeException('photo parameter missing');

		$photos = [];

		foreach ($_GET['photo_id'] as $id)
			if ($photo = $this->model->get_iter($id))
				$photos[] = $photo;
		
		if ($this->_form_is_submitted('delete_photos'))
		{
			foreach ($photos as $photo)
				$this->model->delete($photo);

			return $this->view->redirect($this->generate_url('photos', ['book' => $book->get_id()]));
		}
		
		return $this->view->render_delete_photos($book, $photos);
	}

	protected function _view_mark_read(\DataIterPhotobook $book)
	{
		if (get_auth()->logged_in())
			$this->model->mark_read_recursively(get_identity()->get('id'), $book);

		return $this->view->redirect($this->generate_url('photos', ['book' => $book->get_id()]));
	}

	protected function _view_download_book(\DataIterPhotobook $root_book)
	{
		// This function does not use the $view but produces its own output via ZipStream.

		if (!$this->policy->user_can_download_book($root_book))
			throw new \UnauthorizedException();

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
				if ($this->policy->user_can_download_book($child))
					$books[] = $child;
		
		// Turn that list into a hashtable linking book id to book instance.
		$books = array_combine(
			array_map(curry_call_method('get_id'), $books),
			$books);

		// Apparently nginx doesn't like zipstream
		header('X-Accel-Buffering: no');

		// Set up the output zip stream and just handle all files as large files
		// (meaning no compression, streaming instead of reading into memory.)
		$options = new \ZipStream\Option\Archive();
		$options->setLargeFileSize(1);
		$options->setLargeFileMethod(\ZipStream\Option\Method::STORE());
		$options->setSendHttpHeaders(true);
		$options->setOutputStream(fopen('php://output', 'wb'));

		$zip = new ZipStream(sanitize_filename($root_book->get('titel')) . '.zip', $options);

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

				// Skip things that are not files. Apparently, there are some of those…
				if (!is_file($photo->get_full_path()))
					continue;

				// Skip photo's you cannot access
				if (!get_policy($photo)->user_can_read($photo))
					continue;

				// Let's just assume that the filename the photo already has is sane and safe
				$photo_path = $book_path . '/' . basename($photo->get('filepath'));

				// Add meta data to the zip file if availabley();
				$metadata = new \ZipStream\Option\File();

				if ($photo->has_value('created_on'))
					$metadata->setTime(new \DateTime($photo->get('created_on')));
				else
					$metadata->setTime(new \DateTime(sprintf('@%d', filectime($photo->get_full_path()))));
				
				if ($photo->has_value('beschrijving'))
					$metadata->setComment($photo->get('beschrijving'));

				// And finally add the photo to the actual stream
				$zip->addFileFromPath($photo_path, $photo->get_full_path(), $metadata);
			}
		}

		$zip->finish();
	}

	protected function _view_confirm_download_book(\DataIterPhotobook $root_book)
	{
		if (!$this->policy->user_can_download_book($root_book))
			throw new \UnauthorizedException();

		$books = array($root_book);

		// Make a list of all the books to be added to the zip
		// but filter out the books I can't read.
		for ($i = 0; $i < count($books); ++$i)
			foreach ($books[$i]['books_without_metadata'] as $child)
				if ($this->policy->user_can_download_book($child))
					$books[] = $child;

		$total_photos = 0;
		$total_file_size = 0;

		foreach ($books as $book) {
			foreach ($book->get_photos() as $photo) {
				if ($photo->file_exists() && get_policy($photo)->user_can_read($photo)) {
					$total_photos += 1;
					$total_file_size += $photo->get_file_size();
				}
			}
		}

		return $this->view->render_download_photobook($root_book, $total_photos, $total_file_size);
	}

	protected function _view_read_book(\DataIterPhotobook $book)
	{
		if (!$this->policy->user_can_read($book))
			throw new \UnauthorizedException();

		$rendered_page = $this->view->render_photobook($book);

		if (get_auth()->logged_in())
			$this->model->mark_read(get_identity()->get('id'), $book);

		return $rendered_page;
	}

	protected function _view_people(\DataIterPhotobook $book)
	{
		if (!$this->policy->user_can_read($book))
			throw new \UnauthorizedException();

		$photos = $book->get_photos();

		if ($this->_form_is_submitted('cluster_photos', $book)) {
			if (!$this->policy->user_can_update($book))
				throw new \UnauthorizedException();
			
			$face_model = get_model('DataModelPhotobookFace');
			$face_model->refresh_faces($photos);
		}

		$face_model = get_model('DataModelPhotobookFace');
		$faces = $face_model->get_for_book($book);

		return $this->view->render_people($book, $faces);
	}

	public function json_link_to_update_book_order(\DataIterPhotobook $book)
	{
		$nonce = nonce_generate(nonce_action_name('update_book_order', [$book]));
		return $this->generate_url('photos', ['view' => 'update_book_order', 'book' => $book['id'], '_nonce' => $nonce]);
	}

	public function json_link_to_update_photo_order(\DataIterPhotobook $book)
	{
		$nonce = nonce_generate(nonce_action_name('update_photo_order', [$book]));
		return $this->generate_url('photos', ['view' => 'update_photo_order', 'book' => $book['id'], '_nonce' => $nonce]);
	}

	protected function run_competition() {
		$taggers = get_db()->query('
			SELECT
				l.id,
				l.voornaam,
				COUNT(f_f.id) tags,
				(SELECT
					fav_l.voornaam
				FROM
					foto_faces fav_faces
				LEFT JOIN leden fav_l ON
					fav_l.id = fav_faces.lid_id
				WHERE
					fav_faces.tagged_by = l.id
				GROUP BY
					fav_l.id
				ORDER BY
					COUNT(fav_l.id) DESC
				LIMIT 1) favorite
			FROM
				foto_faces f_f
			LEFT JOIN leden l ON
				l.id = f_f.tagged_by
			WHERE
				f_f.lid_id IS NOT NULL
			GROUP BY
				l.id
			ORDER BY
				tags DESC');

		$tagged = get_db()->query('
			SELECT
				l.id,
				l.voornaam,
				COUNT(f_f.id) tags
			FROM
				foto_faces f_f
			LEFT JOIN leden l ON
				l.id = f_f.lid_id
			WHERE
				f_f.lid_id IS NOT NULL
			GROUP BY
				l.id
			HAVING
				COUNT(f_f.id) > 50
			ORDER BY
				tags DESC');

		return $this->view->render_competition($taggers, $tagged);
	}


	protected function run_slide() {
		$book = $this->model->get_random_book();
		$photos = $this->model->get_photos($book);

		shuffle($photos);

		return $this->view->render_slide($book, $photos);
	}

	protected function run_impl()
	{
		$view = $this->get_parameter('view');

		if (!empty($view) && $view == 'competition')
			return $this->run_competition();

		if (!empty($view) && $view == 'slide')
			return $this->run_slide();

		// Choose the correct view
		if (!empty($this->get_parameter('module'))) {
			switch ($this->get_parameter('module')) {
				case 'comments':
					$controller = new PhotoCommentsController($this->request, $this->router);
					return $controller->run();
				case 'likes':
					$controller = new PhotoLikesController($this->request, $this->router);
					return $controller->run();
				case 'faces':
					$controller = new PhotoFacesController($this->request, $this->router);
					return $controller->run();
				case 'privacy':
					$controller = new PhotoPrivacyController($this->request, $this->router);
					return $controller->run();
			}
		}

		if ($this->get_parameter('photo')) {
			$controller = new PhotosController($this->request, $this->router);
			return $controller->run();
		}

		$photo = $this->get_photo();
		$book = $this->get_book();

		switch ($view)
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

			case 'download_book':
				return $this->_view_download_book($book);

			case 'confirm_download_book':
				return $this->_view_confirm_download_book($book);

			case 'people':
				return $this->_view_people($book);

			default:
				return $this->_view_read_book($book);
		}
	}
}
