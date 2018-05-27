<?php
namespace Vanio\WebBundle\Tests\Request;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Vanio\WebBundle\Request\RefererHelperTrait;
use Vanio\WebBundle\Request\TargetPathResolver;

class RefererHelperTraitTest extends TestCase
{
    /** @var TargetPathResolver|\PHPUnit_Framework_MockObject_MockObject */
    private $targetPathResolverMock;

    /** @var RequestStack */
    private $requestStack;

    protected function setUp()
    {
        $this->targetPathResolverMock = $this->createMock(TargetPathResolver::class);
        $this->targetPathResolverMock
            ->expects($this->any())
            ->method('resolveReferer')
            ->willReturnArgument(1);
        $this->requestStack = new RequestStack;
        $this->requestStack->push(new Request);
    }

    function test_redirecting_to_referer()
    {
        $refererHelper = new RefererHelper($this->targetPathResolverMock, $this->requestStack);
        $response = $refererHelper->redirectToReferer('referer', Response::HTTP_MOVED_PERMANENTLY, ['key' => 'value']);

        $this->assertSame('referer', $response->getTargetUrl());
        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        $this->assertSame('value', $response->headers->get('key'));
    }

    function test_redirecting_to_referer_using_container_aware_referer_helper()
    {
        $refererHelper = new ContainerAwareRefererHelper;
        $container = new Container;
        $container->set('request_stack', $this->requestStack);
        $container->set('vanio_web.request.referer_resolver', $this->targetPathResolverMock);
        $refererHelper->setContainer($container);
        $response = $refererHelper->redirectToReferer('referer');

        $this->assertSame('referer', $response->getTargetUrl());
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    function test_cannot_redirect_to_referer_without_dependencies_being_set()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to redirect to referer.');
        (new ContainerAwareRefererHelper)->redirectToReferer('referer');
    }
}

class RefererHelper
{
    use RefererHelperTrait {
        redirectToReferer as public;
    }

    public function __construct(TargetPathResolver $targetPathResolver, RequestStack $requestStack)
    {
        $this->targetPathResolver = $targetPathResolver;
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
