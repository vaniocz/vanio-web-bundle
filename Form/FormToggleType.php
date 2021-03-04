<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanio\Stdlib\Objects;

class FormToggleType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add($options['toggle_name'], $options['toggle_type'], $options['toggle_options'] + ['required' => false])
            ->add($options['form_name'], $options['form_type'], $options['form_options'] + ['label' => false])
            ->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData'])
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'])
            ->setDataMapper($this);
        $builder->get($options['form_name'])->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['toggleName'] = $options['toggle_name'];
        $view->vars['formName'] = $options['form_name'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'label' => false,
                'toggle_type' => CheckboxType::class,
                'toggle_name' => 'toggle',
                'toggle_options' => [],
                'form_type' => null,
                'form_name' => 'form',
                'form_options' => [],
                'disabled_data' => null,
            ])
            ->setAllowedTypes('toggle_type', 'string')
            ->setAllowedTypes('toggle_name', 'string')
            ->setAllowedTypes('toggle_options', 'array')
            ->setAllowedTypes('form_type', ['string', 'null'])
            ->setAllowedTypes('form_name', 'string')
            ->setAllowedTypes('form_options', 'array');
    }

    /**
     * @param mixed $data
     * @param \Iterator|FormInterface[] $forms
     */
    public function mapDataToForms($data, $forms)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface $child */
        $child = reset($forms);
        $options = $child->getParent()->getConfig()->getOptions();
        $forms[$options['toggle_name']]->setData((bool) $data);
        $forms[$options['form_name']]->setData($data);
    }

    /**
     * @param \Iterator|FormInterface[] $forms
     * @param mixed $data
     */
    public function mapFormsToData($forms, &$data)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface $child */
        $child = reset($forms);
        $form = $child->getParent();
        $data = $forms[$form->getConfig()->getOption('form_name')]->getData();
    }

    /**
     * @internal
     */
    public function onPreSetData(FormEvent $event)
    {
        $config = $event->getForm()->getConfig();
        $dataMapper = $config->getDataMapper();

        if ($config instanceof FormConfigBuilder) {
            if ($dataMapper && !$dataMapper instanceof FormToggleDataMapper) {
                $validatingMapper = new FormToggleDataMapper($dataMapper);
                Objects::setPropertyValue($config, 'dataMapper', $validatingMapper, FormConfigBuilder::class);
            }
        }
    }

    /**
     * @internal
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $options = $event->getForm()->getConfig()->getOptions();

        if (empty($data[$options['toggle_name']])) {
            unset($data[$options['form_name']]);
            $event->setData($data);
        }
    }

    /**
     * @internal
     */
    public function onPostSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $toggleForm = $form->get($form->getConfig()->getOption('toggle_name'));
        $innerForm = $form->get($form->getConfig()->getOption('form_name'));
        $config = $innerForm->getConfig();
        $options = $config->getOptions();

        if ($options['js_validation_groups'] === null) {
            $options['js_validation_groups'] = sprintf(
                "form.parent.get(%s).getData() ? %s : []",
                json_encode($toggleForm->getName()),
                json_encode($this->resolveJsValidationGroups($form))
            );
        }

        $options['validation_groups'] = function (FormInterface $form) use ($options, $toggleForm) {
            if (!$toggleForm->getData()) {
                return [];
            } elseif ($options['validation_groups'] === null) {
                return $this->resolveValidationGroups($form->getParent());
            } elseif (!is_string($options['validation_groups']) && is_callable($options['validation_groups'])) {
                return $options['validation_groups']($form);
            }

            return $options['validation_groups'] === false ? [] : (array) $options['validation_groups'];
        };

        Objects::setPropertyValue($config, 'options', $options, FormConfigBuilder::class);
    }

    private function resolveValidationGroups(FormInterface $form): array
    {
        $resolveValidationGroups = function () use ($form) {
            return (new FormValidator)->getValidationGroups($form);
        };
        $resolveValidationGroups = $resolveValidationGroups->bindTo(null, FormValidator::class);

        return $resolveValidationGroups();
    }

    private function resolveJsValidationGroups(FormInterface $form): array
    {
        $groups = $form->getConfig()->getOption('validation_groups');

        if ($groups instanceof \Closure) {
            return [$this->resolveElementId($form)];
        } elseif ($groups === false) {
            return [];
        } elseif (is_array($groups)) {
            return $groups;
        }

        return $form->getParent() ? $this->resolveValidationGroups($form->getParent()) : ['Default'];
    }

    private function resolveElementId(FormInterface $form): string
    {
        return $form->getParent()
            ? sprintf('%s_%s', $this->getElementId($form->getParent()), $form->getName())
            : $form->getName();
    }
}
