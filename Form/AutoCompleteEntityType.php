<?php
namespace Vanio\WebBundle\Form;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
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
use Vanio\DomainBundle\Form\EntityToIdTransformer;
use Vanio\DomainBundle\UnexpectedResponse\UnexpectedResponseException;

class AutoCompleteEntityType extends AbstractType implements DataMapperInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var FormRendererInterface */
    private $formRenderer;

    public function __construct(ManagerRegistry $doctrine, FormRendererInterface $formRenderer)
    {
        $this->doctrine = $doctrine;
        $this->formRenderer = $formRenderer;
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
            ->add('entity', $options['entity_type'], $options['entity_options'] + [
                'label' => false,
            ])
            ->add('search', $options['search_type'], $options['search_options'] + [
                'label' => false,
                'required' => false,
            ])
            ->add('ajax', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
            ->addViewTransformer(new EntityToIdTransformer($options['query_builder']))
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'])
            ->setDataMapper($this);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-component-auto-complete'] = [
            'entitySelector' => "#{$view['entity']->vars['id']}",
            'searchSelector' => "#{$view['search']->vars['id']}",
            'ajaxField' => $view['ajax']->vars['full_name'],
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'entity_type' => HiddenType::class,
                'entity_options' => [],
                'search_type' => null,
                'search_options' => [],
                'class' => null,
                'entity_manager' => null,
                'query_builder' => null,
                'search_query_builder' => null,
                'fetch_paginator_join_collection' => true,
                'search_value' => null,
                'suggestion_data' => null,
                'suggestion_label' => null,
                'group_by' => null,
                'placeholder' => null,
                'suggestion_form_theme' => null,
            ])
            ->setNormalizer('entity_manager', $this->entityManagerNormalizer())
            ->setNormalizer('query_builder', $this->queryBuilderNormalizer())
            ->setNormalizer('suggestion_label', $this->choiceLabelNormalizer())
            ->setNormalizer('group_by', $this->groupByNormalizer())
            ->setAllowedTypes('entity_type', 'string')
            ->setAllowedTypes('entity_options', 'array')
            ->setAllowedTypes('search_type', ['string', 'null'])
            ->setAllowedTypes('search_options', 'array')
            ->setAllowedTypes('entity_manager', [EntityManager::class, 'string', 'null'])
            ->setAllowedTypes('query_builder', [QueryBuilder::class, 'callable', 'null'])
            ->setAllowedTypes('search_query_builder', 'callable')
            ->setAllowedTypes('fetch_paginator_join_collection', 'bool')
            ->setAllowedTypes('search_value', ['string', 'callable', 'null'])
            ->setAllowedTypes('suggestion_form_theme', ['string', 'null'])
            ->setAllowedTypes('suggestion_data', ['callable', 'null'])
            ->setAllowedTypes('suggestion_label', ['callable', PropertyPath::class, 'string', 'null'])
            ->setAllowedTypes('group_by', ['callable', PropertyPath::class, 'string', 'null'])
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
        $form = $forms['entity']->getParent();
        $options = $form->getConfig()->getOptions();

        if (is_string($options['search_value'])) {
            $search = $options['search_value'];
        } elseif ($options['search_value'] === null) {
            $search = $data === null
                ? null
                : call_user_func($options['suggestion_label'], $form->getNormData());
        } else {
            $search = call_user_func($options['search_value'], $form->getNormData());
        }

        $forms['search']->setData($search);
        $forms['entity']->setData($data);
    }

    /**
     * @param \Iterator|FormInterface[] $forms
     * @param mixed $data
     */
    public function mapFormsToData($forms, &$data)
    {
        $forms = iterator_to_array($forms);
        $data = $forms['entity']->getData();
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
        $searchForm = $form->get('search');
        $searchFormView = $searchForm->createView();
        $entityOptions = $form->get('entity')->getConfig()->getOptions();

        /** @var QueryBuilder $queryBuilder */
        if (!$queryBuilder = $options['query_builder']) {
            /** @var EntityManager $entityManager */
            $entityManager = $entityOptions['em'];
            $queryBuilder = $entityManager->getRepository($entityOptions['class'])->createQueryBuilder('entity');
        }

        if ($options['suggestion_form_theme'] !== null) {
            $this->formRenderer->setTheme($searchFormView, $options['suggestion_form_theme']);
        }

        $queryBuilder = call_user_func($options['search_query_builder'], $queryBuilder, $searchForm->getData(), $form);
        $query = $queryBuilder->getQuery();
        $suggestions = [];
        $paginator = new Paginator($query, $options['fetch_paginator_join_collection']);

        foreach ($query->getResult() as $entity) {
            try {
                $html = $this->formRenderer->searchAndRenderBlock($searchFormView, 'item', $searchFormView->vars + [
                    'form' => $searchFormView,
                    'entity' => $entity,
                ]);
            } catch (LogicException $e) {}

            $data = call_user_func($options['suggestion_data'], $entity);

            if ($options['group_by']) {
                $data['_group'] = (string) call_user_func($options['group_by'], $entity);
            }

            $suggestion = [
                'value' => call_user_func($options['suggestion_label'], $entity),
                'viewValue' => $this->transformToViewValue($form, $entity),
                'data' => $data,
            ];

            if (isset($html)) {
                $suggestion['html'] = $html;
            }

            $suggestions[] = $suggestion;
        }

        throw new UnexpectedResponseException(new JsonResponse([
            'query' => $searchForm->getData(),
            'totalCount' => $paginator->count(),
            'suggestions' => $suggestions,
        ]));
    }

    private function entityManagerNormalizer(): \Closure
    {
        return function (Options $options, $entityManager) {
            if ($entityManager instanceof EntityManager) {
                return $entityManager;
            } elseif ($entityManager !== null) {
                return $this->registry->getManager($entityManager);
            }

            if (!$entityManager = $this->doctrine->getManagerForClass($options['class'])) {
                throw new RuntimeException(sprintf(
                    'Class "%s" seems not to be a managed Doctrine entity. Did you forget to map it?',
                    $options['class']
                ));
            }

            return $entityManager;
        };
    }

    private function queryBuilderNormalizer(): \Closure
    {
        return function (Options $options, $queryBuilder = null) {
            if ($queryBuilder instanceof QueryBuilder) {
                return $queryBuilder;
            }

            /** @var EntityManager $entityManager */
            $entityManager = $options['entity_manager'];
            $entityRepository = $entityManager->getRepository($options['class']);

            if (is_callable($queryBuilder)) {
                return call_user_func($queryBuilder, $entityRepository);
            }


            return $entityRepository->createQueryBuilder('e');
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
