<?php
namespace Vanio\WebBundle\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AutoCompleteEntityTransformer implements DataTransformerInterface
{
    /** @var QueryBuilder[] */
    private $queryBuilders;

    /**
     * @param QueryBuilder[] $queryBuilders
     */
    public function __construct(array $queryBuilders)
    {
        $this->queryBuilders = $queryBuilders;
    }

    /**
     * @param object $entity
     * @return mixed
     */
    public function transform($entity)
    {
        if (!$entity) {
            return null;
        }

        foreach ($this->queryBuilders as $class => $queryBuilder) {
            if ($entity instanceof $class) {
                $identifierValues = $queryBuilder
                    ->getEntityManager()
                    ->getClassMetadata($class)
                    ->getIdentifierValues($entity);
                $id = count($identifierValues) === 1 ? current($identifierValues) : $identifierValues;

                return count($this->queryBuilders) === 1 ? $id : json_encode([$class, $id]);
            }
        }

        return null;
    }

    /**
     * @param mixed $id
     * @return object
     */
    public function reverseTransform($id)
    {
        if ($id === null) {
            return null;
        }

        if (count($this->queryBuilders) === 1) {
            $queryBuilder = current($this->queryBuilders);
        } else {
            [$class, $id] = json_decode($id, true);

            if (!$queryBuilder = ($this->queryBuilders[$class] ?? null)) {
                return null;
            }
        }

        $dqlAlias = $queryBuilder->getRootAliases()[0];

        return (clone $queryBuilder)
            ->andWhere("{$dqlAlias} = :_id")
            ->setParameter('_id', $id)
            ->getQuery()
            ->getSingleResult();
    }
}
