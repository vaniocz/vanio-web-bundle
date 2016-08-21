<?php
namespace Vanio\WebBundle\Tests\Request;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Vanio\WebBundle\Request\RefererHelperTrait;
use Vanio\WebBundle\Request\RefererResolver;

class RefererHelperTraitTest extends \PHPUnit_Framework_TestCase
{
    /** @var RefererResolver|\PHPUnit_Framework_MockObject_MockObject */
    private $refererResolverMock;

    /** @var RequestStack */
    private $requestStack;

    protected function setUp()
    {
        $this->refererResolverMock = $this->createMock(RefererResolver::class);
        $this->refererResolverMock
            ->expects($this->any())
            ->method('resolveReferer')
            ->willReturnArgument(1);
        $this->requestStack = new RequestStack;
        $this->requestStack->push(new Request);
    }

    function test_redirecting_to_referer()
    {
        $refererHelper = new RefererHelper($this->refererResolverMock, $this->requestStack);

        $this->assertSame('referer', $refererHelper->redirectToReferer('referer')->getTargetUrl());
    }

    function test_redirecting_to_referer_using_container_aware_referer_helper()
    {
        $refererHelper = new ContainerAwareRefererHelper;
        $container = new Container;
        $container->set('request_stack', $this->requestStack);
        $container->set('vanio_web.request.referer_resolver', $this->refererResolverMock);
        $refererHelper->setContainer($container);

        $this->assertSame('referer', $refererHelper->redirectToReferer('referer')->getTargetUrl());
    }

    function test_cannot_redirect_to_referer_without_dependencies_being_set()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to redirect to referer.');
        (new ContainerAwareRefererHelper)->redirectToReferer('referer')->getTargetUrl();
    }
}

class RefererHelper
{
    use RefererHelperTrait {
        redirectToReferer as public;
    }

    public function __construct(RefererResolver $refererResolver, RequestStack $requestStack)
    {
        $this->refererResolver = $refererResolver;
        $this->requestStack = $requestStack;
    }
}

class ContainerAwareRefererHelper
{
    use ContainerAwareTrait;
    use RefererHelperTrait {
        redirectToReferer as public;
    }
}
