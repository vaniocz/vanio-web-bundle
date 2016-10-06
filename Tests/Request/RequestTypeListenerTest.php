<?php
namespace Vanio\WebBundle\Tests\Request;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Vanio\WebBundle\Request\RequestTypeListener;

class RequestTypeListenerTest extends TestCase
{
    function test_request_type_attribute_on_master_request()
    {
        $requestTypeListener = new RequestTypeListener;
        $event = $this->createGetResponseEvent(HttpKernelInterface::MASTER_REQUEST);
        $requestTypeListener->onKernelRequest($event);
        $this->assertSame(HttpKernelInterface::MASTER_REQUEST, $event->getRequest()->attributes->get('_request_type'));
    }

    function test_request_type_attribute_on_sub_request()
    {
        $requestTypeListener = new RequestTypeListener;
        $event = $this->createGetResponseEvent(HttpKernelInterface::SUB_REQUEST);
        $requestTypeListener->onKernelRequest($event);
        $this->assertSame(HttpKernelInterface::SUB_REQUEST, $event->getRequest()->attributes->get('_request_type'));
    }

    private function createGetResponseEvent(int $requestType): GetResponseEvent
    {
        return new GetResponseEvent($this->createMock(HttpKernelInterface::class), new Request, $requestType);
    }
}
