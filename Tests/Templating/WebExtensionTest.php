<?php
namespace Vanio\WebBundle\Tests\Templating;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Vanio\WebBundle\Request\RefererResolver;
use Vanio\WebBundle\Templating\WebExtension;

class WebExtensionTest extends TestCase
{
    /** @var \Twig_Environment */
    private $twig;

    /** @var RequestStack */
    private $requestStack;

    /** @var RequestContext */
    private $requestContext;

    protected function setUp()
    {
        $this->requestContext = new RequestContext;
        $this->requestStack = new RequestStack;
        $this->requestStack->push(new Request);
        $this->twig = new \Twig_Environment(new \Twig_Loader_Array([]));
        $this->twig->addExtension(new WebExtension(
            $this->createTranslator(),
            $this->requestStack,
            $this->createUrlGenerator(),
            $this->createRefererResolverMock()
        ));
    }

    function test_resolving_class_name()
    {
        $this->assertSame('', $this->render('{{ class_name([]) }}'));
        $this->assertSame('foo bar', $this->render('{{ class_name({foo: true, bar: true}) }}'));
        $this->assertSame('foo', $this->render('{{ class_name({foo: true, bar: false}) }}'));
    }

    function test_route_is_current()
    {
        $this->requestStack->push(Request::create('/foo/foo'));
        $this->assertEquals(true, $this->render("{{ is_current('foo') }}"));

        $this->requestStack->push(Request::create('/bar/foo'));
        $this->assertEquals(true, $this->render("{{ is_current('bar') }}"));

        $this->requestStack->getCurrentRequest()->attributes->replace(['_route' => 'foo']);
        $this->assertEquals(true, $this->render("{{ is_current('foo') }}"));

        $this->requestStack->push(Request::create('/baz/parameter/foo'));
        $this->requestStack->getCurrentRequest()->attributes->replace(['parameter' => 'parameter']);
        $this->assertEquals(true, $this->render("{{ is_current('baz') }}"));
    }

    function test_route_is_not_current()
    {
        $this->assertEquals(false, $this->render("{{ is_current('foo') }}"));

        $this->requestStack->push(Request::create('/foo'));
        $this->assertEquals(false, $this->render("{{ is_current('homepage') }}"));

        $this->requestStack->push(Request::create('/baz'));
        $this->assertEquals(false, $this->render("{{ is_current('foo') }}"));

        $this->assertEquals(false, $this->render("{{ is_current('baz') }}"));

        $this->requestStack->getCurrentRequest()->attributes->replace(['parameter' => '']);
        $this->assertEquals(false, $this->render("{{ is_current('baz') }}"));
    }

    function test_string_is_translated()
    {
        $this->assertEquals(true, $this->render("{{ is_translated('foo') }}"));
    }

    function test_string_is_not_translated()
    {
        $this->assertEquals(false, $this->render("{{ is_translated('bar') }}"));
    }

    function test_resolving_referer()
    {
        $this->assertSame('fallback_path', $this->render("{{ referer('fallback_path') }}"));
    }

    function test_converting_html_to_text()
    {
        $this->assertSame('', $this->render("{{ ''|html_to_text }}"));
        $this->assertSame('FOO', $this->render("{{ '<strong>foo</strong>'|html_to_text }}"));
        $this->assertSame("foo\nbar", $this->render("{{ 'foo<br>bar'|html_to_text }}"));
        $this->assertSame("foo\nbar", $this->render("{{ 'foo bar'|html_to_text({width: 1}) }}"));
        $this->assertSame(
            'foo [http://example.com]',
            $this->render("{{ '<a href=\"http://example.com\">foo</a>'|html_to_text }}")
        );
    }

    function test_instance_of_test()
    {
        $this->assertEquals(true, $this->render("{{ value is instance of('stdClass') }}", ['value' => new \stdClass]));
        $this->assertEquals(false, $this->render("{{ value is instance of('stdClass') }}", ['value' => 'value']));
    }

    function test_without_filter()
    {
        $this->assertEquals(
            '{"bar":"baz"}',
            $this->render("{{ {foo: 'bar', bar: 'baz'}|without('foo')|json_encode }}")
        );
        $this->assertEquals(
            '{"baz":"qux"}',
            $this->render("{{ {foo: 'bar', bar: 'baz', baz: 'qux'}|without(['foo', 'bar'])|json_encode }}")
        );
    }

    private function render(string $template, array $context = []): string
    {
        return $this->twig->createTemplate("{% autoescape false %}$template{% endautoescape %}")->render($context);
    }

    private function createTranslator(): Translator
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader);
        $translator->addResource('array', ['foo' => 'foo'], 'en');

        return $translator;
    }

    private function createUrlGenerator(): UrlGenerator
    {
        $routes = new RouteCollection;
        $routes->add('homepage', new Route('/'));
        $routes->add('foo', new Route('/foo'));
        $routes->add('foo_foo', new Route('/foo/foo'));
        $routes->add('bar', new Route('/bar/'));
        $routes->add('bar_foo', new Route('/bar/foo'));
        $routes->add('baz', new Route('/baz/{parameter}/', [], ['parameter' => '\w+']));
        $routes->add('baz_foo', new Route('/baz/{parameter}/foo'));

        return new UrlGenerator($routes, $this->requestContext);
    }

    /**
     * @return RefererResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createRefererResolverMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        $refererResolverMock = $this->createMock(RefererResolver::class);
        $refererResolverMock
            ->expects($this->any())
            ->method('resolveReferer')
            ->willReturnArgument(1);

        return $refererResolverMock;
    }
}
