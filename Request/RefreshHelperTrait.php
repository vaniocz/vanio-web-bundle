<?php
namespace Vanio\WebBundle\Request;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait RefreshHelperTrait
{
    /** @var ContainerInterface|null */
    protected $container;

    /** @var RequestStack|null */
    protected $requestStack;

    /** @var UrlGeneratorInterface|null */
    protected $urlGenerator;

    /**
     * @param int $status
     * @param array $headers
     * @return RedirectResponse
     * @throws \LogicException
     */
    protected function refresh($status = Response::HTTP_FOUND, array $headers = []): RedirectResponse
    {
        if (!$this->container && (!$this->requestStack || !$this->urlGenerator)) {
            throw new \LogicException(sprintf(
                'Unable to refresh. You must set both "requestStack" and "urlGenerator" properties or make "%s" class container-aware.',
                __CLASS__
            ));
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
