<?php
namespace Vanio\WebBundle\Request;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestTypeListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onRequest'];
    }

    /** @internal */
    public function onRequest(GetResponseEvent $event)
    {
        $event->getRequest()->attributes->set('_request_type', $event->getRequestType());
    }
}
