<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

use DoctrineExtensions\Hierarchical\AbstractDecorator,
    DoctrineExtensions\Hierarchical\Node,
    Doctrine\ORM\Query\NoResultException;

/**
 * TODO: Should we really use an exception as a normal and expected flow?
 * This happens when we expect a single result (like getPrevSibling, getFirstChild, etc)
 *
 * TODO: Isn't there a way to make this thing act as the entity it decorates? so it's easier to pass it along to the EM
 */
class NestedSetDecorator extends AbstractDecorator implements Node, NestedSetNodeInfo
{
    public function hasPrevSibling()
    {
        return $this->getPrevSibling() !== null;
    }

    public function hasNextSibling()
    {
        return $this->getNextSibling() !== null;
    }

    public function hasChildren()
    {
        return ($this->getRightValue() - $this->getLeftValue()) > 1;
    }

    public function hasParent()
    {
        return $this->getLevel() != 0;
    }

    public function isRoot()
    {
        return ! $this->hasParent();
    }

    public function isLeaf()
    {
        return ! $this->hasChildren();
    }

    public function getPrevSibling()
    {
        $em = $this->getEntityManager();
        $config = $this->getConfiguration();

        $q = $em->createQuery('
            SELECT e FROM ' . $this->getClassName() . ' e
             WHERE e.' . $config->getRootFieldName() . ' = ?1
               AND e.' . $config->getRightFieldName() . ' = ?2
        ')->setParameters(array(
            1 => $this->getRoot(),
            2 => $this->getLeftValue() - 1
        ));

        try {
            $sibling = $q->getSingleResult();

            return $this->getNode($sibling);
        } catch (NoResultException $e) {
            // Do nothing
        }

        return null;
    }

    public function getNextSibling()
    {
        $em = $this->getEntityManager();
        $config = $this->getConfiguration();

        $q = $em->createQuery('
            SELECT e FROM ' . $this->getClassName() . ' e
             WHERE e.' . $config->getRootFieldName() . ' = ?1
               AND e.' . $config->getLeftFieldName() . ' = ?2
        ')->setParameters(array(
            1 => $this->getRoot(),
            2 => $this->getRightValue() + 1
        ));

        try {
            $sibling = $q->getSingleResult();

            return $this->getNode($sibling);
        } catch (NoResultException $e) {
            // Do nothing
        }

        return null;
    }

    public function getChildren()
    {
        $hm = $this->getHierarchicalManager();
        $em = $this->getEntityManager();
        $config = $this->getConfiguration();

        $q = $em->createQuery('
            SELECT e FROM ' . $this->getClassName() . ' e
             WHERE e.' . $config->getRootFieldName() . ' = ?1
               AND e.' . $config->getLeftFieldName() . ' > ?2
               AND e.' . $config->getRightFieldName() . ' < ?3
               AND e.' . $config->getLevelFieldName() . ' = ?4
        ')->setParameters(array(
            1 => $this->getRoot(),
            2 => $this->getLeftValue(),
            3 => $this->getRightValue(),
            4 => $this->getLevel() + 1
        ));

        $children = $q->getResult();

        // Return instance of ArrayCollection instead of PersistentCollection
        return $children->unwrap()->map(
            function ($child) use ($hm) {
                return $hm->getNode($child);
            }
        );
    }

    public function getParent()
    {
        $em = $this->getEntityManager();
        $config = $this->getConfiguration();

        $q = $em->createQuery('
            SELECT e FROM ' . $this->getClassName() . ' e
             WHERE e.' . $config->getRootFieldName() . ' = ?1
               AND e.' . $config->getLeftFieldName() . ' < ?2
               AND e.' . $config->getRightFieldName() . ' > ?3
               AND e.' . $config->getLevelFieldName() . ' = ?4
        ')->setParameters(array(
            1 => $this->getRoot(),
            2 => $this->getLeftValue(),
            3 => $this->getRightValue(),
            4 => $this->getLevel() - 1
        ));

        try {
            $sibling = $q->getSingleResult();

            return $this->getNode($sibling);
        } catch (NoResultException $e) {
            // Do nothing
        }

        return null;
    }

    public function getFirstChild()
    {
        $em = $this->getEntityManager();
        $config = $this->getConfiguration();

        $q = $em->createQuery('
            SELECT e FROM ' . $this->getClassName() . ' e
             WHERE e.' . $config->getRootFieldName() . ' = ?1
               AND e.' . $config->getLeftFieldName() . ' = ?2
        ')->setParameters(array(
            1 => $this->getRoot(),
            2 => $this->getLeftValue() + 1
        ));

        try {
            $sibling = $q->getSingleResult();

            return $this->getNode($sibling);
        } catch (NoResultException $e) {
            // Do nothing
        }

        return null;
    }

    public function getLastChild()
    {
        $em = $this->getEntityManager();
        $config = $this->getConfiguration();

        $q = $em->createQuery('
            SELECT e FROM ' . $this->getClassName() . ' e
             WHERE e.' . $config->getRootFieldName() . ' = ?1
               AND e.' . $config->getRightFieldName() . ' = ?2
        ')->setParameters(array(
            1 => $this->getRoot(),
            2 => $this->getRightValue() - 1
        ));

        try {
            $sibling = $q->getSingleResult();

            return $this->getNode($sibling);
        } catch (NoResultException $e) {
            // Do nothing
        }

        return null;
    }

    public function getNumberOfChildren()
    {
        return count($this->getChildren());
    }

    public function getNumberOfDescendants()
    {
        return ($this->getRightValue() - $this->getLeftValue() - 1) / 2;
    }

    public function getDescendants($depth = null)
    {
    }

    public function delete()
    {
    }

    public function addChild(Node $node)
    {
    }

    public function createRoot()
    {
        $entity = $this->unwrap();

        $entity->setLevel(0);
        $entity->setRoot(0);
        $entity->setLeftValue(1);
        $entity->setRightValue(2);
        $entity->setParent(0);

        $this->getEntityManager()->persist($entity);
    }

    public function insertAsLastChildOf(Node $node)
    {
    }

    public function insertAsFirstChildOf(Node $node)
    {
    }

    public function insertAsNextSiblingOf(Node $node)
    {
    }

    public function insertAsPrevSiblingOf(Node $node)
    {
    }

    public function moveAsLastChildOf(Node $node)
    {
    }

    public function moveAsFirstChildOf(Node $node)
    {
    }

    public function moveAsNextSiblingOf(Node $node)
    {
    }

    public function moveAsPrevSiblingOf(Node $node)
    {
    }

    // Delegate support for Decorator object

    public function getLeftValue()
    {
        return $this->unwrap()->getLeftValue();
    }

    public function setLeftValue($value)
    {
        $this->unwrap()->setLeftValue($value);
    }

    public function getRightValue()
    {
        return $this->unwrap()->getRightValue();
    }

    public function setRightValue($value)
    {
        $this->unwrap()->setRightValue($value);
    }

    public function getLevel()
    {
        return $this->unwrap()->getLevel();
    }

    public function setLevel($value)
    {
        $this->unwrap()->setLevel($value);
    }

    public function getRoot()
    {
        return $this->unwrap()->getRoot();
    }

    public function setRoot($value)
    {
        $this->unwrap()->setRoot($value);
    }
}