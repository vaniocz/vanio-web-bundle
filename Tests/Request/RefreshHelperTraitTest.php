<?php
namespace Vanio\WebBundle\Tests\Request;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Vanio\WebBundle\Request\RefreshHelperTrait;

class RefreshHelperTraitTest extends TestCase
{
    /** @var RequestStack */
    private $requestStack;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    protected function setUp()
    {
        $request = new Request;
        $request->attributes->set('_route', 'route');
        $request->attributes->set('_route_params', ['parameter' => 'value']);

        $this->requestStack = new RequestStack;
        $this->requestStack->push($request);

        $routes = new RouteCollection;
        $routes->add('route', new Route('/path/{parameter}'));
        $this->urlGenerator = new UrlGenerator($routes, new RequestContext);
    }

    function test_refresh()
    {
        $refreshHelper = new RefreshHelper($this->requestStack, $this->urlGenerator);
        $response = $refreshHelper->refresh(Response::HTTP_MOVED_PERMANENTLY, ['key' => 'value']);

        $this->assertSame('/path/value', $response->getTargetUrl());
        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        $this->assertSame('value', $response->headers->get('key'));
    }

    function test_refresh_using_container_aware_refresh_helper()
    {
        $refreshHelper = new ContainerAwareRefreshHelper;
        $container = new Container;
        $container->set('request_stack', $this->requestStack);
        $container->set('router', $this->urlGenerator);
        $refreshHelper->setContainer($container);
        $response = $refreshHelper->refresh();

        $this->assertSame('/path/value', $response->getTargetUrl());
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    function test_cannot_refresh_without_dependencies_being_set()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to refresh.');
        (new ContainerAwareRefreshHelper)->refresh();
    }
}

class RefreshHelper
{
    use RefreshHelperTrait {
        refresh as public;
    }

    public function __construct(RequestStack $requestStack, UrlGeneratorInterface $urlGenerator)
    {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }
}

class ContainerAwareRefreshHelper
{
    use ContainerAwareTrait;
    use RefreshHelperTrait {
        refresh as public;
    }
}
