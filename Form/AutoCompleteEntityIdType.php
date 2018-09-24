<?php
namespace Vanio\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            /** @var EntityManager $entityManager */
            $entityManager = $options['entity_manager'];
            $classMetadata = $entityManager->getClassMetadata($options['class']);
            $property = $classMetadata->identifier;

            if (isset($classMetadata->identifierDiscriminatorField)) {
                $property = array_diff($property, (array) $classMetadata->identifierDiscriminatorField);
            }

            return $property;
        };
    }
}
