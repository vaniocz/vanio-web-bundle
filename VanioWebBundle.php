<?php
namespace Vanio\WebBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Vanio\WebBundle\DependencyInjection\JsFormValidatorFactoryCompilerPass;
use Vanio\WebBundle\DependencyInjection\UploadedFileCompilerPass;

class VanioWebBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new JsFormValidatorFactoryCompilerPass);
        $container->addCompilerPass(new UploadedFileCompilerPass);
    }
}
