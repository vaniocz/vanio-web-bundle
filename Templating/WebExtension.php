<?php
namespace Vanio\WebBundle\Templating;

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
        $className = '';

        foreach ($classes as $class => $enabled) {
            if ($enabled) {
                $className .= (is_int($class) ? $enabled : $class) . ' ';
            }
        }

        return trim($className);
    }

    public function isInstanceOf($value, string $class): bool
    {
        return is_a($value, $class, true);
    }
}
