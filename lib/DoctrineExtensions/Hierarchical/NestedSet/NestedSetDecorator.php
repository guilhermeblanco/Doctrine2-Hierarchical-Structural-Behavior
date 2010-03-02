<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

use DoctrineExtensions\Hierarchical\Node;

class NestedSetDecorator extends AbstractDecorator implements Node, NestedSetNodeInfo
{
    public function hasChildren()
    {
        $rgtValue = $this->_getEntityValue('rightFieldName');
        $lftValue = $this->_getEntityValue('leftFieldName');

        return ($rgtValue - $lftValue) > 1;
    }

    // ...
}