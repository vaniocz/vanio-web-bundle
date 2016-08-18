<?php
namespace Vanio\WebBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Vanio\WebBundle\DependencyInjection\VanioWebExtension;

class WebExtensionTest extends AbstractExtensionTestCase
{
    function test_default_configuration()
    {
        $this->load();
        $this->assertContainerBuilderHasParameter('vanio_web.referer_fallback_path', '/');
    }

    function test_referer_fallback_path_configuration()
    {
        $this->load(['referer_fallback_path' => 'referer_fallback_path']);
        $this->assertContainerBuilderHasParameter('vanio_web.referer_fallback_path', 'referer_fallback_path');
    }

    /**
     * @return ExtensionInterface[]
     */
    protected function getContainerExtensions(): array
    {
        return [new VanioWebExtension];
    }
}
