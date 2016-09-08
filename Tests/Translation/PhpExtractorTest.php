<?php
namespace Vanio\WebBundle\Tests\Translation;

use Symfony\Component\Translation\MessageCatalogue;
use Vanio\WebBundle\Translation\PhpExtractor;

class PhpExtractorTest extends \PHPUnit_Framework_TestCase
{
    function test_extracting_messages()
    {
        $catalogue = new MessageCatalogue('locale', []);
        (new PhpExtractor)->extract(realpath(sprintf('%s/../Fixtures', __DIR__)), $catalogue);
        $messages = [
            'foo' => 'foo',
            'bar' => 'bar',
            'baz' => 'baz',
            'qux' => 'qux',
            'quux' => 'quux',
            'corge' => 'corge',
            'grault' => 'grault',
        ];
        $this->assertEquals(['messages' => $messages], $catalogue->all());
    }
}
