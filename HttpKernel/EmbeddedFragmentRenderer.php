<?php
namespace Vanio\WebBundle\HttpKernel;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EmbeddedFragmentRenderer extends InlineFragmentRenderer
{
    /** @var HttpKernelInterface */
    private $kernel;

    const REDIRECT_REFRESH = 'refresh';
    const REDIRECT_FOLLOW = 'follow';
    const REDIRECT_FORWARD = 'forward';
    const REDIRECT_IGNORE = 'ignore';

    public function __construct(HttpKernelInterface $kernel, EventDispatcherInterface $dispatcher = null)
    {
        parent::__construct($kernel, $dispatcher);
        $this->kernel = $kernel;
    }

    /**
     * @param ControllerReference|string $uri
     * @param Request $request
     * @param array $options
     * @return Response
     */
    public function render($uri, Request $request, array $options = []): Response
    {
        $options += ['redirect' => self::REDIRECT_REFRESH];
        $response = parent::render($uri, $request, $options);

        if ($response->isRedirect()) {
            $location = $response->headers->get('Location');

            switch ($options['redirect']) {
                case self::REDIRECT_REFRESH:
                case self::REDIRECT_FOLLOW:
                    if ($this->kernel instanceof TerminableInterface) {
                        $this->kernel->terminate($request, $response);
                    }

                    $targetUrl = $options['redirect'] === self::REDIRECT_REFRESH ? $request->getUri() : $location;
                    (new RedirectResponse($targetUrl))->send();
                    exit;
                case self::REDIRECT_FORWARD:
                    do {
                        $response = parent::render($location, new Request, $options);
                        $location = $response->headers->get('Location');
                    } while ($response->isRedirect());
                case self::REDIRECT_IGNORE:
                    return new Response('');
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
