<?php
namespace Vanio\WebBundle\Tests\Templating;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Vanio\WebBundle\Request\RefererResolver;
use Vanio\WebBundle\Templating\WebExtension;

class WebExtensionTest extends TestCase
{
    /** @var \Twig_Environment */
    private $twig;

    protected function setUp()
    {
        $request = new Request;
        $requestStack = new RequestStack;
        $requestStack->push($request);
        $this->twig = new \Twig_Environment(new \Twig_Loader_Array([]));
        $this->twig->addExtension(new WebExtension($this->createRefererResolverMock($request), $requestStack));
    }

    function test_resolving_class_name()
    {
        $this->assertSame('', $this->render('{{ class_name([]) }}'));
        $this->assertSame('foo bar', $this->render('{{ class_name({foo: true, bar: true}) }}'));
        $this->assertSame('foo', $this->render('{{ class_name({foo: true, bar: false}) }}'));
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

    private function render(string $template, array $context = []): string
    {
        return $this->twig->createTemplate($template)->render($context);
    }

    /**
     * @param Request $request
     * @return RefererResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createRefererResolverMock(Request $request): \PHPUnit_Framework_MockObject_MockObject
    {
        $refererResolverMock = $this->createMock(RefererResolver::class);
        $refererResolverMock
            ->expects($this->any())
            ->method('resolveReferer')
            ->with($request)
            ->willReturnArgument(1);

        return $refererResolverMock;
    }
}
