<?php

namespace DoctrineExtensions\Hierarchical;

class HierarchicalException extends \Exception
{
    public static function couldNotFindClassConfiguration($className)
    {
        return new self("Could not find Configuration for Class '$className'.");
    }
}