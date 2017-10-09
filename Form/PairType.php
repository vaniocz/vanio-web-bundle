<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PairType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add($options['first_name'], $options['first_type'], $options['first_options'])
            ->add($options['second_name'], $options['second_type'], $options['second_options']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'first_type' => null,
                'first_name' => '0',
                'first_options' => [],
                'second_type' => null,
                'second_name' => '1',
                'second_options' => [],
            ])
            ->setAllowedTypes('first_type', ['string', 'null'])
            ->setAllowedTypes('first_name', 'string')
            ->setAllowedTypes('first_options', 'array')
            ->setAllowedTypes('second_type', ['string', 'null'])
            ->setAllowedTypes('second_name', 'string')
            ->setAllowedTypes('second_options', 'array');
    }
}
