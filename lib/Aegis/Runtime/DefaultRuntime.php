<?php

namespace Aegis\Runtime;

use Aegis\NodeRegistry;
use Aegis\RuntimeInterface;

class DefaultRuntime implements RuntimeInterface
{
    private $vars = [];
    private $blocks = [];
    public $functions = [];

    public function __construct()
    {
        NodeRegistry::register([
            'Aegis\\Runtime\\Node\\IfNode',
            'Aegis\\Runtime\\Node\\ForNode',
            'Aegis\\Runtime\\Node\\BlockNode',
            'Aegis\\Runtime\\Node\\ExtendNode',
            'Aegis\\Runtime\\Node\\IncludeNode',
            'Aegis\\Runtime\\Node\\PrintNode',
            'Aegis\\Runtime\\Node\\RawNode',
        ]);
    }

    public function set($k, $v)
    {
        $this->vars[$k] = $v;
    }

    public function __get($k)
    {
        if (!isset($this->vars[$k])) {
            throw new UndefinedVariable($k);
        }

        return $this->vars[$k];
    }

    public function setBlock($id, $callable)
    {
        $this->blocks[$id] = [$callable];
    }

    public function appendBlock($id, $callable)
    {
        $this->blocks[$id][] = $callable;
    }

    public function prependBlock($id, $callable)
    {
        array_unshift($this->blocks[$id], $callable);
    }

    public function getBlock($id)
    {
        foreach ($this->blocks[$id] as $callable) {
            $callable();
        }
    }

    public function setFunction($funcName, $callable)
    {
        $this->functions[$funcName] = $callable;
    }
}
