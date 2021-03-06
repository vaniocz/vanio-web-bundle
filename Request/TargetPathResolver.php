<?php
namespace Vanio\WebBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Vanio\Stdlib\Strings;

class TargetPathResolver
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
            'referer_fallback' => '/',
            'target_path_parameter' => '_target_path',
            'target_path_fallback' => '/',
        ];
    }

    public function resolveReferer(Request $request, string $fallbackPath = null): string
    {
        $targetPath = $request->query->get($this->options['referer_parameter'], $request->headers->get('referer', ''));

        return $this->resolvePath($request, $targetPath, $fallbackPath ?? $this->options['referer_fallback'], false);
    }

    public function resolveTargetPath(Request $request, string $fallbackPath = null): string
    {
        $targetPath = $request->query->get($this->options['target_path_parameter'], '');

        return $this->resolvePath($request, $targetPath, $fallbackPath ?? $this->options['target_path_fallback'], true);
    }

    private function resolvePath(Request $request, string $targetPath, string $fallbackPath, bool $allowRefresh): string
    {
        $absoluteBaseUrl = $request->getSchemeAndHttpHost() . $request->getBaseUrl();
        $path = Strings::startsWith($targetPath, $absoluteBaseUrl)
            ? substr($targetPath, strlen($absoluteBaseUrl))
            : $targetPath;
        $targetPath = $absoluteBaseUrl . $path;

        if (
            Strings::startsWith($path, '/')
            && ($allowRefresh || rawurldecode($request->getUri()) !== rawurldecode($targetPath))
        ) {
            $path = parse_url($path, PHP_URL_PATH);

            if ($path !== false) {
                try {
                    $this->urlMatcher->match($path);

                    return $targetPath;
                } catch (ExceptionInterface $e) {}
            }
        }

        return $this->httpUtils->generateUri($request, $fallbackPath);
    }
}
