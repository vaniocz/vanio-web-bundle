<?php
namespace Vanio\WebBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class VanioWebExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(sprintf('%s/../Resources/config', __DIR__)));
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

        $resources = $container->hasParameter('twig.form.resources')
            ? $container->getParameter('twig.form.resources')
            : [];
        $resources[] = '@VanioWeb/formLayout.html.twig';
        $resources[] = '@VanioWeb/formStartLayout.html.twig';
        $resources[] = '@VanioWeb/formAttributesLayout.html.twig';

        if ($config['recursive_form_label']) {
            $resources[] = '@VanioWeb/recursiveFormLabelLayout.html.twig';
        }

        if ($config['collection_widget']) {
            $resources[] = '@VanioWeb/collectionWidgetLayout.html.twig';
        }

        if ($config['form_choice_widget']) {
            $resources[] = '@VanioWeb/formChoiceWidgetLayout.html.twig';
        }

        $container->setParameter('twig.form.resources', $resources);
    }

    public function prepend(ContainerBuilder $container)
    {
        $container->setParameter('web_root', '%kernel.root_dir%/../web');
        $container->prependExtensionConfig('liip_imagine', [
            'filter_sets' => [
                'uploaded_file_thumbnail' => [
                    'quality' => 90,
                    'filters' => [
                        'thumbnail' => [
                            'size' => [120, 120],
                            'mode' => 'outbound',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
