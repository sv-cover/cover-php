<?php
namespace App\Controller;

require_once 'src/framework/controllers/Controller.php';

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class LegacyController extends \Controller
{
    public function report($name, $match)
    {
        if (empty(\sentry_get_client()))
            return;

        \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
            $scope->setTag('legacy_script', $name);
            $scope->setTag('legacy_match', $match);

            // Most of these are redundant, but it would be nice to have anyway
            $scope->setContext('legacy', [
                'method' => $_SERVER['REQUEST_METHOD'],
                'uri' => $_SERVER['REQUEST_URI'],
                'query' => $_SERVER['QUERY_STRING'],
                'referer' => $_SERVER['HTTP_REFERER'],
                'match' => $match,
            ]);
        });

        \Sentry\captureMessage(sprintf('Legacy URL visited %s', $name));
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
