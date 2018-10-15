<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TupleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['elements'] as $name => $elementOptions) {
            $builder->add($name, $elementOptions['type'] ?? null, $elementOptions['options'] ?? []);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('elements', [])
            ->setAllowedTypes('elements', 'array');
    }
}
