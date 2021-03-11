<?php
require_once 'include/init.php';

use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;


try
{
    $fileLocator = new FileLocator([__DIR__ . DIRECTORY_SEPARATOR . 'public']);
    $loader = new YamlFileLoader($fileLocator);
    $routes = $loader->load('routes.yaml');

    $context = new RequestContext();
    $context->fromRequest(Request::createFromGlobals());

    // Routing can match routes with incoming requests
    $matcher = new UrlMatcher($routes, $context);
    $parameters = $matcher->match($context->getPathInfo());


    $generator = new UrlGenerator($routes, $context);

    $controller_class = $parameters['_controller'];
    $controller = new $controller_class();
    $controller->run($parameters, $generator);
}
catch (ResourceNotFoundException $e)
{
    $view = new \View();
    echo $view->render_404_not_found(new \NotFoundException());
}
