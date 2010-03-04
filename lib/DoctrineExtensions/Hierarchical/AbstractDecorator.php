<?php

namespace DoctrineExtensions\Hierarchical;


class AbstractDecorator
{
    protected $_configuration;

    protected $_className;

    protected $_entity;

    protected $_hm;

    public function __construct($entity, $hm)
    {
        $this->_className = get_class($entity);
        $this->_entity = $entity;
        $this->_hm = $hm;
    }

    public function unwrap()
    {
        return $this->_entity;
    }

    public function getConfiguration()
    {
        if ($this->_configuration === null) {
            $this->_configuration = $this->_hm->getClassConfiguration($this->_className);
        }

        return $this->_configuration;
    }

    public function getClassName()
    {
        return $this->_className;
    }

    public function getHierarchicalManager()
    {
        return $this->_hm;
    }

    protected function getEntityManager()
    {
        return $this->_hm->getEntityManager();
    }

    protected function getNode($entity)
    {
        return $this->_hm->getNode($entity);
    }

    // ...
}