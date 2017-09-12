<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MapEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add($options['key_name'], $options['key_type'], $options['key_options'])
            ->add($options['value_name'], $options['value_type'], $options['value_options']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'label' => false,
                'key_type' => null,
                'key_name' => 'key',
                'key_options' => [],
                'value_type' => null,
                'value_name' => 'value',
                'value_options' => [],
            ])
            ->setAllowedTypes('key_type', ['string', 'null'])
            ->setAllowedTypes('key_name', 'string')
            ->setAllowedTypes('key_options', 'array')
            ->setAllowedTypes('value_type', ['string', 'null'])
            ->setAllowedTypes('value_name', 'string')
            ->setAllowedTypes('value_options', 'array');
    }
}
