<?php

namespace Illuminate\Routing\Sorting;

use Illuminate\Routing\Route;
use Symfony\Component\Routing\CompiledRoute;

/**
 * Processes routes order promoting routes that are masked by some other route.
 *
 * Class RoutesSorter
 */
class RoutesSorter
{
    /**
     * @param  Route[]  $routes
     *
     * @return Route[]
     */
    public function __invoke(array $routes)
    {
        $order = [];
        $n = 1;
        foreach ($routes as $key => $route) {
            $order[$key] = $n++;
        }

        uksort($routes, function ($key1, $key2) use ($routes, $order) {
            $route1 = $routes[$key1];
            $route2 = $routes[$key2];

            if (! array_intersect($route1->getMethods(), $route2->getMethods())) {
                return $order[$key1] - $order[$key2];
            }

            if (($route1->httpOnly() && $route2->secure()) || ($route2->httpOnly() && $route1->secure())) {
                return $order[$key1] - $order[$key2];
            }

            if ($route1->domain() != $route2->domain()) {
                return $order[$key1] - $order[$key2];
            }

            $isRoute1Shadowed = $this->isRouteMasked($route1, $route2);
            $isRoute2Shadowed = $this->isRouteMasked($route2, $route1);

            if ($isRoute1Shadowed && $isRoute2Shadowed) {
                return $order[$key1] - $order[$key2];
            }
            if ($isRoute1Shadowed) {
                return -1;
            }
            if ($isRoute2Shadowed) {
                return 1;
            }

            return $order[$key1] - $order[$key2];
        });

        return $routes;
    }

    /**
     * Checks if first route is masked by second.
     *
     * @param  Route  $route1
     * @param  Route  $route2
     *
     * @return bool
     */
    private function isRouteMasked(Route $route1, Route $route2)
    {
        $compiledRoute2 = $route2->getCompiled();
        if (! $compiledRoute2->getVariables()) {
            return false;
        }

        $compiledRoute1 = $route1->getCompiled();
        if (preg_match($compiledRoute2->getRegex(), $compiledRoute1->getStaticPrefix())) {
            return true;
        }

        return $this->compareTokens($compiledRoute1, $compiledRoute2);
    }

    /**
     * Checks if the routes have the same variables until the first optional parameter of the second route.
     *
     * @param  CompiledRoute  $route1
     * @param  CompiledRoute  $route2
     *
     * @return bool
     */
    private function compareTokens(CompiledRoute $route1, CompiledRoute $route2)
    {
        $route2FirstOptionalParameterIndex = $this->getFirstOptionalParameterIndex($route2);
        if (null === $route2FirstOptionalParameterIndex) {
            return false;
        }

        $route1Tokens = $route1->getTokens();
        $route2Tokens = $route2->getTokens();

        $key1 = count($route1Tokens) - 1;
        $key2 = count($route2Tokens) - 1;
        $variablesFound2 = 0;
        do {
            $token2 = $route2Tokens[$key2];
            if ($route1Tokens[$key1] != $token2) {
                return false;
            }
            if ('variable' == $token2[0]) {
                ++$variablesFound2;
            }
            --$key1;
            --$key2;
        } while ($key1 >= 0 && $key2 >= 0);

        return $variablesFound2 == $route2FirstOptionalParameterIndex;
    }

    /**
     * @param  CompiledRoute  $route
     *
     * @return int|null|string
     */
    private function getFirstOptionalParameterIndex(CompiledRoute $route)
    {
        if (preg_match_all('/(\(.+?\))/', $route->getRegex(), $matches)) {
            foreach ($matches[1] as $param_index => $match) {
                if (strpos($match, '(?:/') === 0) {
                    return $param_index;
                }
            }
        }

        return;
    }
}
