<?php
namespace Vanio\WebBundle\Tests\Request;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Vanio\WebBundle\Request\RequestTypeListener;

class RequestTypeListenerTest extends TestCase
{
    function test_request_type_attribute_on_master_request()
    {
        $requestTypeListener = new RequestTypeListener;
        $event = $this->createRequestEvent(HttpKernelInterface::MASTER_REQUEST);
        $requestTypeListener->onRequest($event);
        $this->assertSame(HttpKernelInterface::MASTER_REQUEST, $event->getRequest()->attributes->get('_request_type'));
    }

    function test_request_type_attribute_on_sub_request()
    {
        $requestTypeListener = new RequestTypeListener;
        $event = $this->createRequestEvent(HttpKernelInterface::SUB_REQUEST);
        $requestTypeListener->onRequest($event);
        $this->assertSame(HttpKernelInterface::SUB_REQUEST, $event->getRequest()->attributes->get('_request_type'));
    }

    private function createRequestEvent(int $requestType): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), new Request, $requestType);
    }
}
