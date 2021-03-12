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
    public function __construct()
    {
        // We need a view (for the redirect function, and error reporting)
        $this->view = new \View($this);
    }

    public function redirect_route()
    {
        // Copy parameters
        $params = $this->parameters;

        // Remove any parameters we do NOT want to pass to the new route
        unset($params['_route'], $params['_controller'], $params['route'], $params['permanent'], $params['keepQueryParams']);

        // Copy $_GET into the parameters if needed
        if (!empty($this->parameters['keepQueryParams']))
            $params = array_merge($_GET, $params);
    
        // Generate url
        $path = $this->router->generate($this->parameters['route'], $params, UrlGeneratorInterface::ABSOLUTE_URL);

        // Redirect
        $permanent = $this->parameters['permanent'] ?? false;
        return $this->view->redirect($path, $permanent);
    }

    public function redirect_path()
    {
        $permanent = $this->parameters['permanent'] ?? false;

        // Extract flags
        $allow_external_domains = !empty($this->parameters['allowExternalDomains']) ? ALLOW_EXTERNAL_DOMAINS : 0;
        $allow_subdomains = !empty($this->parameters['allowExternalDomains']) ? ALLOW_SUBDOMAINS : 0;

        // Redirect
        return $this->view->redirect($this->parameters['path'], $permanent, $allow_external_domains & $allow_subdomains);
    }

    public function run_impl()
    {
        if (\array_key_exists('route', $this->parameters)) {
            if (\array_key_exists('path', $this->parameters))
                throw new \RuntimeException('Ambiguous redirect settings: use either "route" or "path" parameter.');
            return $this->redirect_route();
        } else if (\array_key_exists('path', $this->parameters)) {
            return $this->redirect_path();
        }

        throw new \RuntimeException('Invalid redirect settings: specify the "route" or "path" parameter.');
    }
}
