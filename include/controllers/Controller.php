<?php
	if (!defined('IN_SITE'))
		return;

	require_once 'include/functions.php';
	require_once 'include/markup.php';


	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\Routing\RouterInterface;
	use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

	/** 
	  * A class implementing the simplest controller. This class provides
	  * viewing a simple static page by running the header view, then
	  * the specified view and then the footer view
	  */
	class Controller
	{
		protected $view_name;

		protected $view;

		protected $model;

		protected $request;

		protected $router;

		public function __construct(Request $request, RouterInterface $router, $view = null)
		{
			$this->request = $request;
			$this->router = $router;

			if (isset($view))
				$this->view = $view;
			elseif (isset($this->view_name))
				$this->view = \View::byName($this->view_name, $this);
			else
				$this->view = new \View($this);
		}

		public function view()
		{
			return $this->view;
		}
		
		public function model()
		{
			return $this->model;
		}
		
		public function run()
		{
			try {
				try {
					echo $this->run_impl();
				}
				catch (Exception $e) {
					echo $this->run_exception($e);
				}
				catch (TypeError $e) {
					echo $this->run_exception($e);
				}
			} catch (Exception $e) {
				sentry_report_exception($e);

				if (get_config_value('show_exceptions'))
					printf('<pre>%s</pre>', $e);

				die('Exception during the exception?! Something went double wrong!');
			}
		}

		protected function run_impl()
		{
			return '';
		}

		protected function run_exception($e)
		{
			if ($e instanceof NotFoundException)
				return $this->run_404_not_found($e);
			elseif ($e instanceof UnauthorizedException)
				return $this->run_401_unauthorized($e);
			else
				return $this->run_500_internal_server_error($e);
		}

		protected function run_401_unauthorized(UnauthorizedException $exception)
		{
			return $this->view()->render_401_unauthorized($exception);
		}

		protected function run_404_not_found(NotFoundException $exception)
		{
			//sentry_report_exception($exception, ['level' => 'warning']);

			return $this->view()->render_404_not_found($exception);
		}

		protected function run_500_internal_server_error($e)
		{
			if (!headers_sent())
				header('Status: 500 Interal Server Error');

			$sentry_id = sentry_report_exception($e);

			return $this->view()->render('@layout/500.twig', ['exception' => $e, 'sentry_id' => $sentry_id]);
		}

		protected function _form_is_submitted($action, $args = [])
		{
			$args = func_get_args();
			array_shift($args);
			
			// Turn _form_is_submitted('delete', iter) to 'delete_24'
			$action_name = nonce_action_name($action, $args);

			$nonce = null;

			if (!empty($_POST['_nonce']))
				$nonce = $_POST['_nonce'];
			else if (!empty($_GET['_nonce']))
				$nonce = $_GET['_nonce'];

			$answer = $_SERVER['REQUEST_METHOD'] == 'POST'
				&& $nonce !== null
				&& nonce_verify($nonce, $action_name);

			return $answer;
		}

		public function generate_url(string $name, array $parameters = [], int $reference_type = UrlGeneratorInterface::ABSOLUTE_PATH)
		{
			if (!isset($this->router))
				throw new LogicException('Router not set on controller');
			return $this->router->generate($name, $parameters, $reference_type);
		}

		public function get_referrer(string $key = 'referrer')
		{
			if (!empty($_GET[$key]) && is_safe_redirect($_GET[$key]))
				return $_GET[$key];
			return null;
		}

		public function get_request()
		{
			return $this->request;
		}

		public function get_router()
		{
			return $this->router;
		}

		public function get_parameter($key, $default=null)
		{
			if ($this !== $result = $this->request->attributes->get($key, $this)) {
				return $result;
			}

			return $this->request->query->get($key, $default);
		}

		final protected function get_content()
		{
			throw new LogicException("Controller::get_content is no longer accepted");
		}
	}
