<?php

namespace Aegis;

abstract class Node
{
    private $parent = null;
    private $children = [];
    private $attributes = [];
    private $isAttribute;

    public function setAttribute(Node $n)
    {
        $n->isAttribute = true;
        $this->attributes[] = $n;
    }

    public function isAttribute()
    {
        return $this->isAttribute;
    }

    public function getAttribute($i)
    {
        return  isset($this->attributes[ $i ]) ? $this->attributes[ $i ] : null;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(Node $parent)
    {
    	$this->parent = $parent;
    }

    public function getChild($i)
    {
        return $this->children[ $i ];
    }

    public function getLastChild()
    {
        return end($this->children);
    }

    public function removeChild($i)
    {
        unset($this->children[ $i ]);
    }

    public function removeLastChild()
    {
        array_pop($this->children);
    }

    public function getSiblings()
    {
        return $this->parent->children;
    }

    public function insert(Node $node)
    {
        $this->children[] = $node;
    }

    public function getName()
    {
        return get_class($this);
    }

    abstract public function compile(CompilerInterface $compiler);
}
