<?php
namespace Vanio\WebBundle\Request;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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

    public function __construct(TranslatorInterface $translator, SessionInterface $session)
    {
        $this->translator = $translator;
        $this->session = $session;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => ['onKernelView', PHP_INT_MAX]];
    }

    /**
     * @internal
     */
    public function onKernelView(GetResponseForControllerResultEvent $event): void
    {
        $controllerResult = $event->getControllerResult();

        if ($controllerResult instanceof \Generator) {
            foreach ($controllerResult as $result) {
                if ($result instanceof FlashMessage) {
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
