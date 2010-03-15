<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

use DoctrineExtensions\Hierarchical\AbstractDecorator,
    DoctrineExtensions\Hierarchical\Node,
    DoctrineExtensions\Hierarchical\HierarchicalException,
    Doctrine\ORM\NoResultException;

/**
 * TODO comment/finish this class
 *
 * TODO if you want to return Nodes in getChildren and others,
 *      we need a custom iterator class that can fetch items from
 *      the result set and turn them into nodes on the fly, we can
 *      not just fetch everything at once imo memory wise it's bad,
 *      and imo it should be optional anyway, or just never do it,
 *      because when you fetch nodes to display them, you don't care
 *      about having this decorator around each of them. Also that is
 *      why I added the HierarchicalManager::getNodes() function, this
 *      should be up to the user imo.
 *
 * TODO all inserts/move methods should read the left/right values
 *      from the parent from the DB and not the given node, otherwise
 *      we might have race conditions that break the tree
 *
 * TODO getIdFieldName and others should be cached imo, and lazy-loaded
 *      by _getBaseQueryBuilder if they are not filled in
 */
class NestedSetDecorator extends AbstractDecorator implements Node
{
    protected $_baseQuery;

    protected $_parent;

    protected $_children;

    // Delegate support for Decorator object

    public function getIdFieldName()
    {
        return $this->_entity->getIdFieldName();
    }

    public function getLeftFieldName()
    {
        return $this->_entity->getLeftFieldName();
    }

    public function getRightFieldName()
    {
        return $this->_entity->getRightFieldName();
    }

    public function getLevelFieldName()
    {
        return $this->_entity->getLevelFieldName();
    }

    public function getRootIdFieldName()
    {
        return $this->_entity->getRootIdFieldName();
    }

    public function getParentIdFieldName()
    {
        return $this->_entity->getParentIdFieldName();
    }

    // End of delegate support of Decorator object

    protected function _getBaseQueryBuilder()
    {
        if ($this->_baseQuery === null) {
            $this->_baseQuery = $this->_hm->getEntityManager()->createQueryBuilder()
                ->select('e')
                ->from($this->_class->name, 'e');
        }

        return $this->_baseQuery;
    }

    public function hasChildren()
    {
        $rightValue = $this->_getValue($this->getRightFieldName());
        $leftValue  = $this->_getValue($this->getLeftFieldName());

        return ($rightValue - $leftValue) > 1;
    }

    public function hasParent()
    {
        return $this->_getValue($this->getLevelFieldName()) != 0;
    }

    public function isRoot()
    {
        return ! $this->hasParent();
    }

    public function isLeaf()
    {
        return ! $this->hasChildren();
    }

    public function isValid()
    {
        $rightValue = $this->_getValue($this->getRightFieldName());
        $leftValue  = $this->_getValue($this->getLeftFieldName());

        return ($rightValue > $leftValue);
    }

    public function getNumberOfChildren()
    {
        return count($this->getChildren());
    }

    public function getNumberOfDescendants()
    {
        $rightValue = $this->_getValue($this->getRightFieldName());
        $leftValue  = $this->_getValue($this->getLeftFieldName());

        return ($rightValue - $leftValue - 1) / 2;
    }

    public function getChildren($limit = null, $offset = 0, $order = 'ASC')
    {
        if ($this->_children) {
            return $this->_children;
        }

        return $this->_children = $this->getDescendants(1, $limit, $offset, $order);
    }

    public function getParent()
    {
        if ( ! $this->hasParent()) {
            return null;
        }

        if ($this->_parent) {
            return $this->_parent;
        }

        $qb = $this->_getBaseQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();
        $andX->add($expr->eq('e.' . $this->getRootIdFieldName(), $this->_getValue($this->getRootIdFieldName())));
        $andX->add($expr->gt('e.' . $this->getRightFieldName(), $this->_getValue($this->getRightFieldName())));
        $andX->add($expr->lt('e.' . $this->getLeftFieldName(), $this->_getValue($this->getLeftFieldName())));
        $andX->add($expr->eq('e.' . $this->getLevelFieldName(), $this->_getValue($this->getLevelFieldName()) - 1));
        $qb->where($andX);

        $this->_parent = $this->_hm->getNode($qb->getQuery()->getSingleResult());

        return $this->_parent;
    }

    public function getDescendants($depth = 0, $limit = null, $offset = 0, $order = 'ASC')
    {
        if ( ! $this->hasChildren()) {
            return array();
        }

        $qb = $this->_getBaseQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();
        $andX->add($expr->eq('e.' . $this->getRootIdFieldName(), $this->_getValue($this->getRootIdFieldName())));
        $andX->add($expr->gt('e.' . $this->getLeftFieldName(), $this->_getValue($this->getLeftFieldName())));
        $andX->add($expr->lt('e.' . $this->getRightFieldName(), $this->_getValue($this->getRightFieldName())));

        if ($depth > 0) {
            $andX->add($expr->lte(
                'e.' . $this->getLevelFieldName(), $this->_getValue($this->getLevelFieldName()) + $depth
            ));
        }

        $qb->where($andX)->orderBy('e.' . $this->getLeftFieldName(), $order);

        $q = $qb->getQuery();
        if ($limit !== null) {
            $q->setMaxResults((int) $limit);
        }
        if ($offset) {
            $q->setFirstResult((int) $offset);
        }

        return $q->getResult();
    }

