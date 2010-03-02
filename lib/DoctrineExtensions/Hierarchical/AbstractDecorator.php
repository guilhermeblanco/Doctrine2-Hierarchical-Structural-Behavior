<?php

namespace DoctrineExtensions\Hierarchical;


class AbstractDecorator
{
    protected $entity;

    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    public function unwrap()
    {
        return $this->entity;
    }

    // ...
}