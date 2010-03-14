<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

interface NestedSetNodeInfo
{
    /**
     * Retrieves the Entity identifier field name
     *
     * @return string 
     */
    public function getIdFieldName();

    /**
     * Retrieves the Entity Node left field name
     *
     * @return string
     */
    public function getLeftFieldName();

    /**
     * Retrieves the Entity Node right field name
     *
     * @return string
     */
    public function getRightFieldName();

    /**
     * Retrieves the Entity Node level field name
     *
     * @return string
     */
    public function getLevelFieldName();

    /**
     * Defines the Entity Node Root Node field name
     *
     * @return string
     */
    public function getRootFieldName();
}