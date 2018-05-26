<?php
namespace Vanio\WebBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Vanio\WebBundle\Routing\FrameworkAnnotatedRouteControllerLoader;
use Vanio\WebBundle\Routing\FrameworkExtraAnnotatedRouteControllerLoader;
use Vanio\WebBundle\Routing\I18nRoutingAnnotatedRouteControllerLoader;

class OverrideAnnotatedRouteControllerLoadersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('vanio_web.override_default_route_names')) {
            return;
        }

        $definitionClasses = [
            'routing.loader.annotation' => FrameworkAnnotatedRouteControllerLoader::class,
            'sensio_framework_extra.routing.loader.annot_class' => FrameworkExtraAnnotatedRouteControllerLoader::class,
            'be_simple_i18n_routing.loader.annotation_class' => I18nRoutingAnnotatedRouteControllerLoader::class,
        ];

        foreach ($definitionClasses as $id => $class) {
            if ($container->hasDefinition($id)) {
                $container->getDefinition($id)->setClass($class);
            }
        }
    }
}
