<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanio\DomainBundle\Form\EntityValueType;
use Vanio\DomainBundle\Form\ValueToEntityTransformer;

class AutoCompleteEntityValueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new ValueToEntityTransformer(
            $options['entity_manager'],
            $options['class'],
            (array) $options['property'],
            false,
            $options['query_builder']
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('property')
            ->setAllowedTypes('property', ['string', 'array']);
    }

    public function getParent(): string
    {
        return AutoCompleteEntityType::class;
    }
}
