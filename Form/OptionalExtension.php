<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanio\Stdlib\Objects;

class OptionalExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$options['optional']) {
            return;
        }

        $builder
            ->add('_optional_toggle', CheckboxType::class, [
                'label' => $options['optional_toggle_label'],
                'label_attr' => $options['optional_toggle_label_attr'],
                'translation_domain' => $options['optional_toggle_translation_domain'],
                'required' => false,
                'mapped' => false,
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData'])
            ->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['optional'] = $options['optional'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'label' => function (Options $options, $label) {
                    return $options['optional'] ? $label ?? false : $label;
                },
                'optional' => false,
                'optional_toggle_label' => null,
                'optional_toggle_label_attr' => [],
                'optional_toggle_translation_domain' => null,
            ])
            ->setAllowedTypes('optional', 'bool')
            ->setAllowedTypes('optional_toggle_label_attr', 'array')
            ->setAllowedTypes('optional_toggle_translation_domain', ['string', 'bool', 'null']);
    }

    public function getExtendedType(): string
    {
        return FormType::class;
    }

    /**
     * @internal
     */
    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $config = $form->getConfig();
        $dataMapper = $config->getDataMapper();

        if ($config instanceof FormConfigBuilder) {
            if ($dataMapper && !$dataMapper instanceof OptionalDataMapper) {
                $ValidatingMapper = new OptionalDataMapper($dataMapper);
                Objects::setPropertyValue($config, 'dataMapper', $ValidatingMapper, FormConfigBuilder::class);
            }
        }
    }

    /**
     * @internal
     */
    public function onPostSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $config = $form->getConfig();
        $options = $config->getOptions();

        if ($options['js_validation_groups'] === null) {
            $options['js_validation_groups'] = sprintf(
                "form.get('_optional_toggle').getData() ? %s : []",
                json_encode($this->resolveJsValidationGroups($form))
            );
        }

        $options['validation_groups'] = function (FormInterface $form) use ($options) {
            if (!$form->get('_optional_toggle')->getData()) {
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
            return FormValidator::{'getValidationGroups'}($form);
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
