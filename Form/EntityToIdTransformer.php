<?php
namespace Vanio\WebBundle\Form;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\DataTransformerInterface;

class EntityToIdTransformer implements DataTransformerInterface
{
    /** @var QueryBuilder */
    private $queryBuilder;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = clone $queryBuilder;
    }

    /**
     * @param object $entity
     * @return object|object[]
     */
    public function transform($entity)
    {
        if (!$entity) {
            return null;
        }

        $id = $this->queryBuilder
            ->getEntityManager()
            ->getClassMetadata(get_class($entity))
            ->getIdentifierValues($entity);

        return count($id) === 1 ? current($id) : $id;
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

        $dqlAlias = $this->queryBuilder->getRootAliases()[0];

        return $this->queryBuilder
            ->andWhere("$dqlAlias = :_id")
            ->setParameter('_id', $id)
            ->getQuery()
            ->getSingleResult();
    }
}
