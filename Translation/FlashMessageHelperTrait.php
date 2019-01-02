<?php
namespace Vanio\WebBundle\Translation;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

trait FlashMessageHelperTrait
{
    /** @var ContainerInterface|PsrContainerInterface|null */
    protected $container;

    /** @var SessionInterface|null */
    protected $session;

    /**
     * @param string $type
     * @param mixed $message
     */
    protected function addFlash(string $type, $message): void
    {
        if (!$this->session && !$this->container) {
            throw new \LogicException('Unable to add flash message. You must set "session" property manually, subscribe for "session" service or make your class container-aware.');
        } elseif (!$this->session) {
            $this->session = $this->container->get('session');
        }

        $this->session->getFlashBag()->add($type, $message);
    }
}
