<?php
namespace App\Controller;

require_once 'include/controllers/Controller.php';

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


/**
  * A controller more or less analogous to Symfony's RedirectController. This is
  * used to make redirecting routes.
  *
  * Usage:
  * ======
  *
  * Available parameters when redirecting to a route:
  * - route: the name of the route to redirect to
  * - keepQueryParams: this will merge the $_GET params into the array with
  *     parameter used when generating the redirect url.
  * - permanent: if true, HTTP status 301 will be used
  * - [name]: any parameter you want to pass to the route
  *
  * Available parameters when redirecting to a url:
  * - path: the url to redirect to
  * - permanent: if true, HTTP status 301 will be used
  * - allowSubdomain: enables redirect to a subdomain of current domain if true
  * - allowExternalDomain: enables redirect to an external domain if true
  */
class RedirectController extends \Controller
{
	public function redirect_route($parameters)
	{
		// Copy parameters
		$args = $this->request->attributes->get('_route_params');

		// Remove any parameters we do NOT want to pass to the new route
		unset($args['route'], $args['permanent'], $args['keepQueryParams']);

		// Copy $_GET into the parameters if needed
		if (!empty($parameters['keepQueryParams']))
			$args = array_merge($_GET, $args);
	
		// Generate url
		$path = $this->router->generate($parameters['route'], $args, UrlGeneratorInterface::ABSOLUTE_URL);

		// Redirect
		$permanent = $parameters['permanent'] ?? false;
		return $this->view->redirect($path, $permanent);
	}

	public function redirect_path($parameters)
	{
		$permanent = $parameters['permanent'] ?? false;

		// Extract flags
		$allow_external_domains = !empty($parameters['allowExternalDomains']) ? ALLOW_EXTERNAL_DOMAINS : 0;
		$allow_subdomains = !empty($parameters['allowExternalDomains']) ? ALLOW_SUBDOMAINS : 0;

		// Redirect
		return $this->view->redirect($parameters['path'], $permanent, $allow_external_domains & $allow_subdomains);
	}

	public function run_impl()
	{
		$parameters = $this->request->attributes->get('_route_params');
		if (\array_key_exists('route', $parameters)) {
			if (\array_key_exists('path', $parameters))
				throw new \RuntimeException('Ambiguous redirect settings: use either "route" or "path" parameter.');
			return $this->redirect_route($parameters);
		} else if (\array_key_exists('path', $parameters)) {
			return $this->redirect_path($parameters);
		}

		throw new \RuntimeException('Invalid redirect settings: specify the "route" or "path" parameter.');
	}
}
