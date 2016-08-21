<?php
namespace Vanio\WebBundle\Tests\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Http\HttpUtils;
use Vanio\WebBundle\Request\RefererResolver;

class RefererResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var RefererResolver */
    private $refererResolver;

    protected function setUp()
    {
        $routes = new RouteCollection;
        $routes->add('route', new Route('/path'));
        $urlMatcher = new UrlMatcher($routes, new RequestContext);
        $this->refererResolver = new RefererResolver(new HttpUtils, $urlMatcher);
    }

    function test_resolving_known_referer()
    {
        $request = Request::create('http://localhost');
        $request->headers->set('referer', 'http://localhost/path');

        $this->assertSame('http://localhost/path', $this->refererResolver->resolveReferer($request));
    }

    function test_resolving_to_fallback_path_when_referer_is_missing()
    {
        $request = Request::create('http://localhost');

        $this->assertSame('http://localhost/', $this->refererResolver->resolveReferer($request));
        $this->assertSame(
            'http://localhost/fallback-path',
            $this->refererResolver->resolveReferer($request, '/fallback-path')
        );
    }

    function test_resolving_to_fallback_path_when_referer_is_not_routed()
    {
        $request = Request::create('http://localhost');
        $request->headers->set('referer', 'http://localhost/bar');

        $this->assertSame('http://localhost/', $this->refererResolver->resolveReferer($request));
    }

    function test_resolving_to_fallback_path_when_referer_is_same_as_request_url()
    {
        $request = Request::create('http://localhost/path');
        $request->headers->set('referer', 'http://localhost/path');

        $this->assertSame('http://localhost/', $this->refererResolver->resolveReferer($request));
    }
}
