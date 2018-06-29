<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
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
        if (!empty($options['optional'])) {
            $builder
                ->add('_optional_toggle', CheckboxType::class, [
                    'label' => $options['optional_toggle_label'],
                    'label_attr' => $options['optional_toggle_label_attr'],
                    'translation_domain' => $options['optional_toggle_translation_domain'],
                    'required' => false,
                    'mapped' => false,
                ])
                ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['optional'] = $options['optional'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
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

        $options = $config->getOptions();

        $validationGroups = function (FormInterface $form) use ($options) {
            if (!$form->get('_optional_toggle')->getData()) {
                return false;
            }

            return is_callable($options['validation_groups'])
                ? $options['validation_groups']($form)
                : $options['validation_groups'];
        };

        if ($options['js_validation_groups'] === null) {
            $options['js_validation_groups'] = sprintf(
                "form.get('_optional_toggle').getData() ? %s : []",
                json_encode($this->resolveValidationGroups($form))
            );
        }

        Objects::setPropertyValue($config, 'options', $options, FormConfigBuilder::class);
    }

    private function resolveValidationGroups(FormInterface $form): array
    {
        $groups = $form->getConfig()->getOption('validation_groups');

        if ($groups instanceof \Closure) {
            return $this->getElementId($form);
        } elseif ($groups === false) {
            return [];
        } elseif (is_array($groups)) {
            return $groups;
        }

        return $form->getParent() ? $this->resolveValidationGroups($form->getParent()) : ['Default'];
    }
}
