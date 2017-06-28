<?php
namespace Vanio\WebBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Vanio\WebBundle\Form\JsFormValidatorFactory;

class JsFormValidatorFactoryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('fp_js_form_validator.factory')) {
            $container
                ->getDefinition('fp_js_form_validator.factory')
                ->setClass(JsFormValidatorFactory::class)
                ->addMethodCall('setValidationsConstraintsGuesser', [
                    new Reference('vanio_web.form.validation_constraints_guesser'),
                ]);
        }
    }
}
