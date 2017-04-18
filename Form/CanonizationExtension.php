<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanio\Stdlib\Arrays;
use Vanio\Stdlib\Uri;

class CanonizationExtension extends AbstractTypeExtension
{
    /** @var RequestStack */
    private $requestStack;

    /** @var \SplObjectStorage */
    private $query;

    /** @var \SplObjectStorage */
    private $submittedData;

    /** @var \SplObjectStorage */
    private $canonicalData;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $this->query = new \SplObjectStorage;
        $this->submittedData = new \SplObjectStorage;
        $this->canonicalData = new \SplObjectStorage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'], PHP_INT_MAX);

        if ($options['canonize']) {
            $builder->setMethod($this->requestStack->getCurrentRequest()->getRealMethod());
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

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $root = $form->getRoot();

        if (!$form->isSubmitted() || !$root->getConfig()->getOption('canonize')) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $query = $this->query[$root] ?? $request->query->all();
        $path = $this->resolvePath($view->vars['full_name']);

        try {
            Arrays::unset($query, $path);
        } catch (\InvalidArgumentException $e) {}

        $this->query[$root] = $query;

        if (!$form->getConfig()->getCompound() && !$this->isEmptyData($form)) {
            $canonicalData = $this->canonicalData[$root] ?? [];
            Arrays::set($canonicalData, $path, $this->submittedData[$form]);
            $this->canonicalData[$root] = $canonicalData;
        }

        if ($form->isRoot()) {
            $this->redirectToCanonicalUrl($form, $view);
        }
    }

    /**
     * @internal
     */
    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        if ($form->getRoot()->getConfig()->getOption('canonize') && !$form->getConfig()->getCompound()) {
            $this->submittedData[$form] = (string) $event->getData();
        }
    }

    private function resolvePath(string $formName): array
    {
        $path = str_replace('[]', '', $formName);
        $path = str_replace(']', '', $path);

        return explode('[', $path);
    }

    private function redirectToCanonicalUrl(FormInterface $form, FormView $formView)
    {
        $request = $this->requestStack->getCurrentRequest();
        $query = $this->query[$form];
        $query += $this->canonicalData[$form] ?? [];
        $queryString = Uri::encodeQuery($query);

        if (
            $form->getName() !== ''
            && Arrays::get($query, $this->resolvePath($formView->vars['full_name']), '') === ''
        ) {
            $queryString .= ($query ? '&' : '') . $formView->vars['full_name'];
        }

        if ($request->getRealMethod() !== 'GET' || $request->server->get('QUERY_STRING') !== $queryString) {
            $uri = $request->getBaseUrl() . $request->getPathInfo();

            if ($queryString) {
                $uri .= '?' . $queryString;
            }

            (new RedirectResponse($uri))->send();
            exit;
        }
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
