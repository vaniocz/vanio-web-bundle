<?php
namespace Vanio\WebBundle\Templating;

use Symfony\Bridge\Twig\Form\TwigRendererEngine;

class TwigFormRendererEngine extends TwigRendererEngine
{
    public function getDefaultThemes(): array
    {
        return $this->defaultThemes;
    }

    public function setDefaultThemes(array $defaultThemes)
    {
        $this->defaultThemes = $defaultThemes;
    }

    public function addDefaultTheme(string $defaultTheme)
    {
        $this->defaultThemes[] = $defaultTheme;
    }
}
