<?php
namespace Vanio\WebBundle\Form;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanio\DomainBundle\Form\EntityValueType;
use Vanio\DomainBundle\Form\ValueToEntityTransformer;

/**
 * TODO: Support for multiple entity classes as supported in AutoCompleteEntityType
 */
class AutoCompleteEntityValueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $queryBuilder = current($options['query_builder']);
        assert($queryBuilder instanceof QueryBuilder);
        $builder->addModelTransformer(new ValueToEntityTransformer(
            $queryBuilder->getEntityManager(),
            current($options['class']),
            (array) $options['property'],
            false,
            $queryBuilder
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('property')
            ->setAllowedTypes('property', ['string', 'array'])
            ->setAllowedTypes('class', 'string');
    }

    public function getParent(): string
    {
        return AutoCompleteEntityType::class;
    }
}
