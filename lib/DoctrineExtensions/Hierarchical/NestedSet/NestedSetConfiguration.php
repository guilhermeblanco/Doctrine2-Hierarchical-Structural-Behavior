<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

/**
 * TODO: Document this class (it's already finished!)
 */
class NestedSetConfiguration extends DoctrineExtensions\Hierarchical\Configuration
{
    /**
     * @var string Left field name
     */
    private $_leftFieldName;

    /**
     * @var string Right field name
     */
    private $_rightFieldName;

    /**
     * @var string Root field name
     */
    private $_rootFieldName;

    /**
     * @var string Level field name
     */
    private $_levelFieldName;

    public function setLeftFieldName($fieldName)
    {
        $this->_leftFieldName = $fieldName;
    }

    public function getLeftFieldName()
    {
        return $this->_leftFieldName;
    }

    public function setRightFieldName($fieldName)
    {
        $this->_rightFieldName = $fieldName;
    }

    public function getRightFieldName()
    {
        return $this->_rightFieldName;
    }

    public function setRootFieldName($fieldName)
    {
        $this->_rootFieldName = $fieldName;
    }

    public function getRootFieldName()
    {
        return $this->_rootFieldName;
    }

    public function setLevelFieldName($fieldName)
    {
        $this->_levelFieldName = $fieldName;
    }

    public function getLevelFieldName()
    {
        return $this->_levelFieldName;
    }
}