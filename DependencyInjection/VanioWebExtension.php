<?php
namespace Vanio\WebBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class VanioWebExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources'));
        $loader->load('config.xml');
        $container->setParameter('vanio_web', $config);

        foreach ($config as $key => $value) {
            $container->setParameter("vanio_web.$key", $value);
        }

        if (!$config['multilingual_supported_locales'] && $container->hasParameter('be_simple_i18n_routing.locales')) {
            $container->setParameter('vanio_web.multilingual_supported_locales', '%be_simple_i18n_routing.locales%');
        }

        if ($config['detect_request_type']) {
            $container
                ->getDefinition('vanio_web.request.request_type_listener')
                ->setAbstract(false)
                ->addTag('kernel.event_subscriber');
        }

        if ($config['render_snippets']) {
            $container
                ->getDefinition('vanio_web.templating.snippet_renderer')
                ->setAbstract(false)
                ->addTag('kernel.event_subscriber');
        }

        if ($config['multilingual']) {
            $container
                ->getDefinition('vanio_web.request.multilingual_listener')
                ->setAbstract(false)
                ->addTag('kernel.event_subscriber');
        }
    }
}
