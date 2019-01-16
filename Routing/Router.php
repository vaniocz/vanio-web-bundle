<?php
namespace Vanio\WebBundle\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Vanio\Stdlib\Strings;

class Router implements RouterInterface, RequestMatcherInterface
{
    /** @var RouterInterface */
    private $router;

    /** @var RequestStack */
    private $requestStack;

    /** @var mixed[] */
    private $persistentParameters;

    public function __construct(
        RouterInterface $router,
        RequestStack $requestStack,
        array $persistentParameters
    ) {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->persistentParameters = $persistentParameters;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param string $pathinfo
     * @return string[]
     */
    public function match($pathinfo): array
    {
        return $this->router->match($pathinfo);
    }

    /**
     * @return mixed[]
     */
    public function matchRequest(Request $request): array
    {
        return $this->router->matchRequest($request);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param string $name
     * @param string[] $parameters
     * @param int $referenceType
     * @return string
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        $request = $this->requestStack->getCurrentRequest();

        foreach ($this->persistentParameters as $parameterName => $routeNames) {
            if ($request && $request->query->has($parameterName) && $this->isRouteInList($name, $routeNames)) {
                $parameters += [$parameterName => $request->query->get($parameterName)];
            }
        }

        return $this->router->generate($name, $parameters, $referenceType);
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->router->getRouteCollection();
    }

    public function setContext(RequestContext $context): void
    {
        $this->router->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->router->getContext();
    }

    private function isRouteInList(string $name, array $routesList): bool
    {
        foreach ($routesList as $routeName) {
            if (
                $routeName === $name
                || (Strings::endsWith($routeName, '*') && Strings::startsWith($name, Strings::trimRight($routeName, '*')))
            ) {
                return true;
            }
        }

        return false;
    }
}
