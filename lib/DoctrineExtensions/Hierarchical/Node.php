<?php

namespace DoctrineExtensions\Hierarchical;


interface Node
{
    public function __construct($entity, $hm);

    public function hasPrevSibling();
    public function hasNextSibling();
    public function hasChildren();
    public function hasParent();
    public function isRoot();
    public function isLeaf();

    public function unwrap();

    public function getPrevSibling();
    public function getNextSibling();
    public function getChildren();
    public function getParent();
    public function getFirstChild();
    public function getLastChild();
    public function getNumberOfChildren();
    public function getNumberOfDescendants();

    public function getDescendants($depth = null);

    public function delete();

    public function addChild(Node $node);
    public function createRoot();

    public function insertAsLastChildOf(Node $node);
    public function insertAsFirstChildOf(Node $node);
    public function insertAsNextSiblingOf(Node $node);
    public function insertAsPrevSiblingOf(Node $node);
    public function moveAsLastChildOf(Node $node);
    public function moveAsFirstChildOf(Node $node);
    public function moveAsNextSiblingOf(Node $node);
    public function moveAsPrevSiblingOf(Node $node);
}