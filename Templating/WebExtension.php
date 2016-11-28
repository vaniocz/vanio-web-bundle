<?php
namespace Vanio\WebBundle\Templating;

use Html2Text\Html2Text;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Vanio\Stdlib\Strings;
use Vanio\Stdlib\Uri;
use Vanio\WebBundle\Request\RefererResolver;

class WebExtension extends \Twig_Extension
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var RequestStack */
    private $requestStack;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var RefererResolver */
    private $refererResolver;

    public function __construct(
        TranslatorInterface $translator,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        RefererResolver $refererResolver
    ) {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->refererResolver = $refererResolver;
    }

    /**
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('class_name', [$this, 'resolveClassName']),
            new \Twig_SimpleFunction('is_current', [$this, 'isCurrent']),
            new \Twig_SimpleFunction('is_translated', [$this, 'isTranslated']),
            new \Twig_SimpleFunction('referer', [$this, 'resolveReferer']),
        ];
    }

    /**
     * @return \Twig_SimpleFilter[]
     */
    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter('filter', [$this, 'filter']),
            new \Twig_SimpleFilter('without', [$this, 'without']),
            new \Twig_SimpleFilter('html_to_text', [$this, 'convertHtmlToText']),
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

    public function resolveClassName(array $classes): string
    {
        return implode(' ', array_keys(array_filter($classes)));
    }

    public function isTranslated(string $id, string $domain = 'messages', string $locale = null): bool
    {
        return $this->translator instanceof TranslatorBagInterface
            ? $this->translator->getCatalogue($locale)->has($id, $domain)
            : false;
    }

    public function isCurrent(string $route): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->attributes->get('_route') === $route) {
            return true;
        }

        try {
            $attributes = $request->attributes->all();
            $path = $this->urlGenerator->generate($route, $attributes, UrlGeneratorInterface::ABSOLUTE_PATH);
            $path = (new Uri($path))->path();
        } catch (MissingMandatoryParametersException $e) {
            return false;
        } catch (InvalidParameterException $e) {
            return false;
        }

        return $request->getPathInfo() === $path
            || $path !== '/'
            && Strings::startsWith($request->getPathInfo(), Strings::endsWith($path, '/') ? $path : $path . '/');
    }

    public function resolveReferer(string $fallbackPath = null): string
    {
        return $this->refererResolver->resolveReferer($this->requestStack->getCurrentRequest(), $fallbackPath);
    }

    public function filter(array $array): array
    {
        return array_filter($array, [$this, 'isNotEmpty']);
    }

    public function without(array $array, $keys): array
    {
        return array_diff_key($array, array_flip((array) $keys));
    }

    public function convertHtmlToText(string $html, array $options = []): string
    {
        return (new Html2Text($html, $options))->getText();
    }

    public function isInstanceOf($value, string $class): bool
    {
        return is_a($value, $class, true);
    }

    public function isNotEmpty($value): bool
    {
        if ($value instanceof \Countable) {
            return count($value) > 0;
        }

        return $value !== '' && $value !== false && $value !== null && $value !== [];
    }
}
