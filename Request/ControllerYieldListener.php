<?php
namespace Vanio\WebBundle\Request;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\TranslatorInterface;
use Vanio\WebBundle\Translation\FlashMessage;

class ControllerYieldListener implements EventSubscriberInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var SessionInterface */
    private $session;

    /** @var mixed[] */
    private $headers = [];

    public function __construct(TranslatorInterface $translator, SessionInterface $session)
    {
        $this->translator = $translator;
        $this->session = $session;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', PHP_INT_MAX],
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * @internal
     */
    public function onKernelView(GetResponseForControllerResultEvent $event): void
    {
        $controllerResult = $event->getControllerResult();

        if ($controllerResult instanceof \Generator) {
            foreach ($controllerResult as $result) {
                if ($result instanceof Header) {
                    $this->headers[$result->name()] = $result->value();
                } elseif ($result instanceof FlashMessage) {
                    $this->addFlash($result);
                }
            }

            $return = $controllerResult->getReturn();

            if ($return instanceof Response) {
                $event->setResponse($return);
            }

            $event->setControllerResult($return);
        }
    }

    /**
     * @internal
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $this->headers = [];
    }

    /**
     * @internal
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        $event->getResponse()->headers->add($this->headers);
    }

    private function addFlash(FlashMessage $flashMessage): void
    {
        $this->session->getFlashBag()->add($flashMessage->type(), $this->translator->trans(
            $flashMessage->message(),
            $flashMessage->parameters(),
            $flashMessage->domain() ?? 'flashes',
            $flashMessage->locale()
        ));
    }
}
