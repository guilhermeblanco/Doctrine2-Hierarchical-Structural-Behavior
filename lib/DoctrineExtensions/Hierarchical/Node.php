<?php

namespace DoctrineExtensions\Hierarchical;


interface Node
{
    public function __construct($entity, $hm);

    public function hasChildren();
    public function hasParent();
    public function isRoot();
    public function isLeaf();

    public function unwrap();

    public function getRootNodes($limit = null, $offset = 0, $order = 'ASC');
    public function getChildren($limit = null, $offset = 0, $order = 'ASC');
    public function getParent();
    public function getFirstChild();
    public function getLastChild();
    public function getNumberOfChildren();
    public function getNumberOfDescendants();

    public function getAncestors($depth = null, $limit = null, $offset = 0, $order = 'ASC');
    public function getDescendants($depth = null, $limit = null, $offset = 0, $order = 'ASC');

    public function delete();

    public function addChild($entity);
    public function createRoot();

    public function insertAsLastChildOf($entity);
    public function insertAsFirstChildOf($entity);
    public function insertAsNextSiblingOf($entity);
    public function insertAsPrevSiblingOf($entity);
    public function moveAsLastChildOf($entity);
    public function moveAsFirstChildOf($entity);
    public function moveAsNextSiblingOf($entity);
    public function moveAsPrevSiblingOf($entity);
}