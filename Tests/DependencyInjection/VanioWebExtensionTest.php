<?php
namespace Vanio\WebBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Vanio\WebBundle\DependencyInjection\VanioWebExtension;

class VanioWebExtensionTest extends AbstractExtensionTestCase
{
    function test_default_configuration()
    {
        $this->load();
        $this->assertContainerBuilderHasParameter('vanio_web.referer_parameter', '_referer');
        $this->assertContainerBuilderHasParameter('vanio_web.referer_fallback_path', '/');
    }

    function test_detect_request_type_configuration()
    {
        $this->load(['detect_request_type' => true]);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'vanio_web.request.request_type_listener',
            'kernel.event_subscriber'
        );
    }

    function test_referer_fallback_path_configuration()
    {
        $this->load([
            'referer_parameter' => 'referer_parameter',
            'referer_fallback_path' => 'referer_fallback_path',
        ]);
        $this->assertContainerBuilderHasParameter('vanio_web.referer_parameter', 'referer_parameter');
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
