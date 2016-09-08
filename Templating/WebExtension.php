<?php
namespace Vanio\WebBundle\Templating;

use Html2Text\Html2Text;

class WebExtension extends \Twig_Extension
{
    /**
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions(): array
    {
        return [new \Twig_SimpleFunction('class_name', [$this, 'className'])];
    }

    /**
     * @return \Twig_SimpleFilter[]
     */
    public function getFilters(): array
    {
        return [new \Twig_SimpleFilter('html_to_text', [$this, 'htmlToText'])];
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

    public function className(array $classes): string
    {
        return implode(' ', array_keys(array_filter($classes)));
    }

    public function htmlToText(string $html, array $options = []): string
    {
        return (new Html2Text($html, $options))->getText();
    }

    public function isInstanceOf($value, string $class): bool
    {
        return is_a($value, $class, true);
    }
}
