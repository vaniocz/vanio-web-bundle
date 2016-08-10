<?php
namespace Vanio\WebBundle\Tests\Templating;

use Vanio\WebBundle\Templating\WebExtension;

class WebExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebExtension */
    private $webExtension;

    public function setUp()
    {
        $this->webExtension = new WebExtension;
    }

    function test_resolving_class_name()
    {
        $this->assertSame('', $this->webExtension->className([]));
        $this->assertSame('', $this->webExtension->className([' ']));
        $this->assertSame('class', $this->webExtension->className(['class']));
        $this->assertSame('foo bar', $this->webExtension->className(['foo' => true, 'bar' => true]));
        $this->assertSame('foo', $this->webExtension->className(['foo' => true, 'bar' => false]));
        $this->assertSame('foo baz', $this->webExtension->className(['foo' => true, 'bar' => false, 'baz']));
    }
}
