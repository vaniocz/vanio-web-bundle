<?php
namespace Vanio\WebBundle\Templating;

use Html2Text\Html2Text;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Vanio\Stdlib\Strings;
use Vanio\WebBundle\Request\RefererResolver;
use Vanio\WebBundle\Request\RouteHierarchyResolver;

class WebExtension extends \Twig_Extension
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

    public function __construct(
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        TwigFormRendererEngine $twigFormRendererEngine,
        RequestStack $requestStack,
        RefererResolver $refererResolver,
        RouteHierarchyResolver $routeHierarchyResolver
    ) {
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->twigFormRendererEngine = $twigFormRendererEngine;
        $this->requestStack = $requestStack;
        $this->refererResolver = $refererResolver;
        $this->routeHierarchyResolver = $routeHierarchyResolver;
    }

    /**
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('class_name', [$this, 'renderClassName']),
            new \Twig_SimpleFunction('attributes', [$this, 'renderAttributes'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new \Twig_SimpleFunction('is_translated', [$this, 'isTranslated']),
            new \Twig_SimpleFunction('route_exists', [$this, 'routeExists']),
            new \Twig_SimpleFunction('form_default_theme', [$this, 'formDefaultTheme']),
            new \Twig_SimpleFunction('referer', [$this, 'resolveReferer']),
            new \Twig_SimpleFunction('is_current', [$this, 'isCurrent']),
            new \Twig_SimpleFunction('breadcrumbs', [$this, 'resolveBreadcrumbs']),
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
            new \Twig_SimpleFilter('regexp_replace', [$this, 'regexpReplace']),
            new \Twig_SimpleFilter('html_to_text', [$this, 'htmlToText']),
            new \Twig_SimpleFilter('evaluate', [$this, 'evaluate'], ['needs_environment' => true]),
            new \Twig_SimpleFilter('width', [$this, 'width']),
            new \Twig_SimpleFilter('height', [$this, 'height']),
        ];
    }

    /**
     * @return \Twig_SimpleFunction[]
     */
    public function getTests(): array
    {
        return [new \Twig_SimpleTest('instance of', [$this, 'isInstanceOf'])];
    }

    public function getName(): string
    {
        return 'vanio_web_extension';
    }

    public function renderClassName(array $classes): string
    {
        return implode(' ', array_keys(array_filter($classes)));
    }

    public function renderAttributes(\Twig_Environment $environment, array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            if ($name === 'class' && is_array($value)) {
                $value = $this->renderClassName($value);
            } elseif ($value === true) {
                $value = $name;
            }

            if ($value !== false) {
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, $environment->getCharset());
                $html .= sprintf(' %s="%s"', $name, $value);
            }
        }

        return ltrim($html);
    }

    public function isTranslated(string $id, string $domain = null, string $locale = null): bool
    {
        if ($domain === null) {
            $domain = 'messages';
        }

        return $this->translator instanceof TranslatorBagInterface
            && $this->translator->getCatalogue($locale)->has($id, $domain)
            && $this->translator->getCatalogue($locale)->get($id, $domain) !== false;
    }

    public function routeExists(string $name): bool
    {
        try {
            $this->urlGenerator->generate($name);
        } catch (RouteNotFoundException $e) {
            return false;
        } catch (ExceptionInterface $e) {}

        return true;
    }

    public function formDefaultTheme(string $theme): void
    {
        $defaultThemes = $this->twigFormRendererEngine->getDefaultThemes();
        $recursiveFormLayoutTheme = '@VanioWeb/recursiveFormLabelLayout.html.twig';
        $recursiveFormLayoutThemeKey = array_search($recursiveFormLayoutTheme, $defaultThemes);

        if ($recursiveFormLayoutThemeKey !== false) {
            unset($defaultThemes[$recursiveFormLayoutThemeKey]);
            $defaultThemes = array_values($defaultThemes);
        }

        $defaultThemes[] = $theme;

        if ($recursiveFormLayoutThemeKey !== false) {
            $defaultThemes[] = '@VanioWeb/recursiveFormLabelLayout.html.twig';
        }

        $this->twigFormRendererEngine->setDefaultThemes($defaultThemes);
    }

    public function resolveReferer(string $fallbackPath = null): string
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

        return isset($hierarchy[$route]);
    }

    /**
     * @return string[]
     */
    public function resolveBreadcrumbs(): array
    {
        return $this->routeHierarchyResolver->resolveRouteHierarchy($this->requestStack->getCurrentRequest());
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

    public function width(string $path): int
    {
        return $this->resolveImageDimensions($path)[0];
    }

    public function height(string $path): int
    {
        return $this->resolveImageDimensions($path)[1];
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

    private function resolveImageDimensions(string $path): array
    {
        static $imageDimensions = [];

        if (!isset($imageDimensions[$path])) {
            $imageDimensions[$path] = getimagesize($path);
        }

        return $imageDimensions[$path];
    }
}
