<?php
namespace Vanio\WebBundle\Tests\Templating;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Vanio\WebBundle\Request\RouteHierarchyResolver;
use Vanio\WebBundle\Request\TargetPathResolver;
use Vanio\WebBundle\Templating\TwigFormRendererEngine;
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
            $this->createRouterMock(),
            new TwigFormRendererEngine,
            $this->requestStack,
            $this->createTargetPathResolverMock(),
            new RouteHierarchyResolver($this->createUrlMatcher()),
            null,
            sys_get_temp_dir()
        ));
    }

    function test_resolving_class_name()
    {
        $this->assertSame('', $this->render('{{ class_name([]) }}'));
        $this->assertSame('foo bar', $this->render('{{ class_name({foo: true, bar: true}) }}'));
        $this->assertSame('foo', $this->render('{{ class_name({foo: true, bar: false}) }}'));
    }

    function test_resolving_attributes()
    {
        $this->assertSame('', $this->render('{{ attributes({}) }}'));
        $this->assertSame('', $this->render('{{ attributes({required: false}) }}'));
        $this->assertSame('required="required"', $this->render('{{ attributes({required: true}) }}'));
        $this->assertSame('class="foo"', $this->render("{{ attributes({class: 'foo'}) }}"));
        $this->assertSame(
            'class="foo bar" name="value"',
            $this->render("{{ attributes({class: {foo: true, bar: true}, name: 'value'}) }}")
        );
        $this->assertSame('name="&lt;value&gt;"', $this->render("{{ attributes({name: '<value>'}) }}"));
    }

    function test_string_is_translated()
    {
        $this->assertEquals(true, $this->render("{{ is_translated('foo') }}"));
    }

    function test_string_is_not_translated()
    {
        $this->assertEquals(false, $this->render("{{ is_translated('bar') }}"));
        $this->assertEquals(false, $this->render("{{ is_translated('baz') }}"));
    }

    function test_resolving_referer()
    {
        $this->assertSame('fallback_path', $this->render("{{ referer('fallback_path') }}"));
    }

    function test_route_is_current()
    {
        $this->requestStack->push(Request::create('/foo/foo'));
        $this->assertEquals(true, $this->render("{{ is_current('foo') }}"));

        $this->requestStack->push(Request::create('/bar/foo'));
        $this->assertEquals(true, $this->render("{{ is_current('bar') }}"));

        $this->requestStack->getCurrentRequest()->attributes->set('_route', 'foo');
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

        $this->requestStack->getCurrentRequest()->attributes->set('parameter', '');
        $this->assertEquals(false, $this->render("{{ is_current('baz') }}"));
    }

    function test_without_filter()
    {
        $this->assertEquals(
            '["foo","bar"]',
            $this->render("{{ ['foo', 'bar']|without('baz')|json_encode }}")
        );
        $this->assertEquals(
            '{"1":"bar"}',
            $this->render("{{ ['foo', 'bar']|without('foo')|json_encode }}")
        );
        $this->assertEquals(
            '{"2":"baz"}',
            $this->render("{{ ['foo', 'bar', 'baz']|without(['foo', 'bar'])|json_encode }}")
        );
    }

    function test_without_keys_filter()
    {
        $this->assertEquals(
            '{"bar":"baz"}',
            $this->render("{{ {foo: 'bar', bar: 'baz'}|without_keys('foo')|json_encode }}")
        );
        $this->assertEquals(
            '{"baz":"qux"}',
            $this->render("{{ {foo: 'bar', bar: 'baz', baz: 'qux'}|without_keys(['foo', 'bar'])|json_encode }}")
        );
    }

    function test_without_empty_filter()
    {
        $this->assertEquals('{"4":"0"}', $this->render("{{ ['', false, null, [], '0']|without_empty|json_encode }}"));
    }

    function test_replacing_using_regular_expression()
    {
        $this->assertSame('bar', $this->render("{{ 'foo'|regexp_replace('~foo~', 'bar') }}"));
        $this->assertSame('baz qux', $this->render("{{ 'foo bar'|regexp_replace({'~foo~': 'baz', '~bar~': 'qux'}) }}"));
        $this->assertSame('baz baz', $this->render("{{ 'foo bar'|regexp_replace(['~foo~', '~bar~'], 'baz') }}"));
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

    function test_evaluating_twig_template()
    {
        $this->assertSame('foo bar', $this->render("{{ '{{ foo }} {{ bar }}'|evaluate({foo: 'foo', bar: 'bar'}) }}"));
        $this->assertSame('%foo%', $this->render("{{ '%foo%'|evaluate() }}"));
    }

    function test_instance_of_test()
    {
        $this->assertEquals(true, $this->render("{{ value is instance of('stdClass') }}", ['value' => new \stdClass]));
        $this->assertEquals(false, $this->render("{{ value is instance of('stdClass') }}", ['value' => 'value']));
    }

    private function render(string $template, array $context = []): string
    {
        return $this->twig->createTemplate("{% autoescape false %}$template{% endautoescape %}")->render($context);
    }

    private function createTranslator(): Translator
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader);
        $translator->addResource('array', ['foo' => 'foo', 'baz' => false], 'en');

        return $translator;
    }

    private function createRouterMock(): RouterInterface
    {
        return $this->createMock(RouterInterface::class);
    }

    private function createUrlMatcher(): UrlMatcher
    {
        $routes = new RouteCollection;
        $routes->add('homepage', new Route('/'));
        $routes->add('foo', new Route('/foo'));
        $routes->add('foo_foo', new Route('/foo/foo'));
        $routes->add('bar', new Route('/bar/'));
        $routes->add('bar_foo', new Route('/bar/foo'));
        $routes->add('baz', new Route('/baz/{parameter}/', [], ['parameter' => '\w+']));
        $routes->add('baz_foo', new Route('/baz/{parameter}/foo'));

        return new UrlMatcher($routes, $this->requestContext);
    }

    /**
     * @return TargetPathResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createTargetPathResolverMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        $targetPathResolverMock = $this->createMock(TargetPathResolver::class);
        $targetPathResolverMock
            ->expects($this->any())
            ->method('resolveReferer')
            ->willReturnArgument(1);

        return $targetPathResolverMock;
    }
}
