<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

interface NestedSetNodeInfo
{
    /**
     * Retrieves the Entity identifier
     *
     * @return mixed
     */
    public function getId();

    /**
     * Retrieves the Entity Node left value
     *
     * @return integer Node left value
     */
    public function getLeft();

    /**
     * Defines the Entity Node left value
     *
     * @param integer $value Node left value
     */
    public function setLeft($value);

    /**
     * Retrieves the Entity Node right value
     *
     * @return integer Node right value
     */
    public function getRight();

    /**
     * Defines the Entity Node right value
     *
     * @param integer $value Node right value
     */
    public function setRight($value);

    /**
     * Retrieves the Entity Node level
     *
     * @return integer Node level
     */
    public function getLevel();

    /**
     * Defines the Entity Node level
     *
     * @param integer $value Node level
     */
    public function setLevel($value);

    /**
     * Defines the Entity Node Root Node
     *
     * @param integer $value Node Root Node
     */
    public function getRoot();

    /**
     * Defines the Entity Node Root Node
     *
     * @param integer $value Node Root Node
     */
    public function setRoot($value);
}