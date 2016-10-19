<?php
namespace Vanio\WebBundle\HttpKernel;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;

class EmbeddedFragmentRenderer extends InlineFragmentRenderer
{
    const REDIRECT_REFRESH = 'refresh';
    const REDIRECT_FOLLOW = 'follow';
    const REDIRECT_FORWARD = 'forward';

    public function render($uri, Request $request, array $options = []): Response
    {
        $options += ['redirect' => self::REDIRECT_REFRESH];
        $response = parent::render($uri, $request, $options);

        if ($response->isRedirect()) {
            $location = $response->headers->get('Location');

            switch ($options['redirect']) {
                case self::REDIRECT_REFRESH:
                case self::REDIRECT_FOLLOW:
                    $targetUrl = $options['redirect'] === self::REDIRECT_REFRESH ? $request->getUri() : $location;
                    (new RedirectResponse($targetUrl))->send();
                    exit;
                case self::REDIRECT_FORWARD:
                    return parent::render($location, new Request, $options);
            }
        }

        return $response;
    }

    public function getName(): string
    {
        return 'embedded';
    }

    /**
     * @param string|ControllerReference $uri
     * @param Request $request
     * @return Request
     */
    protected function createSubRequest($uri, Request $request): Request
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            return $request;
        }

        return parent::createSubRequest($uri, $request);
    }
}
