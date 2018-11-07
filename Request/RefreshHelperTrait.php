<?php
namespace Vanio\WebBundle\Request;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait RefreshHelperTrait
{
    /** @var ContainerInterface|PsrContainerInterface|null */
    protected $container;

    /** @var RequestStack|null */
    protected $requestStack;

    /** @var UrlGeneratorInterface|null */
    protected $urlGenerator;

    /**
     * @param int $status
     * @param mixed[] $headers
     * @return RedirectResponse
     */
    protected function refresh(int $status = Response::HTTP_FOUND, array $headers = []): RedirectResponse
    {
        if (!$this->container && (!$this->requestStack || !$this->urlGenerator)) {
            throw new \LogicException('Unable to refresh. You must set both "requestStack" and "urlGenerator", subscribe for "request_stack" and "router" services or make your class container-aware.');
        }

        if (!$this->requestStack) {
            $this->requestStack = $this->container->get('request_stack');
        }

        if (!$this->urlGenerator) {
            $this->urlGenerator = $this->container->get('router');
        }

        $request = $this->requestStack->getCurrentRequest();
        $url = $this->urlGenerator->generate($request->get('_route'), $request->get('_route_params'));

        return new RedirectResponse($url, $status, $headers);
    }
}
