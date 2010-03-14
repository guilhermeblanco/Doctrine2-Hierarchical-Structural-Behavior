<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

use DoctrineExtensions\Hierarchical\AbstractDecorator,
    DoctrineExtensions\Hierarchical\Node,
    DoctrineExtensions\Hierarchical\HierarchicalException,
    Doctrine\ORM\NoResultException;

/**
 * TODO: Isn't there a way to make this thing act as the entity it decorates? so it's easier to pass it along to the EM
 * I don't know a clean ay to achieve it
 */
class NestedSetDecorator extends AbstractDecorator implements Node, NestedSetNodeInfo
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

    public function getRootFieldName()
    {
        return $this->_entity->getRootFieldName();
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

    public function getChildren()
    {
        if ($this->_children) {
            return $this->_children;
        }

        return $this->getDescendants(1);
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
        $andX = $expr->andX()
            ->add($expr->eq('e.' . $this->getRootFieldName(), $this->_getValue($this->getRootFieldName())))
            ->add($expr->gt('e.' . $this->getRightFieldName(), $this->_getValue($this->getRightFieldName())))
            ->add($expr->lt('e.' . $this->getLeftFieldName(), $this->_getValue($this->getLeftFieldName())))
            ->add($expr->eq('e.' . $this->getLevelFieldName(), $this->_getValue($this->getLevelFieldName()) - 1));
        $qb->where($andX);

        $this->_parent = $this->_hm->getNode($qb->getQuery()->getSingleResult());

        return $this->_parent;
    }

    public function getDescendants($depth = 0)
    {
        if ( ! $this->hasChildren()) {
            return array();
        }

        $qb = $this->_getBaseQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX()
            ->add($expr->eq('e.' . $this->getRootFieldName(), $this->_getValue($this->getRootFieldName())))
            ->add($expr->gt('e.' . $this->getLeftFieldName(), $this->_getValue($this->getLeftFieldName())))
            ->add($expr->lt('e.' . $this->getRightFieldName(), $this->_getValue($this->getRightFieldName())));

        if ($depth > 0) {
            $andX->add($expr->lte(
                'e.' . $this->getLevelFieldName(), $this->_getValue($this->getLevelFieldName()) + $depth
            ));
        }

        $qb->where($andX)->orderBy('e.' . $this->getLeftFieldName(), 'ASC');

        $nodes = array_map(function ($entity) use ($this->_hm) {
            return $this->_hm->getNode($entity);
        }, $qb->getQuery()->getResult());

        return $nodes;
    }

    public function addChild($entity)
    {
        if ($entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        $newLeft  = $this->_getValue($this->getRightFieldName());
        $newRight = $newLeft + 1;
        $newRoot  = $this->_getValue($this->getRootFieldName());

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $node = $this->_hm->getNode($entity);
        $node->_setValue($this->getLevelFieldName(), $this->_getValue($this->getLevelFieldName()) + 1);
        $node->_setValue($this->getLeftFieldName(), $newLeft);
        $node->_setValue($this->getRightFieldName(), $newRight);
        $node->_setValue($this->getRootFieldName(), $newRoot);

        $this->_hm->getEntityManager()->persist($entity);

        return $node;
    }

    public function insertAsPrevSiblingOf(Node $node)
    {
        if ($node->_entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as sibling of itself.');
        }

        $newLeft  = $this->_getValue($this->getRightFieldName()) + 1;
        $newRight = $newLeft + 2;
        $newRoot  = $this->_getValue($this->getRootFieldName());

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $this->_setValue($this->getLevelFieldName(), $this->_getValue($this->getLevelFieldName()));
        $this->_setValue($this->getLeftFieldName(), $newLeft);
        $this->_setValue($this->getRightFieldName(), $newRight);
        $this->_setValue($this->getRootFieldName(), $newRoot);

        $this->_hm->getEntityManager()->persist($this->_entity);
    }

    public function insertAsNextSiblingOf($entity)
    {
       if ($entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as sibling of itself.');
        }

        $newLeft  = $this->_getValue($this->getLeftFieldName());
        $newRight = $newLeft + 1;
        $newRoot  = $this->_getValue($this->getRootFieldName());

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $this->_setValue($this->getLevelFieldName(), $this->_getValue($this->getLevelFieldName()));
        $this->_setValue($this->getLeftFieldName(), $newLeft);
        $this->_setValue($this->getRightFieldName(), $newRight);
        $this->_setValue($this->getRootFieldName(), $newRoot);

        $this->_hm->getEntityManager()->persist($this->_entity);
    }

    public function insertAsLastChildOf(Node $node)
    {
        if ($node->_entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        $newLeft  = $this->_getValue($this->getRightFieldName());
        $newRight = $newLeft + 1;
        $newRoot  = $this->_getValue($this->getRootFieldName());

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $this->_setValue($this->getLevelFieldName(), $this->_getValue($this->getLevelFieldName()) + 1);
        $this->_setValue($this->getLeftFieldName(), $newLeft);
        $this->_setValue($this->getRightFieldName(), $newRight);
        $this->_setValue($this->getRootFieldName(), $newRoot);

        $this->_hm->getEntityManager()->persist($this->_entity);
    }

    public function insertAsFirstChildOf(Node $node)
    {
       if ($entity === $this->_entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        $newLeft  = $this->_getValue($this->getLeftFieldName()) + 1;
        $newRight = $newLeft + 2;
        $newRoot  = $this->_getValue($this->getRootFieldName());

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $this->_setValue($this->getLevelFieldName(), $this->_getValue($this->getLevelFieldName()));
        $this->_setValue($this->getLeftFieldName(), $newLeft);
        $this->_setValue($this->getRightFieldName(), $newRight);
        $this->_setValue($this->getRootFieldName(), $newRoot);

        $this->_hm->getEntityManager()->persist($this->_entity);
    }

    public function delete()
    {
        $oldRoot = $this->_getValue($this->getRootFieldName());

        $qb = $this->_getBaseQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX()
            ->add($expr->eq('e.' . $this->getRootFieldName(), $this->_getValue($this->getRootFieldName())))
            ->add($expr->gte('e.' . $this->getLeftFieldName(), $this->_getValue($this->getLeftFieldName())))
            ->add($expr->lte('e.' . $this->getRightFieldName(), $this->_getValue($this->getRightFieldName())));
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
        $andX = $expr->andX()
            ->add($expr->eq('e.' . $this->getRootFieldName(), $this->_getValue($this->getRootFieldName())))
            ->add($expr->eq('e.' . $this->getLeftFieldName(), $this->_getValue($this->getLeftFieldName()) + 1));
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
        $andX = $expr->andX()
            ->add($expr->eq('e.' . $this->getRootFieldName(), $this->_getValue($this->getRootFieldName())))
            ->add($expr->eq('e.' . $this->getRightFieldName(), $this->_getValue($this->getRightFieldName()) - 1));
        $qb->where($andX);

        return $this->_hm->getNode($qb->getQuery()->getSingleResult());
    }

    protected function _shiftRLValues($first, $last, $delta, $root)
    {

    }


    /*public function getRootNodes($limit = null, $offset = 0)
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
            2 => $this->getLeft(),
            3 => $this->getRight()
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

    public function createRoot()
    {
        $entity = $this->unwrap();

        if ($entity->getRoot() !== null) {
            throw new HierarchicalException('This entity is already initialized and can not be made a root node');
        }

        $entity->setLevel(0);
        $entity->setRoot(null);
        $entity->setLeft(1);
        $entity->setRight(2);

        $this->getEntityManager()->persist($entity);
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
    }*/
}