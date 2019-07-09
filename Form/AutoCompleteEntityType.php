<?php
namespace Vanio\WebBundle\Form;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Vanio\DomainBundle\UnexpectedResponse\UnexpectedResponseException;
use Vanio\Stdlib\Objects;

class AutoCompleteEntityType extends AbstractType implements DataMapperInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var FormRendererInterface */
    private $formRenderer;

    /** @var TranslatorInterface */
    private $translator;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(
        ManagerRegistry $doctrine,
        FormRendererInterface $formRenderer,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->doctrine = $doctrine;
        $this->formRenderer = $formRenderer;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!isset($options['search_options']['attr'])) {
            $options['search_options']['attr'] = [];
        }

        if (isset($options['placeholder'])) {
            $options['search_options']['attr']['placeholder'] = $options['placeholder'];
        }

        $builder
            ->add('entityId', $options['entity_id_type'], $options['entity_id_options'] + [
                'label' => false,
                'required' => true,
                'required_message' => $options['required_message'],
                'error_bubbling' => true,
            ])
            ->add($options['search_name'], $options['search_type'], $options['search_options'] + [
                'label' => false,
                'required' => false,
                'error_bubbling' => true,
            ])
            ->add('ajax', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
            ->addViewTransformer(new AutoCompleteEntityTransformer($options['query_builder']))
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'])
            ->setDataMapper($this);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['searchName'] = $options['search_name'];
        $autoCompleteOptions = $view->vars['attr']['data-component-auto-complete'] ?? [];
        $autoCompleteOptions +=[
            'entitySelector' => "#{$view['entityId']->vars['id']}",
            'searchSelector' => "#{$view[$options['search_name']]->vars['id']}",
            'ajaxField' => $view['ajax']->vars['full_name'],
            'allowUnsuggested' => $options['allow_unsuggested'],
            'remainingCountLabel' => $options['remaining_count_label'],
        ];
        $view->vars['attr']['data-component-auto-complete'] = $autoCompleteOptions;
        $view->vars['nonCompoundWrapper'] = true;
        $view['ajax']->setRendered(true);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'error_bubbling' => false,
                'class' => null,
                'entity_id_type' => HiddenType::class,
                'entity_id_options' => [],
                'search_type' => null,
                'search_name' => 'search',
                'search_options' => [],
                'query_builder' => null,
                'search_query_builder' => null,
                'search_value' => null,
                'suggestion_data' => null,
                'suggestion_label' => null,
                'suggestion_form_theme' => null,
                'allow_unsuggested' => false,
                'group_by' => null,
                'group_translation_domain' => false,
                'placeholder' => null,
                'remaining_count_link' => null,
                'remaining_count_label' => null,
                'remaining_count' => function (array $entities, array $queries) {
                    $remainingCount = 0;

                    foreach ($queries as $class => $query) {
                        $paginator = new Paginator($query);
                        $remainingCount += $paginator->count() - count($entities[$class]);
                    }

                    return $remainingCount;
                },
            ])
            ->setNormalizer('class', $this->classNormalizer())
            ->setNormalizer('query_builder', $this->queryBuilderNormalizer())
            ->setNormalizer('suggestion_label', $this->choiceLabelNormalizer())
            ->setNormalizer('group_by', $this->groupByNormalizer())
            ->setAllowedTypes('class', ['string', 'array'])
            ->setAllowedTypes('entity_id_type', 'string')
            ->setAllowedTypes('entity_id_options', 'array')
            ->setAllowedTypes('search_type', ['string', 'null'])
            ->setAllowedTypes('search_name', 'string')
            ->setAllowedTypes('search_options', 'array')
            ->setAllowedTypes('query_builder', [QueryBuilder::class, 'callable', 'null'])
            ->setAllowedTypes('search_query_builder', 'callable')
            ->setAllowedTypes('remaining_count', 'callable')
            ->setAllowedTypes('remaining_count_link', ['string', 'callable', 'null'])
            ->setAllowedTypes('remaining_count_label', ['string', 'null'])
            ->setAllowedTypes('search_value', ['string', 'callable', 'null'])
            ->setAllowedTypes('suggestion_data', ['callable', 'null'])
            ->setAllowedTypes('suggestion_label', ['callable', PropertyPath::class, 'string', 'null'])
            ->setAllowedTypes('suggestion_form_theme', ['string', 'null'])
            ->setAllowedTypes('allow_unsuggested', 'boolean')
            ->setAllowedTypes('group_by', ['callable', PropertyPath::class, 'string', 'null'])
            ->setAllowedTypes('group_translation_domain', ['string', 'callable', 'bool', 'null'])
            ->setAllowedTypes('placeholder', ['string', 'null'])
            ->setRequired(['class', 'query_builder']);
    }

    /**
     * @param mixed $data
     * @param \Iterator|FormInterface[] $forms
     */
    public function mapDataToForms($data, $forms)
    {
        $forms = iterator_to_array($forms);
        $form = $forms['entityId']->getParent();
        $options = $form->getConfig()->getOptions();

        if (is_string($options['search_value'])) {
            $search = $options['search_value'];
        } elseif ($options['search_value'] === null) {
            $search = $data === null
                ? null
                : $options['suggestion_label']($form->getNormData());
        } else {
            $search = $options['search_value']($form->getNormData());
        }

        $forms[$options['search_name']]->setData($search);
        $forms['entityId']->setData($data);
    }

    /**
     * @param \Iterator|FormInterface[] $forms
     * @param mixed $data
     */
    public function mapFormsToData($forms, &$data)
    {
        $forms = iterator_to_array($forms);
        $data = $forms['entityId']->getData();
    }

    /**
     * @internal
     */
    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        if (!$form->get('ajax')->getData()) {
            return;
        }

        $options = $form->getConfig()->getOptions();
        $search = $form->get($options['search_name'])->getData();
        $formView = $form->createView();

        if ($options['suggestion_form_theme'] !== null) {
            $this->formRenderer->setTheme($formView, $options['suggestion_form_theme']);
        }

        $suggestions = [];
        $queries = [];

        foreach ($options['query_builder'] as $class => $queryBuilder) {
            $queryBuilder = $options['search_query_builder']($queryBuilder, $search, $class, $form);

            if (!$queryBuilder instanceof QueryBuilder) {
                throw new InvalidConfigurationException(sprintf(
                    'The query_builder callable option must return an instance of "%s", "%s" returned.',
                    QueryBuilder::class,
                    Objects::getType($queryBuilder)
                ));
            }

            $queries[$class] = $queryBuilder->getQuery();
            $entities[$class] = $queries[$class]->getResult();
            $suggestions = array_merge($suggestions, $this->resolveSuggestions(
                $form,
                $formView,
                $entities[$class],
                $class,
                $search
            ));
        }

        $remainingCountLink = $options['remaining_count_link'];

        if (is_callable($remainingCountLink) && !is_string($remainingCountLink)) {
            $remainingCountLink = $remainingCountLink(
                $this->urlGenerator,
                $form->get($options['search_name'])->getData()
            );
        }

        throw new UnexpectedResponseException(new JsonResponse([
            'query' => $search,
            'remainingCount' => $options['remaining_count']($entities, $queries),
            'remainingCountLink' => $remainingCountLink,
            'suggestions' => $suggestions,
        ]));
    }

    /**
     * @param FormInterface $form
     * @param FormView $formView
     * @param object[] $entities
     * @param string $class
     * @param string $search
     * @return mixed[]
     */
    private function resolveSuggestions(
        FormInterface $form,
        FormView $formView,
        array $entities,
        string $class,
        string $search
    ): array {
        $options = $form->getConfig()->getOptions();
        $suggestions = [];

        foreach ($entities as $entity) {
            $label = $options['suggestion_label']($entity);

            try {
                $vars = $formView->vars + [
                    'form' => $formView,
                    'entity' => $entity,
                ];
                $html = $this->formRenderer->searchAndRenderBlock($formView, 'suggestion', $vars);
            } catch (LogicException $e) {}

            $data = $options['suggestion_data']
                ? $options['suggestion_data']($entity)
                : [];

            if ($options['group_by']) {
                $data['_group'] = (string) $options['group_by']($entity);

                if ($translationDomain = $this->resolveGroupTranslationDomain($form, $class)) {
                    $data['_group'] = $this->translator->trans($data['_group'], [], $translationDomain);
                }
            }

            $suggestion = [
                'value' => $label,
                'viewValue' => $this->transformToViewValue($form, $entity),
                'data' => $data,
            ];

            if (isset($html)) {
                $suggestion['html'] = $html;
            }

            $suggestions[] = $suggestion;
        }

        return $suggestions;
    }


    /**
     * @param FormInterface $form
     * @param string $class
     * @return string|bool
     */
    private function resolveGroupTranslationDomain(FormInterface $form, string $class)
    {
        $translationDomain = $form->getConfig()->getOption('group_translation_domain');

        if (!is_string($translationDomain) && is_callable($translationDomain)) {
            $translationDomain = $translationDomain($class);
        }

        if ($translationDomain === null) {
            do {
                $translationDomain = $form->getConfig()->getOption('translation_domain');

                if ($translationDomain !== null) {
                    return $translationDomain;
                }
            } while ($form = $form->getParent());
        }

        return false;
    }

    private function classNormalizer(): \Closure
    {
        return function (Options $options, $class) {
            return (array) $class;
        };
    }

    private function queryBuilderNormalizer(): \Closure
    {
        return function (Options $options, $queryBuilder = null) {
            if ($queryBuilder instanceof QueryBuilder) {
                return array_fill_keys($options['class'], $queryBuilder);
            }

            $queryBuilders = [];

            foreach ($options['class'] as $class) {
                $entityRepository = $this->doctrine->getManagerForClass($class)->getRepository($class);
                $queryBuilders[$class] = is_callable($queryBuilder)
                    ? $queryBuilder($entityRepository)
                    : $entityRepository->createQueryBuilder('e');
            }

            return $queryBuilders;
        };
    }

    private function choiceLabelNormalizer(): \Closure
    {
        return function (Options $options, $choiceLabel) {
            if ($choiceLabel === null) {
                return function ($entity) {
                    return (string) $entity;
                };
            } elseif (is_string($choiceLabel)) {
                $choiceLabel = new PropertyPath($choiceLabel);
            }

            if ($choiceLabel instanceof PropertyPath) {
                $propertyAccessor = PropertyAccess::createPropertyAccessor();

                return function ($entity) use ($propertyAccessor, $choiceLabel) {
                    return (string) $propertyAccessor->getValue($entity, $choiceLabel);
                };
            }

            return $choiceLabel;
        };
    }

    private function groupByNormalizer(): \Closure
    {
        return function (Options $options, $groupBy) {
            if ($groupBy === null) {
                return null;
            } elseif (is_string($groupBy)) {
                $groupBy = new PropertyPath($groupBy);
            }

            if ($groupBy instanceof PropertyPath) {
                $propertyAccessor = PropertyAccess::createPropertyAccessor();

                return function ($entity) use ($propertyAccessor, $groupBy) {
                    return (string) $propertyAccessor->getValue($entity, $groupBy);
                };
            }

            return $groupBy;
        };
    }

    /**
     * @param FormInterface $form
     * @param mixed $value
     * @return mixed
     */
    private function transformToViewValue(FormInterface $form, $value)
    {
        foreach ($form->getConfig()->getViewTransformers() as $transformer) {
            $value = $transformer->transform($value);
        }

        return $value;
    }
}
