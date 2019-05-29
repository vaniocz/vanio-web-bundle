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
    private $query;

    /** @var \SplObjectStorage */
    private $submittedData;

    /** @var \SplObjectStorage */
    private $canonicalData;

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
            $builder->setMethod($this->currentRequest->getRealMethod());
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $root = $form->getRoot();

        if (!$this->currentRequest || !$form->isSubmitted() || !$root->getConfig()->getOption('canonize')) {
            return;
        }

        $query = $this->query[$root] ?? $this->currentRequest->query->all();
        $path = $this->resolvePath($view->vars['full_name']);

        try {
            Arrays::unset($query, $path);
        } catch (\InvalidArgumentException $e) {}

        $this->query[$root] = $query;

        if (!$form->getConfig()->getCompound() && !$this->isEmptyData($form)) {
            $canonicalData = $this->canonicalData[$root] ?? [];
            Arrays::set($canonicalData, $path, $this->submittedData[$form]);
            $this->canonicalData[$root] = $canonicalData;
            $formView = $view;

            do {
                $formView->vars['isSubmittedWithEmptyData'] = false;
            } while ($formView = $formView->parent);
        }

        if ($form->isRoot()) {
            $queryString = $this->resolveCanonicalQueryString($form, $view);
            $canonicalUrl = $url = $this->currentRequest->getBaseUrl() . $this->currentRequest->getPathInfo();

            if ($queryString) {
                $canonicalUrl .= '?' . $queryString;
            }

            $this->headers['Canonical-Url'] = $canonicalUrl;

            if (
                !$this->currentRequest->isXmlHttpRequest() && (
                    $this->currentRequest->getRealMethod() !== 'GET'
                    || $this->currentRequest->server->get('QUERY_STRING') !== $queryString
                )
            ) {
                throw new UnexpectedResponseException(new RedirectResponse($canonicalUrl, 302, $this->headers));
            }
        }
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
    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        if ($form->getRoot()->getConfig()->getOption('canonize')) {
            if ($form->getConfig()->getCompound()) {
                if ($form->isRoot() && $event->getData() === '') {
                    $event->setData([]);
                }
            } else {
                $this->submittedData[$form] = (string) $event->getData();
            }
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

    private function resolveCanonicalQueryString(FormInterface $form, FormView $formView): string
    {
        $query = $this->query[$form];
        $query += $this->canonicalData[$form] ?? [];
        $queryString = Uri::encodeQuery($query);

        if (
            $form->getName() !== ''
            && Arrays::get($query, $this->resolvePath($formView->vars['full_name']), '') === ''
        ) {
            $queryString .= ($query ? '&' : '') . $formView->vars['full_name'];
        }

        return $queryString;
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
