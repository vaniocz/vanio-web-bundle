<?php
namespace Vanio\WebBundle\Tests\Fixtures;

use Symfony\Component\Translation\Translator;
use Vanio;
use Vanio\WebBundle;
use Vanio\WebBundle\Translation;
use Vanio\WebBundle\Translation\FlashMessage;

$translator = new Translator('locale');
$translator->trans('foo');
$translator->transChoice('bar', 1);

new FlashMessage('baz');
new Translation\FlashMessage('qux', ['foo' => 'foo']);
new WebBundle\Translation\FlashMessage('quux', [], 'domain');
new Vanio\WebBundle\Translation\FlashMessage('corge', [], null, 'locale');
new \Vanio\WebBundle\Translation\FlashMessage('grault');
