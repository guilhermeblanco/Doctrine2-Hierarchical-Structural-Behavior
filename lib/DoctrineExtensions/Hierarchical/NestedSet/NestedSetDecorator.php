<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

use DoctrineExtensions\Hierarchical\AbstractDecorator,
    DoctrineExtensions\Hierarchical\Node,
    DoctrineExtensions\Hierarchical\HierarchicalException,
    Doctrine\ORM\Query\NoResultException;

/**
 * TODO: Should we really use an exception as a normal and expected flow?
 * This happens when we expect a single result (like getPrevSibling, getFirstChild, etc)
 *
 * TODO: Isn't there a way to make this thing act as the entity it decorates? so it's easier to pass it along to the EM
 *
 * TODO: Can't we rename getLeft/RightValue to getLeft/Right for consistency with the other accessors ?
 *
 * TODO: Why do we call unwrap() in every second method rather than just accessing $this->_entity,
 * which btw should be called $this->entity imo, it's protected I don't see the point of the _, but
 * that might be doctrine policy..
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

    public function getRootNodes($limit = null, $offset = 0)
    {
        $hm = $this->getHierarchicalManager();
        $em = $hm->getEntityManager();
        $config = $this->getConfiguration();

        $q = $em->createQuery('
            SELECT e FROM ' . $this->getClassName() . ' e
             WHERE e.' . $config->getRootFieldName() . ' IS NULL
        ');

        if ($limit !== null) {
            $q->setMaxResults((int) $limit);
        }
        if ($offset) {
            $q->setFirstResult((int) $offset);
        }

        return $q->getResult();
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

    public function getChildren($limit = null, $offset = 0, $order = 'ASC')
    {
        return $this->getDescendants(1, $limit, $offset, $order);
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
        return $this->getNumberOfDescendants(1);
    }

    public function getAncestors($depth = null, $limit = null, $offset = 0, $order = 'ASC')
    {
        $hm = $this->getHierarchicalManager();
        $em = $hm->getEntityManager();
        $config = $this->getConfiguration();

        $qb = $em->createQueryBuilder()
            ->select('e')
            ->from($this->getClassName(), 'e');

        $expr = $qb->expr();
        $andX = $expr->andX();
        $andX->add($expr->eq('e.' . $config->getRootFieldName(), '?1'));
        $andX->add($expr->lt('e.' . $config->getLeftFieldName(), '?2'));
        $andX->add($expr->gt('e.' . $config->getRightFieldName(), '?3'));

        $qb->setParameters(array(
            1 => $this->getRoot(),
            2 => $this->getLeftValue(),
            3 => $this->getRightValue()
        ));

        if ($depth !== null && $depth > 0) {
            $andX->add($expr->lte('e.' . $config->getLevelFieldName(), '?4'));
            $qb->setParameter(4, $this->getLevel() - $depth);
        }

        $qb->where($andX);
        $qb->orderBy('e.' . $config->getLeftFieldName(), $order);

        $q = $qb->getQuery();
        if ($limit !== null) {
            $q->setMaxResults((int) $limit);
        }
        if ($offset) {
            $q->setFirstResult((int) $offset);
        }

        return $q->getResult();
    }

    public function getDescendants($depth = null, $limit = null, $offset = 0, $order = 'ASC')
    {
        if ( ! $this->hasChildren()) {
            return array();
        }

        $entity = $this->unwrap();
        $config = $this->getConfiguration();

        $qb = $this->getDescendantsPartialQuery($entity, $depth);
        $qb->select('e');
        $qb->orderBy('e.' . $config->getLeftFieldName(), $order);
        $q = $qb->getQuery();

        if ($limit !== null) {
            $q->setMaxResults((int) $limit);
        }
        if ($offset) {
            $q->setFirstResult((int) $offset);
        }

        return $q->getResult();
    }

    public function getNumberOfDescendants($depth = null)
    {
        if ( ! $depth) {
            return ($this->getRightValue() - $this->getLeftValue() - 1) / 2;
        }

        $entity = $this->unwrap();

        $qb = $this->getDescendantsPartialQuery($entity, $depth);
        $qb->select('COUNT(*)');
        $q = $qb->getQuery();

        return $q->getScalarResult();
    }

    protected function getDescendantsPartialQuery($entity, $depth)
    {
        $hm = $this->getHierarchicalManager();
        $em = $hm->getEntityManager();
        $config = $this->getConfiguration();

        $qb = $em->createQueryBuilder()
            ->from($this->getClassName(), 'e');

        $expr = $qb->expr();
        $andX = $expr->andX();
        $andX->add($expr->eq('e.' . $config->getRootFieldName(), '?1'));
        $andX->add($expr->gt('e.' . $config->getLeftFieldName(), '?2'));
        $andX->add($expr->lt('e.' . $config->getRightFieldName(), '?3'));

        $qb->setParameters(array(
            1 => $this->getRoot(),
            2 => $this->getLeftValue(),
            3 => $this->getRightValue()
        ));

        if ($depth !== null && $depth > 0) {
            $andX->add($expr->lte('e.' . $config->getLevelFieldName(), '?4'));
            $qb->setParameter(4, $this->getLevel() + $depth);
        }

        $qb->where($andX);

        return $qb;
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

        if ($entity->getRoot() !== null) {
            throw new HierarchicalException('This entity is already initialized and can not be made a root node');
        }

        $entity->setLevel(0);
        $entity->setRoot(null);
        $entity->setLeftValue(1);
        $entity->setRightValue(2);

        $this->getEntityManager()->persist($entity);
    }

    public function insertAsLastChildOf(Node $node)
    {
        $hm = $this->getHierarchicalManager();
        $em = $hm->getEntityManager();

        // Update the current node
        $this->setLevel($node->getLevel() + 1);
        $this->setRoot($node->getRoot());
        $this->setLeftValue($node->getRightValue());
        $this->setRightValue($node->getRightValue() + 1);

        $em->persist($this->unwrap());

        // Retrieving ancestors
        $ancestors = $hm->getNodes($node->getAncestors());

        // Updating parent node
        $node->setRightValue($node->getRightValue() + 2);

        $em->persist($node->unwrap());

        // Updating ancestors
        foreach ($ancestors as $ancestor) {
            $ancestor->setRightValue($ancestor->getRightValue() + 2);
            $em->persist($ancestor->unwrap());
        }
    }

    public function insertAsFirstChildOf(Node $node)
    {
        $hm = $this->getHierarchicalManager();
        $em = $hm->getEntityManager();

        // Update the current node
        $this->setLevel($node->getLevel() + 1);
        $this->setRoot($node->getRoot());
        $this->setLeftValue($node->getLeftValue() - 1);
        $this->setRightValue($node->getLeftValue());

        $em->persist($this->unwrap());

        // Retrieving ancestors
        $ancestors = $hm->getNodes($node->getAncestors());

        // Updating parent node
        $node->setLeftValue($node->getLeftValue() - 2);

        $em->persist($node->unwrap());

        // Updating ancestors
        foreach ($ancestors as $ancestor) {
            $ancestor->setLeftValue($ancestor->getLeftValue() - 2);
            $em->persist($ancestor->unwrap());
        }
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

    public function getId()
    {
        return $this->unwrap()->getId();
    }

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
        if ($this->hasParent()) {
            return $this->unwrap()->getRoot();
        }
        return $this->getId();
    }

    public function setRoot($value)
    {
        $this->unwrap()->setRoot($value);
    }
}