<?php

use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\RedirectableUrlMatcherInterface;


/**
  * Routing helper to make route matching agnostic to trailing slashes
  */
class RedirectableCompiledUrlMatcher extends CompiledUrlMatcher implements RedirectableUrlMatcherInterface
{
    public function redirect(string $path, string $route, string $scheme = null): array
    {
        return [
            '_controller' => 'App\\Controller\\RedirectController',
            'path' => $path,
            'permanent' => true,
            '_route' => $route,
        ];
    }
}
