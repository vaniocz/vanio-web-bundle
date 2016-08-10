<?php
namespace Vanio\WebBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Vanio\Stdlib\Strings;

class RefererResolver
{
    /** @var HttpUtils */
    private $httpUtils;

    /** @var UrlMatcherInterface */
    private $urlMatcher;

    /** @var string */
    private $fallbackPath;

    public function __construct(HttpUtils $httpUtils, UrlMatcherInterface $urlMatcher, string $fallbackPath = '/')
    {
        $this->httpUtils = $httpUtils;
        $this->urlMatcher = $urlMatcher;
        $this->fallbackPath = $fallbackPath;
    }

    public function resolveReferer(Request $request, string $fallbackPath = null): string
    {
        $referer = $request->headers->get('referer');
        $absoluteBaseUrl = $request->getSchemeAndHttpHost() . $request->getBaseUrl();

        if ($referer && $referer !== $request->getUri() && Strings::startsWith($referer, $absoluteBaseUrl)) {
            $path = parse_url(substr($referer, strlen($absoluteBaseUrl)), PHP_URL_PATH);

            if ($path !== false) {
                try {
                    $this->urlMatcher->match($path);

                    return $referer;
                } catch (ExceptionInterface $e) {}
            }
        }

        return $this->httpUtils->generateUri($request, $fallbackPath ?? $this->fallbackPath);
    }
}
