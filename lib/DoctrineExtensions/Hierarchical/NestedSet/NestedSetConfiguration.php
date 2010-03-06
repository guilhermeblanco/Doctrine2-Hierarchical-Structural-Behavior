<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

/**
 * TODO: Document this class (it's already finished!)
 */
class NestedSetConfiguration extends \DoctrineExtensions\Hierarchical\Configuration
{
    /**
     * @var string Left field name
     */
    protected $_leftFieldName = 'lft';

    /**
     * @var string Right field name
     */
    protected $_rightFieldName = 'rgt';

    /**
     * @var string Root field name
     */
    protected $_rootFieldName = 'root';

    /**
     * @var string Level field name
     */
    protected $_levelFieldName = 'level';

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