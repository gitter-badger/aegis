<?php

namespace Aegis\Runtime\Node;

use Aegis\CompilerInterface;
use Aegis\Node;
use Aegis\ParserInterface;
use Aegis\Token;

class VariableNode extends Node
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public static function parse(ParserInterface $parser)
    {
        if ($parser->accept(Token::T_VAR)) {
            $parser->insert(new static($parser->getCurrentToken()->getValue()));
            $parser->advance();

            return true;
        }

        return false;
    }

    public function compile(CompilerInterface $compiler, $local = false)
    {
        if ($local) {
            $compiler->write('$'.str_replace('.', '->', $this->name));
        } else {
            $compiler->write('$env->'.str_replace('.', '->', $this->name));
        }
    }
}
