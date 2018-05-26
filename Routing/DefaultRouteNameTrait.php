<?php
namespace Vanio\WebBundle\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\AnnotatedRouteControllerLoader;
use Vanio\Stdlib\Strings;

trait DefaultRouteNameTrait
{
    protected function getDefaultRouteName(
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $reflectionMethod
    ): string {
        if (Strings::startsWith($reflectionClass->getFileName(), substr(__DIR__, 0, -30))) {
            return preg_replace(
                ['~(bundle|controller)_~', '~action(_\d+)?$~', '~__~'],
                ['_', '\\1', '_'],
                parent::getDefaultRouteName($reflectionClass, $reflectionMethod)
            );
        }

        $defaultRouteName = sprintf(
            '%s_%s_%',
            preg_replace('~(bundle|controller)(\\\|$)~i', '', $reflectionClass->name),
            preg_replace('~^index(Action)?$|action$~i', '', $reflectionMethod->name),
            $this->defaultRouteIndex++ ? $this->defaultRouteIndex : ''
        );

        return Strings::convertToSnakeCase($defaultRouteName);
    }
}
