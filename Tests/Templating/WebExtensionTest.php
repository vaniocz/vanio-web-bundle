<?php
namespace Vanio\WebBundle\Tests\Templating;

use PHPUnit\Framework\TestCase;
use Vanio\WebBundle\Templating\WebExtension;

class WebExtensionTest extends TestCase
{
    /** @var \Twig_Environment */
    private $twig;

    protected function setUp()
    {
        $this->twig = new \Twig_Environment(new \Twig_Loader_Array([]));
        $this->twig->addExtension(new WebExtension);
    }

    function test_resolving_class_name()
    {
        $this->assertSame('', $this->render('{{ class_name([]) }}'));
        $this->assertSame('foo bar', $this->render('{{ class_name({foo: true, bar: true}) }}'));
        $this->assertSame('foo', $this->render('{{ class_name({foo: true, bar: false}) }}'));
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
}
