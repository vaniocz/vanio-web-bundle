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

    /** @var array */
    private $options;

    public function __construct(HttpUtils $httpUtils, UrlMatcherInterface $urlMatcher, array $options = [])
    {
        $this->httpUtils = $httpUtils;
        $this->urlMatcher = $urlMatcher;
        $this->options = $options + [
            'referer_parameter' => '_referer',
            'referer_fallback_path' => '/',
        ];
    }

    public function resolveReferer(Request $request, string $fallbackPath = null): string
    {
        $referer = $request->query->get($this->options['referer_parameter'], $request->headers->get('referer', ''));
        $absoluteBaseUrl = $request->getSchemeAndHttpHost() . $request->getBaseUrl();
        $path = Strings::startsWith($referer, $absoluteBaseUrl)
            ? substr($referer, strlen($absoluteBaseUrl))
            : $referer;
        $referer = $absoluteBaseUrl . $path;

        if ($request->getUri() !== $referer && Strings::startsWith($path, '/')) {
            $path = parse_url($path, PHP_URL_PATH);

            if ($path !== false) {
                try {
                    $this->urlMatcher->match($path);

                    return $referer;
                } catch (ExceptionInterface $e) {}
            }
        }

        return $this->httpUtils->generateUri($request, $fallbackPath ?? $this->options['referer_fallback_path']);
    }
}
