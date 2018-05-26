<?php
namespace Vanio\WebBundle\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\AnnotatedRouteControllerLoader;

class FrameworkAnnotatedRouteControllerLoader extends AnnotatedRouteControllerLoader
{
    use DefaultRouteNameTrait;
}
