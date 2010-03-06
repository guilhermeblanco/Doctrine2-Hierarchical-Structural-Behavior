<?php

namespace DoctrineExtensions\Hierarchical;

use DoctrineExtensions\Hierarchical\AdjacencyList\AdjacencyListNodeInfo,
    DoctrineExtensions\Hierarchical\AdjacencyList\AdjacencyListDecorator,
    DoctrineExtensions\Hierarchical\MaterializedPath\MaterializedPathNodeInfo,
    DoctrineExtensions\Hierarchical\MaterializedPath\MaterializedPathDecorator,
    DoctrineExtensions\Hierarchical\NestedSet\NestedSetNodeInfo,
    DoctrineExtensions\Hierarchical\NestedSet\NestedSetDecorator,
    DoctrineExtensions\Hierarchical\HierarchicalException,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\PersistentCollection;


class HierarchicalManager
{
    private $_em;

    private $_classConfiguration;

    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
        $this->_classConfiguration = array();
    }

    public function getEntityManager()
    {
        return $this->_em;
    }

    public function addClassConfiguration($className, Configuration $configuration)
    {
        $this->_classConfiguration[$className] = $configuration;
    }

    public function getClassConfiguration($className)
    {
        if ( ! isset($this->_classConfiguration[$className])) {
            throw HierarchicalException::couldNotFindClassConfiguration($className);
        }

        return $this->_classConfiguration[$className];
    }

    public function getNode($entity)
    {
        // Not yet implemented
        /*if ($entity instanceof AdjacencyListNodeInfo) {
            return new AdjacencyListDecorator($entity, $this);
        } else if ($entity instanceof MaterializedPathNodeInfo) {
            return new MaterializedPathDecorator($entity, $this);
        } else */
        if ($entity instanceof NestedSetNodeInfo) {
            return new NestedSetDecorator($entity, $this);
        }

        throw new HierarchicalException(
            'Provided entity does not implement any of the Hierarchical algorithms available. ' .
            'Are you sure ' . get_class($entity) . ' implements either ' .
            'AdjacencyListNodeInfo, MaterializedPathNodeInfo or NestedSetNodeInfo?'
        );
    }

    public function getNodes($input)
    {
        if ($input instanceof PersistentCollection) {
            // Return instance of ArrayCollection instead of PersistentCollection
            $hm = $this;
            return $input->unwrap()->map(
                function ($node) use ($hm) {
                    return $hm->getNode($node);
                }
            );
        } elseif (is_array($descendants) || $descendants instanceof Traversable) {
            foreach ($input as $key => $entity) {
                $input[$key] = $this->getNode($entity);
            }
            return $input;
        }

        throw new \InvalidArgumentException(sprintf('Input to getNodes should be a PersistentCollection or a Traversable/array, %s provided', gettype($input)));
    }

    public function createRoot($entity)
    {
        if ( ! $entity instanceof Node) {
            $entity = $this->getNode($entity);
        }

        $entity->createRoot();

        return $entity;
    }

    // ...
}