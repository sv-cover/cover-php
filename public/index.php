<?php
chdir (dirname(__FILE__) . '/..');
set_include_path ( dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' );

require_once 'src/init.php';
require_once 'src/framework/router.php';

use Symfony\Component\Routing\Exception\ResourceNotFoundException;

try {
	$router = get_router();
	$context = $router->getContext();
	$parameters = $router->match($context->getPathInfo());
	$controller_class = $parameters['_controller'];

	$request = get_request();
	$request->attributes->add($parameters);

	unset($parameters['_route'], $parameters['_controller']);
	$request->attributes->set('_route_params', $parameters);

	$controller = new $controller_class($request, $router);
	$controller->run();
} catch (ResourceNotFoundException $e) {
	$view = new \View();
	echo $view->render_404_not_found(new \NotFoundException());
}
