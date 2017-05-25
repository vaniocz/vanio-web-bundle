<?php
namespace Vanio\WebBundle\Request;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

class RouteHierarchyResolver
{
    /** @var UrlMatcherInterface */
    private $urlMatcher;

    /** @var array */
    private $hierarchy = [];

    public function __construct(UrlMatcherInterface $urlMatcher)
    {
        $this->urlMatcher = $urlMatcher;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function resolveRouteHierarchy(Request $request): array
    {
        $pathInfo = rtrim($request->getPathInfo(), '/');

        if (isset($this->hierarchy[$pathInfo])) {
            return $this->hierarchy[$pathInfo];
        }

        $segments = explode('/', substr($pathInfo, 1));
        $path = '';

        if ($request->attributes->has('_route')) {
            array_pop($segments);
        }

        $this->hierarchy[$pathInfo] = [];

        foreach ($segments as $segment) {
            $path .= "/$segment";

            if ($attributes = $this->match($path, $request->attributes)) {
                $this->hierarchy[$pathInfo][$attributes['_route']] = $attributes;
            }
        }

        if ($request->attributes->has('_route')) {
            $this->hierarchy[$pathInfo][$request->attributes->get('_route')] = $request->attributes->all();
        }

        return $this->hierarchy[$pathInfo];
    }

    private function match(string $path, ParameterBag $attributes): array
    {
        $parameters = $this->matchPath("$path/", $attributes->get('_locale'))
            ?: $this->matchPath($path, $attributes->get('_locale'));

        if ($parameters) {
            $attributes = $parameters + $attributes->all();
            unset($parameters['_route'], $parameters['_controller']);

            return ['_route_params' => $parameters] + $attributes;
        }

        return [];
    }

    private function matchPath(string $path, string $locale = null): array
    {
        try {
            $parameters = $this->urlMatcher->match($path);

            if (
                isset($parameters['_route'])
                && (!isset($parameters['_locale']) || $parameters['_locale'] === $locale)
            ) {
                return $parameters;
            }
        } catch (ResourceNotFoundException $e) {
        } catch (MethodNotAllowedException $e) {}

        return [];
    }
}
