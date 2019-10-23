<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanio\Stdlib\Arrays;
use Vanio\Stdlib\Objects;

class NameMappingExtension extends AbstractTypeExtension
{
    /**
     * @param FormBuilderInterface $builder
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['name_mapping']) {
            // Cannot set requestHandler directly, it would be overridden
            $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        }
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param mixed[] $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$form->getRoot()->getConfig()->getRequestHandler() instanceof NameMappingRequestHandler) {
            return;
        }

        $path = explode('[', $view->vars['full_name']);
        $nameMapping = [];
        $replacedPath = [];
        $index = 0;

        foreach ($this->resolveFormHierarchy($form) as $child) {
            $nameMapping = $nameMapping[rtrim($path[$index], ']')] ?? [];
            $nameMapping = array_replace_recursive(self::resolveNameMapping($child), $nameMapping);
            $transformedName = $nameMapping[''] ?? $child->getName();

            if ($transformedName !== '') {
                $replacedPath[] = $replacedPath ? "{$transformedName}]" : $transformedName;
            }

            if ($child->getName() !== '') {
                $index++;
            }
        }

        if ($replacedPath) {
            $view->vars['full_name'] = implode('[', $replacedPath);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('name_mapping', [])
            ->setAllowedTypes('name_mapping', [\Closure::class, 'array', 'string']);
    }

    public function getExtendedType(): string
    {
        return FormType::class;
    }

    /**
     * @internal
     */
    public function onPreSetData(FormEvent $formEvent): void
    {
        $formConfig = $formEvent->getForm()->getRoot()->getConfig();

        if (
            $formConfig instanceof FormConfigBuilder
            && !$formConfig->getRequestHandler() instanceof NameMappingRequestHandler
        ) {
            $requestHandler = &Objects::getPropertyValue($formConfig, 'requestHandler', FormConfigBuilder::class);
            $requestHandler = new NameMappingRequestHandler;
        }
    }

    /**
     * @return mixed[]
     */
    public static function resolveNameMapping(FormInterface $form): array
    {
        $nameMapping = $form->getConfig()->getOption('name_mapping');

        if ($nameMapping instanceof \Closure) {
            $nameMapping = $nameMapping($form);
        }

        if (!is_array($nameMapping)) {
            return ['' => $nameMapping];
        }

        $normalizedNameMapping = [];

        foreach ($nameMapping as $path => $name) {
            if ($path === '') {
                $normalizedNameMapping[''] = $name;
            } else {
                Arrays::set($normalizedNameMapping, explode('.', "{$path}."), $name);
            }
        }

        return $normalizedNameMapping;
    }

    /**
     * @return FormInterface[]
     */
    private function resolveFormHierarchy(FormInterface $form): array
    {
        $formHierarchy = [];

        do {
            array_unshift($formHierarchy, $form);
        } while ($form = $form->getParent());

        return $formHierarchy;
    }
}
