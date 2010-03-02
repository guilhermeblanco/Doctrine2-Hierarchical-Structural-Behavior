<?php

namespace DoctrineExtensions\Hierarchical;


class AbstractDecorator
{
    protected $className;

    protected $entity;

    protected $hm;

    public function __construct($entity, $hm)
    {
        $this->className = get_class($entity);
        $this->entity = $entity;
        $this->hm = $hm;
    }

    public function unwrap()
    {
        return $this->entity;
    }

    public function getHierarchicalManager()
    {
        return $this->hm;
    }

    // ...
}