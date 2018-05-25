<?php
namespace Vanio\WebBundle\Request;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

trait TargetPathTrait
{
    /** @var ContainerInterface|null */
    protected $container;

    /** @var TargetPathResolver|null */
    protected $targetPathResolver;

    /** @var RequestStack|null */
    protected $requestStack;

    /**
     * @throws \LogicException
     */
    protected function redirectToTargetPath(
        string $fallbackPath = null,
        int $status = Response::HTTP_FOUND,
        array $headers = []
    ): RedirectResponse {
        if (!$this->container && (!$this->targetPathResolver || !$this->requestStack)) {
            throw new \LogicException(sprintf(
                'Unable to redirect to target path. You must set both "targetPathResolver" and "requestStack" properties or make "%s" class container-aware.',
                __CLASS__
            ));
        }

        if (!$this->targetPathResolver) {
            $this->targetPathResolver = $this->container->get('vanio_web.request.target_path_resolver');
        }

        if (!$this->requestStack) {
            $this->requestStack = $this->container->get('request_stack');
        }

        $referer = $this->targetPathResolver->resolveTargetPath($this->requestStack->getCurrentRequest(), $fallbackPath);

        return new RedirectResponse($referer, $status, $headers);
    }
}
