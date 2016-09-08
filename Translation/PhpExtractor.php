<?php
namespace Vanio\WebBundle\Translation;

use Symfony\Bundle\FrameworkBundle\Command\TranslationUpdateCommand;
use Symfony\Bundle\FrameworkBundle\Translation\PhpExtractor as BasePhpExtractor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class PhpExtractor extends BasePhpExtractor
{
    /** @var array */
    protected $sequences = [
        ['->', 'trans', '(', self::MESSAGE_TOKEN],
        ['->', 'transChoice', '(', self::MESSAGE_TOKEN],
        ['new', 'FlashMessage', '(', self::MESSAGE_TOKEN],
        ['new', 'Translation', '\\', 'FlashMessage', '(', self::MESSAGE_TOKEN],
        ['new', 'WebBundle', '\\', 'Translation', '\\', 'FlashMessage', '(', self::MESSAGE_TOKEN],
        ['new', 'Vanio', '\\', 'WebBundle', '\\', 'Translation', '\\', 'FlashMessage', '(', self::MESSAGE_TOKEN],
        ['new', '\\', 'Vanio', '\\', 'WebBundle', '\\', 'Translation', '\\', 'FlashMessage', '(', self::MESSAGE_TOKEN],
    ];

    /**
     * @param string|array $directory
     * @return Finder|SplFileInfo[]
     */
    protected function extractFromDirectory($directory)
    {
        $finder = (new Finder)->files()->name('*.php');
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

        // https://github.com/symfony/symfony/issues/17739
        if ($trace[4]['class'] ?? null === TranslationUpdateCommand::class) {
            $directory = preg_replace('~/Resources/views$~', '/', $directory);
            $finder->exclude('vendor')->exclude('var');
        }

        return $finder->in($directory);
    }
}
