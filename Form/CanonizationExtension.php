<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanio\DomainBundle\UnexpectedResponse\UnexpectedResponseException;
use Vanio\Stdlib\Arrays;
use Vanio\Stdlib\Uri;

class CanonizationExtension extends AbstractTypeExtension implements EventSubscriberInterface
{
    /** @var \SplObjectStorage */
    private $submittedData;

    /** @var Request|null */
    private $currentRequest;

    /** @var mixed[] */
    private $headers = [];

    public function __construct()
    {
        $this->query = new \SplObjectStorage;
        $this->submittedData = new \SplObjectStorage;
        $this->canonicalData = new \SplObjectStorage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'], 512);

        if ($this->currentRequest && $options['canonize']) {
            $method = $this->currentRequest->getRealMethod();

            if (in_array($method, ['GET', 'PUT', 'POST', 'DELETE', 'PATCH'])) {
                $builder->setMethod($method);
            }
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$this->currentRequest || !$form->isRoot() || !$form->isSubmitted() || !$options['canonize']) {
            return;
        }

        $query = $this->currentRequest->query->all();
        $canonicalData = $this->canonizeForm($form, $view, $query);
        $canonicalQueryString = $this->resolveCanonicalQueryString($form, $view, $query + $canonicalData);
        $canonicalUrl = $url = $this->currentRequest->getBaseUrl() . $this->currentRequest->getPathInfo();

        if ($canonicalQueryString) {
            $canonicalUrl .= '?' . $canonicalQueryString;
        }

        $this->headers['Canonical-Url'] = $canonicalUrl;

        if ($this->currentRequest->isXmlHttpRequest()) {
            return;
        } elseif ($currentQueryString = $this->currentRequest->server->get('QUERY_STRING')) {
            $currentQueryString = Uri::encodeQuery(Uri::parseQuery($currentQueryString));

            if ($form->getName() !== '') {
                $pattern = sprintf('/(%s)=(?=&|$)/', preg_quote($view->vars['full_name']));

                if (!preg_match($pattern, $this->currentRequest->server->get('QUERY_STRING'))) {
                    $currentQueryString = $this->normalizeEncodedQueryStringEmptyParameter(
                        $currentQueryString,
                        $view->vars['full_name']
                    );
                }
            }
        }

        if ($this->currentRequest->getRealMethod() !== 'GET' || $currentQueryString !== $canonicalQueryString) {
            throw new UnexpectedResponseException(new RedirectResponse($canonicalUrl, 302, $this->headers));
        }
    }

    public function canonizeForm(FormInterface $form, FormView $view, array &$query, array $canonicalData = []): array
    {
        $path = $this->resolvePath($view->vars['full_name']);

        try {
            Arrays::unset($query, $path);
        } catch (\InvalidArgumentException $e) {}

        if (!$form->getConfig()->getCompound()) {
            if ($this->isEmptyData($form)) {
                return $canonicalData;
            }

            $value = &Arrays::getReference($canonicalData, $path);
            $formView = $view;

            if (substr($view->vars['full_name'], -2) === '[]') {
                $value[] = $this->submittedData[$form];
            } else {
                $value = $this->submittedData[$form];
            }

            do {
                $formView->vars['isSubmittedWithEmptyData'] = false;
            } while ($formView = $formView->parent);

            return $canonicalData;
        }

        foreach ($form as $name => $child) {
            $canonicalData = $this->canonizeForm($child, $view->children[$name], $query, $canonicalData);
        }

        return $canonicalData;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('canonize', false)
            ->setAllowedTypes('canonize', 'bool');
    }

    public function getExtendedType(): string
    {
        return FormType::class;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * @internal
     */
    public function onPreSubmit(FormEvent $event): void
    {
        $form = $event->getForm();

        if (!$form->getRoot()->getConfig()->getOption('canonize')) {
            return;
        } elseif ($form->getConfig()->getCompound()) {
            if ($form->isRoot() && $event->getData() === '') {
                $event->setData([]);
            }
        } else {
            $this->submittedData[$form] = (string) $event->getData();
        }
    }

    /**
     * @internal
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $this->currentRequest = $event->getRequest();
        $this->headers = [];
    }

    /**
     * @internal
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        $event->getResponse()->headers->add($this->headers);
    }

    private function resolvePath(string $formName): array
    {
        $path = str_replace('[]', '', $formName);
        $path = str_replace(']', '', $path);

        return explode('[', $path);
    }

    private function resolveCanonicalQueryString(FormInterface $form, FormView $view, array $query): string
    {
        $nameMapping = NameMappingExtension::resolveNameMapping($form);
        $transformedName = $nameMapping[''] ?? $form->getName();;

        if ($transformedName !== '') {
            $path = $this->resolvePath($view->vars['full_name']);

            if (Arrays::get($query, $path, '') === '') {
                Arrays::set($query, $path, '');

                return $this->normalizeEncodedQueryStringEmptyParameter(
                    Uri::encodeQuery($query),
                    $view->vars['full_name']
                );
            }
        }

        return Uri::encodeQuery($query);
    }

    private function normalizeEncodedQueryStringEmptyParameter(string $queryString, string $parameter): string
    {
        $pattern = sprintf(
            '/(%s)=(?=&|$)/',
            substr(preg_quote(http_build_query([$parameter => ''])), 0, -2)
        );

        return preg_replace($pattern, '$1', $queryString);
    }

    private function isEmptyData(FormInterface $form): bool
    {
        return $this->submittedData[$form] === ''
            || $this->submittedData[$form] === $this->transform($form, $this->resolveEmptyData($form));
    }

    /**
     * @param FormInterface $form
     * @param mixed $data
     * @return mixed
     */
    private function transform(FormInterface $form, $data)
    {
        foreach ($form->getConfig()->getModelTransformers() as $transformer) {
            $data = $transformer->transform($data);
        }

        return $data;
    }

    private function resolveEmptyData(FormInterface $form): string
    {
        $emptyData = $form->getConfig()->getEmptyData();

        if ($emptyData instanceof \Closure) {
            $emptyData = $emptyData($form, null);
        }

        return (string) $emptyData;
    }
}
