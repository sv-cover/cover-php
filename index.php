<?php
require_once 'include/init.php';
require_once 'include/routing.php';

use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;


try {
    $file_locator = new FileLocator([__DIR__ . DIRECTORY_SEPARATOR . 'src']);
    $loader = new YamlFileLoader($file_locator);

    $request = Request::createFromGlobals();

    $context = new RequestContext();
    $context->fromRequest($request);
    $router = new Router(
        $loader,
        'routes.yaml',
        [
            'matcher_class' => RedirectableCompiledUrlMatcher::class,
            'cache_dir' => get_config_value('routing_cache'),
        ],
        $context
    );

    $parameters = $router->match($context->getPathInfo());
    $controller_class = $parameters['_controller'];

    $request->attributes->add($parameters);

    unset($parameters['_route'], $parameters['_controller']);
    $request->attributes->set('_route_params', $parameters);

    $controller = new $controller_class($request, $router);
    $controller->run();
} catch (ResourceNotFoundException $e) {
    $view = new \View();
    echo $view->render_404_not_found(new \NotFoundException());
}
