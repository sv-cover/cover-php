<?php
namespace App\Controller;

require_once 'include/controllers/Controller.php';

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class LegacyController extends \Controller
{
    public function run(Array $parameters, UrlGeneratorInterface $router)
    {
        $name = $parameters['name'] ?? null;
        $map = $parameters['map'] ?? null;
        $route = $map[$name] ?? null;

        if (empty($route))
            throw new ResourceNotFoundException();

        $controller_class = $route['controller'];
        $controller = new $controller_class();
        $controller->run($route['parameters'], $router);
    }
}
