<?php
namespace App\Controller;

require_once 'src/controllers/PhotoBooksController.php';
require_once 'src/framework/controllers/ControllerCRUD.php';

class PhotosController extends \ControllerCRUD
{
	use PhotoBookRouteHelper;

	protected $view_name = 'photos';
	protected $_var_id = 'photo';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelPhotobook');

		parent::__construct($request, $router);
	}

	public function path(string $view, \DataIter $iter = null, bool $json = false)
	{
		$parameters = [
			'photo' => $this->get_photo()->get_id(),
		];


		if (isset($iter))
		{
			$parameters[$this->_var_id] = $iter->get_id();

			if ($json)
				$parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));
		}

		return $this->generate_url('photos', $parameters);
	}

	protected function _read($id)
	{
		return $this->get_photo();
	}

	public function run_read(\DataIter $iter)
	{
		if (!get_policy($iter)->user_can_read($iter))
			throw new \UnauthorizedException('You are not allowed to see this photo.');

		return $this->view->render_photo($this->get_book(), $iter);
	}

	public function run_update(\DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_read($iter->get_book()))
			throw new \UnauthorizedException('You are not allowed to update this photo.');

		if ($this->_form_is_submitted('update', $iter)) {
			$iter->set('beschrijving', $_POST['beschrijving']);
			$this->model->update($iter);
			return $this->view->redirect($this->generate_url('photos', [
				'book' => $this->get_book()->get_id(),
				'photo' => $iter->get_id()
			]));
		}

		return $this->view->render_update_photo($this->get_book(), $iter, null, []);
	}

	// Compatibility with old views
	public function run_update_photo(\DataIter $iter)
	{
		return $this->run_update($iter);
	}

	protected function run_scaled()
	{
		$photo = $this->get_photo();

		if (!get_policy($photo)->user_can_read($photo))
			throw new \UnauthorizedException('You may need to log in to view this photo');

		$width = $this->get_parameter('width');
		$height = $this->get_parameter('height');

		$width = !empty($width) && ctype_digit($width) ? min($width, 2400) : null;
		$height = !empty($height) ? min($height, 2400) : null;

		if (get_config_value('url_to_scaled_photo')) {
			return $this->view->redirect(sprintf(
				get_config_value('url_to_scaled_photo'),
				$photo->get_id(),
				$width,
				$height,
			), false, ALLOW_EXTERNAL_DOMAINS);
		}

		$cache_status = null;

		// First open the resource because this could throw a 404 exception with
		// the appropriate headers.
		$file_path = $photo->get_resource($width, $height, !empty($_GET['skip_cache']), $cache_status);

		$last_modified = gmdate(DATE_RFC1123,filemtime($file_path));

		$cache_expires = 180*24*3600;

		header('Pragma: public');
		header('Cache-Control: public, max-age=' . $cache_expires);
		// Don't send a file if the browser has a cached one already
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $last_modified) {
			http_response_code(304);
			return;
		}

		header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_expires));
		header('Last-Modified: ' . $last_modified);
		header('X-Cache-Status: ' . $cache_status);
		
		if (substr($photo['filepath'], -3, 3) == 'gif')
			header('Content-Type: image/gif');
		else
			header('Content-Type: image/jpeg');

		if (get_config_value('nginx_accel_path_to_cache') && get_config_value('nginx_accel_url_to_cache')) {
			$file_name = path_subtract($file_path, get_config_value('nginx_accel_path_to_cache'));
			$accel_path = path_concat(get_config_value('nginx_accel_url_to_cache'), $file_name);
			header('X-Accel-Redirect: ' . $accel_path);
			header('X-Test-path: ' . $accel_path);
			exit;
		}

		$fhandle = fopen($file_path, 'rb');
		fpassthru($fhandle);
		fclose($fhandle);
	}


	protected function run_download()
	{
		// Note that this function ignores the view completely and produces output on its own.

		// We don't want 'guests' to download our originals
		if (!get_auth()->logged_in())
			throw new \UnauthorizedException();

		$photo = $this->get_photo();

		// Also, you need at least read access to this photo
		if (!get_policy($photo)->user_can_read($photo))
			throw new \UnauthorizedException();

		if (!$photo->file_exists())
			throw new \NotFoundException('Could not find original file');

		if (preg_match('/\.(jpg|gif)$/i', $photo->get('filepath'), $match))
			header('Content-Type: image/' . strtolower($match[1]));

		header('Content-Disposition: attachment; filename="' . addslashes(basename($photo->get('filepath'))) . '"');
		header('Content-Length: ' . filesize($photo->get_full_path()));

		$fhandle = fopen($photo->get_full_path(), 'rb');
		fpassthru($fhandle);
		fclose($fhandle);
	}

	public function run_create()
	{
		throw new \NotFoundException();
	}

	public function run_delete(\DataIter $iter)
	{
		throw new \NotFoundException();
	}

	public function run_index()
	{
		throw new \NotFoundException();
	}

	protected function run_impl()
	{
		if (!$this->get_photo())
			throw new \RuntimeException('You cannot access the photo auxiliary functions without also selecting a photo');
		return parent::run_impl();
	}
}
