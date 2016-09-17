<?php
namespace Vanio\WebBundle\Tests\Request;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Http\HttpUtils;
use Vanio\WebBundle\Request\RefererResolver;

class RefererResolverTest extends TestCase
{
    /** @var RefererResolver */
    private $refererResolver;

    protected function setUp()
    {
        $routes = new RouteCollection;
        $routes->add('foo', new Route('/foo'));
        $routes->add('bar', new Route('/bar'));
        $urlMatcher = new UrlMatcher($routes, new RequestContext);
        $this->refererResolver = new RefererResolver(new HttpUtils, $urlMatcher);
    }

    function test_resolving_known_referer_using_query_parameter()
    {
        $request = Request::create('http://localhost');
        $request->query->set('_referer', 'http://localhost/foo');
        $request->headers->set('referer', 'http://localhost/bar');

        $this->assertSame('http://localhost/foo', $this->refererResolver->resolveReferer($request));
    }

    function test_resolving_known_referer_using_header()
    {
        $request = Request::create('http://localhost');
        $request->headers->set('referer', 'http://localhost/foo');

        $this->assertSame('http://localhost/foo', $this->refererResolver->resolveReferer($request));
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

    function test_resolving_to_fallback_path_using_header_when_query_parameter_is_unknown()
    {
        $request = Request::create('http://localhost');
        $request->query->set('_referer', 'http://localhost/baz');
        $request->headers->set('referer', 'http://localhost/foo');

        $this->assertSame('http://localhost/', $this->refererResolver->resolveReferer($request));
    }

    function test_resolving_to_fallback_path_using_header_when_referer_is_unknown()
    {
        $request = Request::create('http://localhost');
        $request->headers->set('referer', 'http://localhost/baz');

        $this->assertSame('http://localhost/', $this->refererResolver->resolveReferer($request));
    }

    function test_resolving_to_fallback_path_when_referer_is_same_as_request_url()
    {
        $request = Request::create('http://localhost/path');
        $request->headers->set('referer', 'http://localhost/path');

        $this->assertSame('http://localhost/', $this->refererResolver->resolveReferer($request));
    }
}
