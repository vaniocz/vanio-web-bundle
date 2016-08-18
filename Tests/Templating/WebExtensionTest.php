<?php
namespace Vanio\WebBundle\Tests\Templating;

use Vanio\WebBundle\Templating\WebExtension;

class WebExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Twig_Environment */
    private $twig;

    public function setUp()
    {
        $this->twig = new \Twig_Environment(new \Twig_Loader_Array([]));
        $this->twig->addExtension(new WebExtension);
    }

    function test_resolving_class_name()
    {
        $this->assertSame('', $this->render('{{ class_name([]) }}'));
        $this->assertSame('', $this->render("{{ class_name([' ']) }}"));
        $this->assertSame('class', $this->render("{{ class_name(['class']) }}"));
        $this->assertSame('foo bar', $this->render('{{ class_name({foo: true, bar: true}) }}'));
        $this->assertSame('foo', $this->render('{{ class_name({foo: true, bar: false}) }}'));
        $this->assertSame('foo baz', $this->render("{{ class_name({foo: true, bar: false, 0: 'baz'}) }}"));
    }

    private function render(string $template, array $context = []): string
    {
        return $this->twig->createTemplate($template)->render($context);
    }
}
