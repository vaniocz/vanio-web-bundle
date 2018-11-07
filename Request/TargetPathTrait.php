<?php
namespace Vanio\WebBundle\Request;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

trait TargetPathTrait
{
    /** @var ContainerInterface|PsrContainerInterface|null */
    protected $container;

    /** @var TargetPathResolver|null */
    protected $targetPathResolver;

    /** @var RequestStack|null */
    protected $requestStack;

    /**
     * @param null|string $fallbackPath
     * @param int $status
     * @param mixed[] $headers
     * @return RedirectResponse
     */
    protected function redirectToTargetPath(
        ?string $fallbackPath = null,
        int $status = Response::HTTP_FOUND,
        array $headers = []
    ): RedirectResponse {
        if (!$this->container && (!$this->targetPathResolver || !$this->requestStack)) {
            throw new \LogicException('Unable to refresh. You must set both "targetPathResolver" and "requestStack", subscribe for "vanio_web.request.target_path_resolver" and "request_stack" services or make your class container-aware.');
        }

        if (!$this->targetPathResolver) {
            $this->targetPathResolver = $this->container->get('vanio_web.request.target_path_resolver');
        }

        if (!$this->requestStack) {
            $this->requestStack = $this->container->get('request_stack');
        }

        $referer = $this->targetPathResolver->resolveTargetPath(
            $this->requestStack->getCurrentRequest(),
            $fallbackPath
        );

        return new RedirectResponse($referer, $status, $headers);
    }
}
