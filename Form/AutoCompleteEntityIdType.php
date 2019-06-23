<?php
namespace Vanio\WebBundle\Form;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * TODO: Support for multiple entity classes as supported in AutoCompleteEntityType
 */
class AutoCompleteEntityIdType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('property', [])
            ->setNormalizer('property', $this->propertyNormalizer());
    }

    public function getParent(): string
    {
        return AutoCompleteEntityValueType::class;
    }

    /**
     * @internal
     */
    public function propertyNormalizer(): \Closure
    {
        return function (Options $options) {
            $queryBuilder = current($options['query_builder']);
            assert($queryBuilder instanceof QueryBuilder);
            $classMetadata = $queryBuilder->getEntityManager()->getClassMetadata(current($options['class']));
            $property = $classMetadata->identifier;

            if (isset($classMetadata->identifierDiscriminatorField)) {
                $property = array_diff($property, (array) $classMetadata->identifierDiscriminatorField);
            }

            return $property;
        };
    }
}
