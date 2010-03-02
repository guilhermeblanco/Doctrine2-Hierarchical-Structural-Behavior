<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

use DoctrineExtensions\Hierarchical\AbstractDecorator,
    DoctrineExtensions\Hierarchical\Node,
    Doctrine\ORM\Query\NoResultException;

/**
 * TODO: "root", "lft", "rgt" and "level" cannot be hardcoded!
 * Maybe using the ClassMetadata this can be possible:
 *
 *     $class = $em->getClassMetadata($this->className);
 *
 * TODO: Should we really use an exception as a normal and expected flow?
 * This happens when we expect a single result (like getPrevSibling, getFirstChild, etc)
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
        return ($this->entity->getRightValue() - $this->entity->getLeftValue()) > 1;
    }

    public function hasParent()
    {
        return $this->entity->getLevel() != 0;
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
        $em = $this->hm->getEntityManager();

        $q = $em->createQuery('
            SELECT e FROM ' . $this->className . ' e
             WHERE e.root = ?1
               AND e.rgt = ?2
        ')->setParameters(array(
            1 => $this->entity->getRoot(),
            2 => $this->entity->getLeftValue() - 1
        ));

        try {
            $sibling = $q->getSingleResult();

            return $this->hm->getNode($sibling);
        } catch (NoResultException $e) {
            // Do nothing
        }

        return null;
    }

    public function getNextSibling()
    {
        $em = $this->hm->getEntityManager();

        $q = $em->createQuery('
            SELECT e FROM ' . $this->className . ' e
             WHERE e.root = ?1
               AND e.left = ?2
        ')->setParameters(array(
            1 => $this->entity->getRoot(),
            2 => $this->entity->getRightValue() + 1
        ));

        try {
            $sibling = $q->getSingleResult();

            return $this->hm->getNode($sibling);
        } catch (NoResultException $e) {
            // Do nothing
        }

        return null;
    }

    public function getChildren()
    {
        $hm = $this->hm;
        $em = $hm->getEntityManager();

        $q = $em->createQuery('
            SELECT e FROM ' . $this->className . ' e
             WHERE e.root = ?1
               AND e.lft > ?2
               AND e.rgt < ?3
               AND e.level = ?4
        ')->setParameters(array(
            1 => $this->entity->getRoot()),
            2 => $this->entity->getLeftValue(),
            3 => $this->entity->getRightValue(),
            4 => $this->entity->getLevel() + 1
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
        $em = $this->hm->getEntityManager();

        $q = $em->createQuery('
            SELECT e FROM ' . $this->className . ' e
             WHERE e.root = ?1
               AND e.lft < ?2
               AND e.rgt > ?3
               AND e.level = ?4
        ')->setParameters(array(
            1 => $this->entity->getRoot()),
            2 => $this->entity->getLeftValue(),
            3 => $this->entity->getRightValue(),
            4 => $this->entity->getLevel() - 1
        ));

        try {
            $sibling = $q->getSingleResult();

            return $this->hm->getNode($sibling);
        } catch (NoResultException $e) {
            // Do nothing
        }

        return null;
    }

    public function getFirstChild()
    {
        $em = $this->hm->getEntityManager();

        $q = $em->createQuery('
            SELECT e FROM ' . $this->className . ' e
             WHERE e.root = ?1
               AND e.lft = ?2
        ')->setParameters(array(
            1 => $this->entity->getRoot(),
            2 => $this->entity->getLeftValue() + 1
        ));

        try {
            $sibling = $q->getSingleResult();

            return $this->hm->getNode($sibling);
        } catch (NoResultException $e) {
            // Do nothing
        }

        return null;
    }

    public function getLastChild()
    {
        $em = $this->hm->getEntityManager();

        $q = $em->createQuery('
            SELECT e FROM ' . $this->className . ' e
             WHERE e.root = ?1
               AND e.rgt = ?2
        ')->setParameters(array(
            1 => $this->entity->getRoot(),
            2 => $this->entity->getRightValue() - 1
        ));

        try {
            $sibling = $q->getSingleResult();

            return $this->hm->getNode($sibling);
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
        return ($this->entity->getRightValue() - $this->entity->getLeftValue() - 1) / 2;
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
        return $this->entity->getLeftValue();
    }

    public function setLeftValue($value)
    {
        $this->entity->setLeftValue($value);
    }

    public function getRightValue()
    {
        return $this->entity->getRightValue();
    }

    public function setRightValue($value)
    {
        $this->entity->setRightValue($value);
    }

    public function getLevel()
    {
        return $this->entity->getLevel();
    }

    public function setLevel($value)
    {
        $this->entity->setLevel($value);
    }

    public function getRoot()
    {
        return $this->entity->getRoot();
    }

    public function setRoot($value)
    {
        $this->entity->setRoot($value);
    }
}