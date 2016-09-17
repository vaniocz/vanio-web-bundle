<?php
namespace Vanio\WebBundle\Templating;

use Html2Text\Html2Text;
use Symfony\Component\HttpFoundation\RequestStack;
use Vanio\WebBundle\Request\RefererResolver;

class WebExtension extends \Twig_Extension
{
    /** @var RefererResolver */
    private $refererResolver;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(RefererResolver $refererResolver, RequestStack $requestStack)
    {
        $this->refererResolver = $refererResolver;
        $this->requestStack = $requestStack;
    }

    /**
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('class_name', [$this, 'resolveClassName']),
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
