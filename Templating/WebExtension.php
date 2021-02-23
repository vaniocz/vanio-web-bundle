<?php
namespace Vanio\WebBundle\Templating;

use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Persistence\ManagerRegistry;
use Html2Text\Html2Text;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\EscaperExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;
use Vanio\Stdlib\Arrays;
use Vanio\Stdlib\Strings;
use Vanio\Stdlib\Uri;
use Vanio\WebBundle\Request\RouteHierarchyResolver;
use Vanio\WebBundle\Request\TargetPathResolver;
use Vanio\WebBundle\Serializer\Serializer;

class WebExtension extends AbstractExtension implements GlobalsInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var TwigFormRendererEngine */
    private $twigFormRendererEngine;

    /** @var RequestStack */
    private $requestStack;

    /** @var TargetPathResolver */
    private $targetPathResolver;

    /** @var RouteHierarchyResolver */
    private $routeHierarchyResolver;

    /** @var ResponseContext */
    private $responseContext;

    /** @var Serializer */
    private $serializer;

    /** @var ManagerRegistry */
    private $doctrine;

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

    /** @var array */
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
        TargetPathResolver $targetPathResolver,
        RouteHierarchyResolver $routeHierarchyResolver,
        ResponseContext $responseContext,
        Serializer $serializer,
        ManagerRegistry $doctrine,
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
        $this->targetPathResolver = $targetPathResolver;
        $this->routeHierarchyResolver = $routeHierarchyResolver;
        $this->responseContext = $responseContext;
        $this->serializer = $serializer;
        $this->doctrine = $doctrine;
        $this->cacheManager = $cacheManager;
        $this->webRoot = $webRoot;
        $this->imageDimensionsCache = new FilesystemCache($imageDimensionsCacheDirectory);
        $this->googleMapsApiKey = $googleMapsApiKey;
        $this->supportedLocales = $supportedLocales;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sum', [$this, 'sum']),
            new TwigFunction('class_name', [$this, 'className']),
            new TwigFunction('attributes', [$this, 'attributes'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('web_path', [$this, 'webPath']),
            new TwigFunction('require_js', [$this, 'requireJs']),
            new TwigFunction('require_js_once', [$this, 'requireJsOnce']),
            new TwigFunction('render_js', [$this, 'renderJs'], ['is_safe' => ['html']]),
            new TwigFunction('is_translated', [$this, 'isTranslated']),
            new TwigFunction('route_exists', [$this, 'routeExists']),
            new TwigFunction('file_exists', [$this, 'fileExists']),
            new TwigFunction('form_default_theme', [$this, 'formDefaultTheme']),
            new TwigFunction('form_block', null, [
                'node_class' => SearchAndRenderBlockNode::class,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('form_widget_attributes', [$this, 'formWidgetAttributes'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('form_error_messages', [$this, 'formErrorMessages']),
            new TwigFunction('referer', [$this, 'referer']),
            new TwigFunction('is_current', [$this, 'isCurrent']),
            new TwigFunction('breadcrumbs', [$this, 'breadcrumbs']),
            new TwigFunction('image_dimensions', [$this, 'imageDimensions']),
            new TwigFunction('imagine_dimensions', [$this, 'imagineDimensions']),
            new TwigFunction('response_status', [$this, 'responseStatus']),
            new TwigFunction('entity', [$this, 'entity']),
            new TwigFunction('entities', [$this, 'entities']),
            new TwigFunction('form_path', [$this, 'formPath']),
            new TwigFunction('source_path', [$this, 'sourcePath'], ['needs_environment' => true]),
        ];
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('trans', [$this, 'trans']),
            new TwigFilter('without', [$this, 'without']),
            new TwigFilter('without_keys', [$this, 'withoutKeys']),
            new TwigFilter('without_empty', [$this, 'withoutEmpty']),
            new TwigFilter('intersect', 'array_intersect'),
            new TwigFilter('group_by', [$this, 'groupBy']),
            new TwigFilter('regexp_replace', [$this, 'regexpReplace']),
            new TwigFilter('regexp_split', [$this, 'regexpSplit']),
            new TwigFilter('html_to_text', [$this, 'htmlToText']),
            new TwigFilter('evaluate', [$this, 'evaluate'], ['needs_environment' => true]),
            new TwigFilter('basename', [$this, 'basename']),
            new TwigFilter('filename', [$this, 'filename']),
            new TwigFilter('extension', [$this, 'extension']),
            new TwigFilter('human_file_size', [$this, 'humanFileSize']),
            new TwigFilter('serialize', [$this, 'serialize']),
            new TwigFilter('with_appended_query', [$this, 'withAppendedQuery']),
        ];
    }

    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('instance of', [$this, 'isInstanceOf']),
            new TwigTest('integer', 'is_int'),
            new TwigTest('float', 'is_float'),
        ];
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

    public function sum(iterable $values): float
    {
        return array_sum(is_array($values) ? $values : iterator_to_array($values, false));
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

    public function attributes(Environment $environment, array $attributes): string
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

    public function fileExists(string $filename): bool
    {
        return is_file($filename);
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
        Environment $environment,
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
        return $this->targetPathResolver->resolveReferer($this->requestStack->getCurrentRequest(), $fallbackPath);
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
            $normalizedPath = Strings::startsWith($path, '//')
                ? sprintf('%s:%s', $this->requestStack->getMasterRequest()->getScheme(), $path)
                : $path;
            @$imageDimensions[$path] = getimagesize($normalizedPath) ?: [0, 0];
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

        $path = parse_url($path, PHP_URL_PATH);

        return $this->imageDimensions(
            $this->cacheManager->getBrowserPath($path, $filter, $runtimeConfig, $resolver),
            $this->cacheManager->generateUrl($path, $filter, $runtimeConfig, $resolver)
        );
    }

    public function formErrorMessages(FormView $form): array
    {
        $errorMessages = [];

        /** @var FormError $error */
        foreach ($form->vars['errors'] ?? [] as $error) {
            $errorMessages[] = $error->getMessage();
        }

        foreach ($form as $name => $child) {
            if ($childErrorMessages = $this->formErrorMessages($child)) {
                $errorMessages[$name] = $childErrorMessages;
            }
        }

        return $errorMessages;
    }

    public function responseStatus(int $statusCode, string $statusText = null)
    {
        $this->responseContext->setStatus($statusCode, $statusText);
    }

    /**
     * @param string $class
     * @param mixed $criteria
     * @return object|null
     */
    public function entity(string $class, $criteria)
    {
        $entityRepository = $this->doctrine->getManagerForClass($class)->getRepository($class);

        return is_array($criteria) ? $entityRepository->findOneBy($criteria) : $entityRepository->find($criteria);
    }

    /**
     * @param string $class
     * @param array $criteria
     * @return object[]
     */
    public function entities(string $class, array $criteria): array
    {
        return $this->doctrine->getManagerForClass($class)->getRepository($class)->findBy($id);
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

    /**
     * @param string $string
     * @param string $pattern
     * @return string[]
     */
    public function regexpSplit(string $string, string $pattern): array
    {
        return preg_split($pattern, $string);
    }

    public function htmlToText(string $html, array $options = []): string
    {
        return (new Html2Text($html, $options))->getText();
    }

    public function evaluate(Environment $environment, string $template, array $context = []): string
    {
        if (!Strings::contains($template, ['{{', '{%', '{#'])) {
            return $template;
        }

        /** @var EscaperExtension $escaper */
        $escaper = $environment->getExtension(EscaperExtension::class);
        $defaultStrategy = $escaper->getDefaultStrategy($template);
        $escaper->setDefaultStrategy(false);
        $content = $environment->createTemplate($template)->render($context);
        $escaper->setDefaultStrategy($defaultStrategy);

        return $content;
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

    public function serialize($data, string $format = 'json'): string
    {
        return $this->serializer->serialize($data, $format);
    }

    /**
     * @param string $url
     * @param mixed[] $query
     * @return string
     */
    public function withAppendedQuery(string $url, array $query): string
    {
        $url .= Strings::contains($url, '?') ? '&' : '?';
        $url .= is_array($query) ? http_build_query($query) : $query;

        return $url;
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

    public function formPath(FormView $view): string
    {
        $root = $view;

        while ($root->parent) {
            $root = $root->parent;
        }

        return Uri::encodeQuery($this->resolveFormData($root, $view), true);
    }

    public function sourcePath(Environment $environment, string $name): string
    {
        return $environment->getLoader()->getSourceContext($name)->getPath();
    }

    private function resolveFormData(FormView $view, FormView $currentView, array $data = []): array
    {
        if (!empty($view->vars['compound'])) {
            foreach ($view->children as $child) {
                $data = $this->resolveFormData($child, $currentView, $data);
            }

            return $data;
        }

        $isCurrent = $view === $currentView;

        if (
            $isCurrent
            || (!empty($view->vars['submitted']) && ($view->vars['isSubmittedWithEmptyData'] ?? true) === false)
        ) {
            if (
                in_array('checkbox', $view->vars['block_prefixes'])
                && ((empty($view->vars['checked']) && !$isCurrent) || (!empty($view->vars['checked']) && $isCurrent))
            ) {
                return $data;
            }

            $viewData = &Arrays::getReference($data, $this->resolveFormPath($view->vars['full_name']));

            if (Strings::endsWith($view->vars['full_name'], '[]')) {
                $viewData[] = $view->vars['value'];
            } else {
                $viewData = $view->vars['value'];
            }
        }

        return $data;
    }

    private function resolveFormPath(string $fullName): array
    {
        $path = str_replace('[]', '', $fullName);
        $path = str_replace(']', '', $path);

        return explode('[', $path);
    }
}
