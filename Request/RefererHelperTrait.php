<?php
namespace Vanio\WebBundle\Request;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

trait RefererHelperTrait
{
    /** @var ContainerInterface|null */
    protected $container;

    /** @var RefererResolver|null */
    protected $refererResolver;

    /** @var RequestStack|null */
    protected $requestStack;

    /**
     * @throws \LogicException
     */
    protected function redirectToReferer(
        string $fallbackPath = null,
        int $status = Response::HTTP_FOUND,
        array $headers = []
    ): RedirectResponse {
        if (!$this->container && (!$this->refererResolver || !$this->requestStack)) {
            throw new \LogicException(sprintf(
                'Unable to redirect to referer. You must set both "refererResolver" and "requestStack" properties or make "%s" class container-aware.',
                __CLASS__
            ));
        }

        if (!$this->refererResolver) {
            $this->refererResolver = $this->container->get('vanio_web.request.referer_resolver');
        }

        if (!$this->requestStack) {
            $this->requestStack = $this->container->get('request_stack');
        }

        $referer = $this->refererResolver->resolveReferer($this->requestStack->getCurrentRequest(), $fallbackPath);

        return new RedirectResponse($referer, $status, $headers);
    }
}