    public function addChild($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        $newLeft  = $this->_getValue($this->getRightFieldName());
        $newRight = $newLeft + 1;
        $newRoot  = $this->_getValue($this->getRootIdFieldName());

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $this->_class->reflFields[$this->getLevelFieldName()]->setValue($entity, $this->_getValue($this->getLevelFieldName()) + 1);
        $this->_class->reflFields[$this->getLeftFieldName()]->setValue($entity, $newLeft);
        $this->_class->reflFields[$this->getRightFieldName()]->setValue($entity, $newRight);
        $this->_class->reflFields[$this->getRootIdFieldName()]->setValue($entity, $newRoot);
        $this->_class->reflFields[$this->getParentIdFieldName()]->setValue($entity, $this->_getValue($this->getIdFieldName()));

        $this->_hm->getEntityManager()->persist($entity);

        return $node;
    }

    public function insertAsPrevSiblingOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as sibling of itself.');
        }

        $newLeft = $this->_class->reflFields[$this->getLeftFieldName()]->getValue($entity);
        $newRight = $newLeft + 1;
        $newLevel = $this->_class->reflFields[$this->getLevelFieldName()]->getValue($entity);
        $newParent = $this->_class->reflFields[$this->getParentIdFieldName()]->getValue($entity);
        $newRoot = $this->_class->reflFields[$this->getRootIdFieldName()]->getValue($entity);
        if (!$newRoot) {
            throw new \IllegalArgumentException('You can not add a sibling to a root node');
        }

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $this->_setValue($this->getLevelFieldName(), $newLevel);
        $this->_setValue($this->getLeftFieldName(), $newLeft);
        $this->_setValue($this->getRightFieldName(), $newRight);
        $this->_setValue($this->getRootIdFieldName(), $newRoot);
        $this->_setValue($this->getParentIdFieldName(), $newParent);

        $this->_hm->getEntityManager()->persist($this->_entity);
    }

    public function insertAsNextSiblingOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as sibling of itself.');
        }

        $newLeft = $this->_class->reflFields[$this->getLeftFieldName()]->getValue($entity) + 2;
        $newRight = $newLeft + 1;
        $newLevel = $this->_class->reflFields[$this->getLevelFieldName()]->getValue($entity);
        $newParent = $this->_class->reflFields[$this->getParentIdFieldName()]->getValue($entity);
        $newRoot = $this->_class->reflFields[$this->getRootIdFieldName()]->getValue($entity);
        if (!$newRoot) {
            throw new \IllegalArgumentException('You can not add a sibling to a root node');
        }

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $this->_setValue($this->getLevelFieldName(), $newLevel);
        $this->_setValue($this->getLeftFieldName(), $newLeft);
        $this->_setValue($this->getRightFieldName(), $newRight);
        $this->_setValue($this->getRootIdFieldName(), $newRoot);
        $this->_setValue($this->getParentIdFieldName(), $newParent);

        $this->_hm->getEntityManager()->persist($this->_entity);
    }

    public function insertAsLastChildOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        $newLeft = $this->_class->reflFields[$this->getLeftFieldName()]->getValue($entity);
        $newRight = $newLeft + 1;
        $newLevel = $this->_class->reflFields[$this->getLevelFieldName()]->getValue($entity) + 1;
        $newParent = $this->_class->reflFields[$this->getParentIdFieldName()]->getValue($entity);
        $newRoot = $this->_class->reflFields[$this->getRootIdFieldName()]->getValue($entity);
        if (!$newRoot) {
            $newRoot = $this->_class->reflFields[$this->getIdFieldName()]->getValue($entity);
        }

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $this->_setValue($this->getLevelFieldName(), $newLevel);
        $this->_setValue($this->getLeftFieldName(), $newLeft);
        $this->_setValue($this->getRightFieldName(), $newRight);
        $this->_setValue($this->getRootIdFieldName(), $newRoot);
        $this->_setValue($this->getParentIdFieldName(), $newParent);

        $this->_hm->getEntityManager()->persist($this->_entity);
    }

    public function insertAsFirstChildOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        $newLeft = $this->_class->reflFields[$this->getLeftFieldName()]->getValue($entity) + 1;
        $newRight = $newLeft + 1;
        $newLevel = $this->_class->reflFields[$this->getLevelFieldName()]->getValue($entity) + 1;
        $newParent = $this->_class->reflFields[$this->getParentIdFieldName()]->getValue($entity);
        $newRoot = $this->_class->reflFields[$this->getRootIdFieldName()]->getValue($entity);
        if (!$newRoot) {
            $newRoot = $this->_class->reflFields[$this->getIdFieldName()]->getValue($entity);
        }

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $this->_setValue($this->getLevelFieldName(), $newLevel);
        $this->_setValue($this->getLeftFieldName(), $newLeft);
        $this->_setValue($this->getRightFieldName(), $newRight);
        $this->_setValue($this->getRootIdFieldName(), $newRoot);
        $this->_setValue($this->getParentIdFieldName(), $newParent);

        $this->_hm->getEntityManager()->persist($this->_entity);
    }

    public function delete()
    {
        $oldRoot = $this->_getValue($this->getRootIdFieldName());

        $qb = $this->_getBaseQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();
        $andX->add($expr->eq('e.' . $this->getRootIdFieldName(), $this->_getValue($this->getRootIdFieldName())));
        $andX->add($expr->gte('e.' . $this->getLeftFieldName(), $this->_getValue($this->getLeftFieldName())));
        $andX->add($expr->lte('e.' . $this->getRightFieldName(), $this->_getValue($this->getRightFieldName())));
        $qb->delete()->where($andX);

        $qb->getQuery()->execute();

        $first = $this->_getValue($this->getLeftFieldName());
        $delta = $leftValue - $this->_getValue($this->getRightFieldName()) - 1;
        $this->_shiftRLValues($first, 0, $delta, $oldRoot);
    }

    public function getFirstChild()
    {
        if ($this->_children) {
            if (count($this->_children) > 0) {
                return $this->_children[0];
            }

            throw new NoResultException;
        }

        $qb = $this->_getBaseQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();
        $andX->add($expr->eq('e.' . $this->getRootIdFieldName(), $this->_getValue($this->getRootIdFieldName())));
        $andX->add($expr->eq('e.' . $this->getLeftFieldName(), $this->_getValue($this->getLeftFieldName()) + 1));
        $qb->where($andX);

        return $this->_hm->getNode($qb->getQuery()->getSingleResult());
    }

    public function getLastChild()
    {
        if ($this->_children) {
            if (count($this->_children) > 0) {
                return end($this->_children);
            }

            throw new NoResultException;
        }

        $qb = $this->_getBaseQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();
        $andX->add($expr->eq('e.' . $this->getRootIdFieldName(), $this->_getValue($this->getRootIdFieldName())));
        $andX->add($expr->eq('e.' . $this->getRightFieldName(), $this->_getValue($this->getRightFieldName()) - 1));
        $qb->where($andX);

        return $this->_hm->getNode($qb->getQuery()->getSingleResult());
    }

    protected function _shiftRLValues($first, $last, $delta, $root)
    {
        // TODO implement
    }

    public function getRootNodes($limit = null, $offset = 0, $order = 'ASC')
    {
        $qb = $this->_getBaseQueryBuilder();
        $qb->where('e.' . $this->getRootIdFieldName() . ' IS NULL');
        $qb->orderBy('e.' . $this->getLeftFieldName(), $order);
        $q = $qb->getQuery();

        if ($limit !== null) {
            $q->setMaxResults((int) $limit);
        }
        if ($offset) {
            $q->setFirstResult((int) $offset);
        }

        return $q->getResult();
    }

    public function getAncestors($depth = null, $limit = null, $offset = 0, $order = 'ASC')
    {
        if ( ! $this->hasChildren()) {
            return array();
        }

        $qb = $this->_getBaseQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();
        $andX->add($expr->eq('e.' . $this->getRootIdFieldName(), $this->_getValue($this->getRootIdFieldName())));
        $andX->add($expr->lt('e.' . $this->getLeftFieldName(), $this->_getValue($this->getLeftFieldName())));
        $andX->add($expr->gt('e.' . $this->getRightFieldName(), $this->_getValue($this->getRightFieldName())));

        if ($depth > 0) {
            $andX->add($expr->lte(
                'e.' . $this->getLevelFieldName(), $this->_getValue($this->getLevelFieldName()) - $depth
            ));
        }

        $qb->where($andX)->orderBy('e.' . $this->getLeftFieldName(), $order);

        $q = $qb->getQuery();
        if ($limit !== null) {
            $q->setMaxResults((int) $limit);
        }
        if ($offset) {
            $q->setFirstResult((int) $offset);
        }

        return $q->getResult();
    }

    public function createRoot()
    {
        if ($this->_getValue($this->getRootIdFieldName())) {
            throw new HierarchicalException('This entity is already initialized and can not be made a root node');
        }

        $this->_setValue($this->getLevelFieldName(), 0);
        $this->_setValue($this->getLeftFieldName(), 1);
        $this->_setValue($this->getRightFieldName(), 2);
        $this->_setValue($this->getRootIdFieldName(), null);
        $this->_setValue($this->getParentIdFieldName(), null);

        $this->_hm->getEntityManager()->persist($this->_entity);
    }

    public function moveAsLastChildOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        // TODO implement
    }

    public function moveAsFirstChildOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        // TODO implement
    }

    public function moveAsNextSiblingOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        // TODO implement
    }

    public function moveAsPrevSiblingOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        // TODO implement
    }
}