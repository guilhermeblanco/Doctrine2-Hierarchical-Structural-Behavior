<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

interface NestedSetNodeInfo
{
    public function getId();

    public function getLeftValue();
    public function setLeftValue($value);

    public function getRightValue();
    public function setRightValue($value);

    public function getLevel();
    public function setLevel($value);

    public function getRoot();
    public function setRoot($value);
}