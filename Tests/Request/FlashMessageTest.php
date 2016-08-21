<?php
namespace Vanio\WebBundle\Tests\Request;

use Vanio\WebBundle\Request\FlashMessage;

class FlashMessageTest extends \PHPUnit_Framework_TestCase
{
    function test_getting_message()
    {
        $this->assertSame('message', (new FlashMessage('message'))->message());
    }

    function test_getting_parameters()
    {
        $this->assertSame(['parameter'], (new FlashMessage('message', ['parameter']))->parameters());
    }

    function test_getting_domain()
    {
        $this->assertNull((new FlashMessage('message'))->domain());
        $this->assertSame('domain', (new FlashMessage('message', [], 'domain'))->domain());
    }

    function test_getting_locale()
    {
        $this->assertNull((new FlashMessage('message'))->locale());
        $this->assertSame('locale', (new FlashMessage('message', [], null, 'locale'))->locale());
    }

    function test_string_representation()
    {
        $this->assertSame('message', (string) new FlashMessage('message'));
    }
}
