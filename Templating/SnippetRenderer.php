<?php
namespace Vanio\WebBundle\Templating;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SnippetRenderer implements EventSubscriberInterface
{
    /** @var ContainerInterface */
    public $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => ['onKernelView', 10]];
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $parameters = $event->getControllerResult();

        /** @var Template $template */
        if (!$template = $request->attributes->get('_template')) {
            return;
        } elseif ($snippets = (array) $request->query->get('_snippet')) {
            $request->query->remove('_snippet');
        } else {
            return;
        }

        if (is_callable([$template, 'getEngine']) && $template->getEngine() !== 'twig') {
            return;
        }

        $template = $template->getTemplate();
        $this->resolveTemplateVariables($request, $parameters);

        if (!is_array($parameters)) {
            return;
        }

        /** @var \Twig_Template $template */
        $template = $this->twig()->loadTemplate($template);
        $content = '';

        foreach ($snippets as $snippet) {
            /** @noinspection PhpInternalEntityUsedInspection */
            $content .= $template->renderBlock("{$snippet}_snippet", $this->twig()->mergeGlobals($parameters));
        }

        $event->setResponse(new Response($content));
    }

    private function resolveTemplateVariables(Request $request, array &$parameters = null)
    {
        if ($parameters) {
            return;
        } elseif (!$variables = $request->attributes->get('_template_vars')) {
            if (!$variables = $request->attributes->get('_template_default_vars')) {
                return;
            }
        }

        foreach ($variables as $variable) {
            $parameters[$variable] = $request->attributes->get($variable);
        }
    }

    private function twig(): \Twig_Environment
    {
        return $this->container->get('twig');
    }
}
