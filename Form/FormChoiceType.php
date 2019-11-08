<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormChoiceType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData'])
            ->setDataMapper($this)
            ->setAttribute('builder', $builder)
            ->add($options['choice_name'], ChoiceType::class, $options['choice_options'] + [
                'mapped' => false,
                'label' => false,
            ]);
        $builder->get($options['choice_name'])->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $builder = $form->getConfig()->getAttribute('builder');

        foreach ($view[$options['choice_name']]->vars['choices'] as $choiceView) {
            $formChoiceForm = $this->createForm($builder, $this->resolveFormOptions($options, $choiceView->data));
            $formView = $formChoiceForm->setParent($form)->createView($view);
            $view[$options['choice_name']]->vars['forms'][$choiceView->value] = $formView;

            if ($formView->vars['multipart']) {
                $view->vars['multipart'] = true;
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'choice_name' => '__choice',
                'choice_data' => null,
                'choice_options' => [],
                'form_name' => 'form',
                'form_type' => null,
                'form_options' => [],
            ])
            ->setAllowedTypes('choice_name', 'string')
            ->setAllowedTypes('choice_data', 'callable')
            ->setAllowedTypes('choice_options', 'array')
            ->setAllowedTypes('form_name', ['string', 'callable'])
            ->setAllowedTypes('form_type', 'callable')
            ->setAllowedTypes('form_options', ['array', 'callable']);
    }

    /**
     * @param mixed $data
     * @param \Traversable|FormInterface[] $forms
     */
    public function mapDataToForms($data, $forms)
    {
        if ($data === null) {
            return;
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface $form */
        $form = current($forms);
        $options = $form->getParent()->getConfig()->getOptions();

        if (!isset($forms[$options['choice_name']])) {
            return;
        }

        $choiceData = call_user_func($options['choice_data'], $data);
        $form->setData($choiceData);
        $form = next($forms);
        $form->setData($data);
    }

    /**
     * @param \Traversable|FormInterface[] $forms
     * @param mixed $data
     */
    public function mapFormsToData($forms, &$data)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface $form */
        $form = next($forms);
        $data = $form ? $form->getData() : null;
    }

    /**
     * @internal
     */
    public function onPreSetData(FormEvent $event)
    {
        if ($event->getData() === null) {
            return;
        }

        $form = $event->getForm();
        $options = $form->getConfig()->getOptions();
        $choiceData = call_user_func($options['choice_data'], $event->getData());
        $formOptions = $this->resolveFormOptions($options, $choiceData);
        $form->add($formOptions['form_name'], $formOptions['form_type'], $formOptions['form_options']);
    }

    /**
     * @internal
     */
    public function onPostSubmit(FormEvent $event)
    {
        if ($event->getData() === '') {
            return;
        }

        $form = $event->getForm()->getParent();
        $options = $form->getConfig()->getOptions();
        $children = $form->all();
        next($children);
        $form->remove(key($children));
        $formOptions = $this->resolveFormOptions($options, $event->getForm()->getData());
        $form->add($formOptions['form_name'], $formOptions['form_type'], $formOptions['form_options']);

        if ($form->get($options['choice_name'])->getData() === call_user_func($options['choice_data'], $form->getData())) {
            $form->get($formOptions['form_name'])->setData($form->getData());
        }
    }

    /**
     * @param array $options
     * @param mixed $choiceData
     * @return array
     */
    private function resolveFormOptions(array $options, $choiceData): array
    {
        $formOptions = [];

        foreach (['form_name', 'form_type', 'form_options'] as $option) {
            $formOptions[$option] = is_callable($options[$option])
                ? call_user_func($options[$option], $choiceData)
                : $options[$option];
        }

        $formOptions['form_options'] += [
            'label' => false,
            'js_validation' => false,
        ];

        return $formOptions;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return FormInterface
     */
    private function createForm(FormBuilderInterface $builder, array $options): FormInterface
    {
        $builder = $builder->create($options['form_name'], $options['form_type'], $options['form_options']);
        $form = $builder->getForm();
        $form->setData($this->resolveEmptyData($form));

        return $form;
    }

    /**
     * @param FormInterface $form
     * @return mixed
     */
    private function resolveEmptyData(FormInterface $form)
    {
        $emptyData = $form->getConfig()->getEmptyData();

        return $emptyData instanceof \Closure
            ? $emptyData($form, null)
            : $emptyData;
    }
}
