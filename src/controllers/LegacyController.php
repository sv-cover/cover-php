<?php
namespace App\Controller;

require_once 'include/controllers/Controller.php';

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class LegacyController extends \Controller
{
    public function run()
    {
        $parameters = $this->request->attributes->get('_route_params');
        $name = $parameters['name'] ?? null;
        $map = $parameters['map'] ?? null;
        $route = $map[$name] ?? null;

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
