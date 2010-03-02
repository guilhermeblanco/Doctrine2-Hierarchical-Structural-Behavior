<?php

namespace DoctrineExtensions\Hierarchical;

use DoctrineExtensions\Hierarchical\AdjacencyList\AdjacencyListNodeInfo,
    DoctrineExtensions\Hierarchical\AdjacencyList\AdjacencyListDecorator,
    DoctrineExtensions\Hierarchical\MaterializedPath\MaterializedPathNodeInfo,
    DoctrineExtensions\Hierarchical\MaterializedPath\MaterializedPathDecorator,
    DoctrineExtensions\Hierarchical\NestedSet\NestedSetNodeInfo,
    DoctrineExtensions\Hierarchical\NestedSet\NestedSetDecorator,
    DoctrineExtensions\Hierarchical\HierarchicalException,
    Doctrine\ORM\EntityManager;


class HierarchicalManager
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getNode($entity)
    {
        if ($entity instanceof AdjacencyListNodeInfo) {
            return new AdjacencyListDecorator($entity);
        } else if ($entity instanceof MaterializedPathNodeInfo) {
            return new MaterializedPathDecorator($entity);
        } else if ($entity instanceof NestedSetNodeInfo) {
            return new NestedSetDecorator($entity);
        }

        throw new HierarchicalException(
            'Provided entity does not implement any of the Hierarchical algorithms available. ' . 
            'Are you sure ' . get_class($entity) . ' implements either ' . 
            'AdjacencyListNodeInfo, MaterializedPathNodeInfo or NestedSetNodeInfo?'
        );
    }

    public function createRoot($entity)
    {
        // ...
    }

    // ...
}