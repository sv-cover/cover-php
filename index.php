<?php
require_once 'include/init.php';

use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;


try {
    $file_locator = new FileLocator([__DIR__ . DIRECTORY_SEPARATOR . 'src']);
    $loader = new YamlFileLoader($file_locator);

    $context = new RequestContext();
    $context->fromRequest(Request::createFromGlobals());

    $router = new Router(
        $loader,
        'routes.yaml',
        [
            'cache_dir' => get_config_value('routing_cache', 'tmp/router')
        ],
        $context
    );

    $parameters = $router->match($context->getPathInfo());

    $controller_class = $parameters['_controller'];
    $controller = new $controller_class();
    $controller->run($parameters, $router);
} catch (ResourceNotFoundException $e) {
    $view = new \View();
    echo $view->render_404_not_found(new \NotFoundException());
}
