<?php
namespace App\Controller;

require_once 'src/framework/controllers/Controller.php';

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class LegacyController extends \Controller
{
	public function report($name, $match)
	{
		if (get_config_value('path_to_legacy_controller_log') === 'sentry') {
			if (empty(\sentry_get_client()))
				return;


			\Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($name, $match): void {
				$scope->setTag('legacy_script', $name);
				$scope->setTag('legacy_match', $match);

				// Most of these are redundant, but it would be nice to have anyway
				$scope->setContext('legacy', [
					'method'  => $_SERVER['REQUEST_METHOD'] ?? null,
					'uri'     => $_SERVER['REQUEST_URI'] ?? null,
					'query'   => $_SERVER['QUERY_STRING'] ?? null,
					'referer' => $_SERVER['HTTP_REFERER'] ?? null,
					'match'   => $match,
				]);
			});

			\Sentry\captureMessage(sprintf('Legacy URL visited %s', $name));
		} else {
			$message = sprintf(
				'Script: "%s", Method: "%s", URI: "%s", Query: "%s", Referrer: "%s", Match: "%s", IP: "%s", User: "%s", User-agent: "%s"',
				$name,
				$_SERVER['REQUEST_METHOD'] ?? null,
				$_SERVER['REQUEST_URI'] ?? null,
				$_SERVER['QUERY_STRING'] ?? null,
				$_SERVER['HTTP_REFERER'] ?? null,
				$match,
				$_SERVER['REMOTE_ADDR'] ?? null,
				get_auth()->logged_in() ? get_identity()->get('id') : null,
				$_SERVER['HTTP_USER_AGENT'] ?? null,
			);

			if (get_config_value('path_to_legacy_controller_log'))
				error_log(date('c') . ' - ' . $message . "\n", 3, get_config_value('path_to_legacy_controller_log'));
			else
				error_log('Legacy URL visited - ' . $message);
		}
	}

	public function run()
	{
		$parameters = $this->request->attributes->get('_route_params');
		$name = $parameters['name'] ?? null;
		$map = $parameters['map'] ?? null;
		$route = $map[$name] ?? null;

		$this->report($name, !empty($route));

		if (empty($route))
			throw new ResourceNotFoundException();

		$request = $this->request;
		$request->attributes->add($route['parameters']);
		$request->attributes->set('_controller', $route['controller']);

		$controller_class = $route['controller'];
		$controller = new $controller_class($this->request, $this->router);
		$controller->run();
	}
}
