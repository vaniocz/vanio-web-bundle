<?php
namespace Vanio\WebBundle\Templating;

use Doctrine\Common\Cache\FilesystemCache;
use Html2Text\Html2Text;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Vanio\Stdlib\Strings;
use Vanio\WebBundle\Request\RefererResolver;
use Vanio\WebBundle\Request\RouteHierarchyResolver;

class WebExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var TwigFormRendererEngine */
    private $twigFormRendererEngine;

    /** @var RequestStack */
    private $requestStack;

    /** @var RefererResolver */
    private $refererResolver;

    /** @var RouteHierarchyResolver */
    private $routeHierarchyResolver;

    /** @var CacheManager|null */
    private $cacheManager;

    /** @var FilesystemCache */
    private $imageDimensionsCache;

    /** @var string */
    private $webRoot;

    /** @var string|null */
    private $googleMapsApiKey;

    /** @var string[] */
    private $supportedLocales;

    /** @var array  */
    private $javaScripts = [];

    /** @var array  */
    private $requiredJavaScripts = [];

    /** @var string[] */
    private $themesToAppend = [
        '@VanioWeb/formStartLayout.html.twig',
        '@VanioWeb/formAttributesLayout.html.twig',
        '@VanioWeb/recursiveFormLabelLayout.html.twig',
        '@VanioWeb/collectionWidgetLayout.html.twig',
        '@VanioWeb/formChoiceWidgetLayout.html.twig',
    ];

    public function __construct(
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        TwigFormRendererEngine $twigFormRendererEngine,
        RequestStack $requestStack,
        RefererResolver $refererResolver,
        RouteHierarchyResolver $routeHierarchyResolver,
        CacheManager $cacheManager = null,
        string $webRoot,
        string $imageDimensionsCacheDirectory,
        string $googleMapsApiKey = null,
        array $supportedLocales = []
    ) {
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->twigFormRendererEngine = $twigFormRendererEngine;
        $this->requestStack = $requestStack;
        $this->refererResolver = $refererResolver;
        $this->routeHierarchyResolver = $routeHierarchyResolver;
        $this->cacheManager = $cacheManager;
        $this->webRoot = $webRoot;
        $this->imageDimensionsCache = new FilesystemCache($imageDimensionsCacheDirectory);
        $this->googleMapsApiKey = $googleMapsApiKey;
        $this->supportedLocales = $supportedLocales;
    }

    /**
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('class_name', [$this, 'className']),
            new \Twig_SimpleFunction('attributes', [$this, 'attributes'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new \Twig_SimpleFunction('web_path', [$this, 'webPath']),
            new \Twig_SimpleFunction('require_js', [$this, 'requireJs']),
            new \Twig_SimpleFunction('require_js_once', [$this, 'requireJsOnce']),
            new \Twig_SimpleFunction('render_js', [$this, 'renderJs'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('is_translated', [$this, 'isTranslated']),
            new \Twig_SimpleFunction('route_exists', [$this, 'routeExists']),
            new \Twig_SimpleFunction('form_default_theme', [$this, 'formDefaultTheme']),
            new \Twig_SimpleFunction('form_widget_attributes', [$this, 'formWidgetAttributes'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new \Twig_SimpleFunction('referer', [$this, 'referer']),
            new \Twig_SimpleFunction('is_current', [$this, 'isCurrent']),
            new \Twig_SimpleFunction('breadcrumbs', [$this, 'breadcrumbs']),
            new \Twig_SimpleFunction('image_dimensions', [$this, 'imageDimensions']),
            new \Twig_SimpleFunction('imagine_dimensions', [$this, 'imagineDimensions']),
        ];
    }

    /**
     * @return \Twig_SimpleFilter[]
     */
    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter('trans', [$this, 'trans']),
            new \Twig_SimpleFilter('without', [$this, 'without']),
            new \Twig_SimpleFilter('without_keys', [$this, 'withoutKeys']),
            new \Twig_SimpleFilter('without_empty', [$this, 'withoutEmpty']),
            new \Twig_SimpleFilter('intersect', 'array_intersect'),
            new \Twig_SimpleFilter('group_by', [$this, 'groupBy']),
            new \Twig_SimpleFilter('regexp_replace', [$this, 'regexpReplace']),
            new \Twig_SimpleFilter('html_to_text', [$this, 'htmlToText']),
            new \Twig_SimpleFilter('evaluate', [$this, 'evaluate'], ['needs_environment' => true]),
            new \Twig_SimpleFilter('basename', [$this, 'basename']),
            new \Twig_SimpleFilter('filename', [$this, 'filename']),
            new \Twig_SimpleFilter('extension', [$this, 'extension']),
            new \Twig_SimpleFilter('human_file_size', [$this, 'humanFileSize']),
        ];
    }

    /**
     * @return \Twig_SimpleFunction[]
     */
    public function getTests(): array
    {
        return [new \Twig_SimpleTest('instance of', [$this, 'isInstanceOf'])];
    }

    public function getGlobals(): array
    {
        return [
            'googleMapsApiKey' => $this->googleMapsApiKey,
            'supportedLocales' => $this->supportedLocales,
        ];
    }

    public function getName(): string
    {
        return 'vanio_web_extension';
    }

    /**
     * @param string|string[] $classes
     * @return string
     */
    public function className($classes): string
    {
        return is_array($classes)
            ? implode(' ', array_keys(array_filter($classes)))
            : $classes;
    }

    public function attributes(\Twig_Environment $environment, array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            if (is_array($value)) {
                if ($name === 'class') {
                    $value = $this->className($value);
                } elseif (Strings::startsWith($name, 'data-')) {
                    $value = json_encode($value);
                }
            } elseif ($value === true) {
                $value = Strings::startsWith($name, 'data-') ? 'true' : $name;
            }

            if ($value === null) {
                $html .= sprintf(' %s', $name);
            } elseif ($value !== false) {
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, $environment->getCharset());
                $html .= sprintf(' %s="%s"', $name, $value);
            }
        }

        return ltrim($html);
    }

    public function webPath(string $path): string
    {
        return $this->webRoot . $path;
    }

    public function requireJs(string $javaScript, string $name = null)
    {
        $this->javaScripts[] = Strings::startsWith(ltrim($javaScript), '<script')
            ? $javaScript
            : sprintf('<script src="%s"></script>', $javaScript);
        $this->requiredJavaScripts[$name === null ? $javaScript : $name] = true;
    }

    public function requireJsOnce(string $javaScript, string $name = null)
    {
        if (!isset($this->requiredJavaScripts[$name === null ? $javaScript : $name])) {
            $this->requireJs($javaScript, $name);
        }
    }

    public function renderJs(): string
    {
        $scripts = implode('', $this->javaScripts);
        $this->javaScripts = [];
        $this->requiredJavaScripts = [];

        return $scripts;
    }

    public function isTranslated(string $id = null, string $domain = null, string $locale = null, bool $includeFallback = true): bool
    {
        if ($domain === null) {
            $domain = 'messages';
        }

        $methodName = $includeFallback ? 'has' : 'defines';

        return $this->translator instanceof TranslatorBagInterface
            && $this->translator->getCatalogue($locale)->$methodName($id, $domain)
            && $this->translator->getCatalogue($locale)->get($id, $domain) !== false;
    }

    public function routeExists(string $name, array $parameters = []): bool
    {
        try {
            $this->urlGenerator->generate($name, $parameters);
        } catch (RouteNotFoundException $e) {
            return false;
        } catch (ExceptionInterface $e) {}

        return true;
    }

    public function formDefaultTheme(string $theme)
    {
        $defaultThemes = $this->twigFormRendererEngine->getDefaultThemes();
        $defaultThemes[] = $theme;
        $themesToAppend = array_intersect($defaultThemes, $this->themesToAppend);
        $defaultThemes = array_diff($defaultThemes, $themesToAppend);
        $defaultThemes = array_merge($defaultThemes, $themesToAppend);
        $this->twigFormRendererEngine->setDefaultThemes($defaultThemes);
    }

    public function formWidgetAttributes(
        \Twig_Environment $environment,
        FormView $formView,
        array $variables = []
    ): string {
        $variables += $formView->vars;
        $attributes = $variables['attr'] + $formView->vars['attr'];

        if (!empty($variables['id'])) {
            $attributes['id'] = $variables['id'];
        }

        foreach (['placeholder', 'title'] as $attribute) {
            if (!isset($attributes[$attribute])) {
                continue;
            }

            $translationDomain = $variables['translation_domain'] ?? null;
            $attributes[$attribute] = $this->translator->trans($attributes[$attribute], [], $translationDomain);
        }

        return $this->attributes($environment, $attributes);
    }

    public function referer(string $fallbackPath = null): string
    {
        return $this->refererResolver->resolveReferer($this->requestStack->getCurrentRequest(), $fallbackPath);
    }

    public function isCurrent(string $route): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->attributes->get('_route') === $route) {
            return true;
        }

        $hierarchy = $this->routeHierarchyResolver->resolveRouteHierarchy($this->requestStack->getCurrentRequest());
        array_shift($hierarchy);

        return isset($hierarchy[$route]);
    }

    /**
     * @return string[]
     */
    public function breadcrumbs(): array
    {
        return $this->routeHierarchyResolver->resolveRouteHierarchy($this->requestStack->getCurrentRequest());
    }

    /**
     * @param string $path
     * @param bool|string $permanentCacheKey
     * @return array
     */
    public function imageDimensions(string $path, $permanentCacheKey = false): array
    {
        static $imageDimensions = [];
        static $permanentImageDimensions;

        if ($permanentCacheKey !== false) {
            if ($permanentImageDimensions === null) {
                $permanentImageDimensions = $this->imageDimensionsCache->fetch('image_dimensions') ?: [];
            }

            if ($permanentCacheKey === true) {
                $permanentCacheKey = $path;
            }

            if (!isset($permanentImageDimensions[$permanentCacheKey])) {
                $permanentImageDimensions[$permanentCacheKey] = $this->imageDimensions($path);
                $this->imageDimensionsCache->save('image_dimensions', $permanentImageDimensions);
            }

            return $permanentImageDimensions[$permanentCacheKey];
        }

        if (!isset($imageDimensions[$path])) {
            $imageDimensions[$path] = getimagesize($path);
            list($imageDimensions[$path]['width'], $imageDimensions[$path]['height']) = $imageDimensions[$path];
        }

        return $imageDimensions[$path];
    }

    /**
     * @param string $path
     * @param string $filter
     * @param array $runtimeConfig
     * @param null $resolver
     * @return array
     * @throws \LogicException
     */
    public function imagineDimensions(string $path, string $filter, array $runtimeConfig = [], $resolver = null): array
    {
        if (!$this->cacheManager) {
            throw new \LogicException('LiipImagineBundle is not installed.');
        }

        return $this->imageDimensions(
            $this->cacheManager->getBrowserPath($path, $filter, $runtimeConfig, $resolver),
            $this->cacheManager->generateUrl($path, $filter, $runtimeConfig, $resolver)
        );
    }

    /**
     * @param string|string[] $messages
     * @param array $arguments
     * @param string|null $domain
     * @param string|null $locale
     * @return string|string[]
     */
    public function trans($messages, array $arguments = [], string $domain = null, string $locale = null)
    {
        if (is_array($messages)) {
            $translatedMessages = [];

            foreach ($messages as $message) {
                $translatedMessages[] = $this->trans($message, $arguments, $domain, $locale);
            }

            return $translatedMessages;
        }

        return $this->translator->trans($messages, $arguments, $domain, $locale);
    }

    /**
     * @param array $array
     * @param string|array $values
     * @return array
     */
    public function without(array $array, $values): array
    {
        return array_diff($array, (array) $values);
    }

    /**
     * @param array $array
     * @param string|array $keys
     * @return array
     */
    public function withoutKeys(array $array, $keys): array
    {
        return array_diff_key($array, array_flip((array) $keys));
    }

    public function withoutEmpty(array $array): array
    {
        return array_filter($array, [$this, 'isNotEmpty']);
    }

    public function groupBy(array $array, string ...$propertyPaths): array
    {
        $grouped = [];
        $propertyPath = array_shift($propertyPaths);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($array as $value) {
            $grouped[(string) $propertyAccessor->getValue($value, $propertyPath)][] = $value;
        }

        if ($propertyPaths) {
            foreach ($grouped as $key => $value) {
                $grouped[$key] = $this->groupBy($value, ...$propertyPaths);
            }
        }

        return $grouped;
    }

    /**
     * @param string $string
     * @param array|string $pattern
     * @param array|string|null $replacement
     * @return string
     */
    public function regexpReplace(string $string, $pattern, $replacement = null): string
    {
        if ($replacement === null) {
            return preg_replace(array_keys($pattern), $pattern, $string);
        }

        return preg_replace($pattern, $replacement, $string);
    }

    public function htmlToText(string $html, array $options = []): string
    {
        return (new Html2Text($html, $options))->getText();
    }

    public function evaluate(\Twig_Environment $environment, string $template, array $context = []): string
    {
        return Strings::contains($template, ['{{', '{%', '{#'])
            ? $environment->createTemplate($template)->render($context)
            : $template;
    }

    public function basename(string $path): string
    {
        return basename($path);
    }

    public function filename(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    public function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    public function humanFileSize(int $bytes, int $decimals = 0): string
    {
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f %s", $bytes / pow(1024, $factor), $units[(string) $factor] ?? '');
    }

    /**
     * @param mixed $value
     * @param string $class
     * @return bool
     */
    public function isInstanceOf($value, string $class): bool
    {
        return is_a($value, $class, true);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isNotEmpty($value): bool
    {
        if ($value instanceof \Countable) {
            return count($value) > 0;
        }

        return $value !== '' && $value !== false && $value !== null && $value !== [];
    }
}
