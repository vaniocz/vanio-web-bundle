<?php
namespace Vanio\WebBundle\Templating;

use Html2Text\Html2Text;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vanio\Stdlib\Strings;
use Vanio\Stdlib\Uri;
use Vanio\WebBundle\Request\RefererResolver;

class WebExtension extends \Twig_Extension
{
    /** @var RequestStack */
    private $requestStack;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var RefererResolver */
    private $refererResolver;

    public function __construct(
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        RefererResolver $refererResolver
    ) {
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
            new \Twig_SimpleFunction('referer', [$this, 'resolveReferer']),
        ];
    }

    /**
     * @return \Twig_SimpleFilter[]
     */
    public function getFilters(): array
    {
        return [new \Twig_SimpleFilter('html_to_text', [$this, 'convertHtmlToText'])];
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

    public function convertHtmlToText(string $html, array $options = []): string
    {
        return (new Html2Text($html, $options))->getText();
    }

    public function isInstanceOf($value, string $class): bool
    {
        return is_a($value, $class, true);
    }
}
