<?php
namespace Vanio\WebBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UploadedFileCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('doctrine.orm.entity_manager')) {
            $container
                ->getDefinition('vanio_web.model.uploaded_file_repository')
                ->setAbstract(false);
            $container
                ->getDefinition('vanio_web.form.uploaded_file_type')
                ->setAbstract(false)
                ->addTag('form.type');
        }
    }
}
