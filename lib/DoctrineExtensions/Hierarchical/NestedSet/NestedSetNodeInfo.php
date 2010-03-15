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
     * Retrieves the Entity left field name
     *
     * @return string
     */
    public function getLeftFieldName();

    /**
     * Retrieves the Entity right field name
     *
     * @return string
     */
    public function getRightFieldName();

    /**
     * Retrieves the Entity level field name
     *
     * @return string
     */
    public function getLevelFieldName();

    /**
     * Retrieves the Entity root_id field name
     *
     * @return string
     */
    public function getRootIdFieldName();

    /**
     * Retrieves the Entity parent_id field name
     *
     * @return string
     */
    public function getParentIdFieldName();
}