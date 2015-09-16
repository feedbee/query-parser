<?php

namespace QueryParser\Parser\Expression;

class Literal extends Expression
{
    private $string = "";

    public function __construct($string = "")
    {
        $this->string = $string;
    }

    public function appendString($string)
    {
        $this->string .= $string;
    }

    public function isEqualWith(Expression $expression)
    {
        return $expression instanceof static && (string)$expression == (string)$this;
    }

    public function __toString()
    {
        return $this->string;
    }

    public function dump()
    {
        return "l\"{$this}\"";
    }

    public function isEmpty()
    {
        return strlen($this->string) == 0 ? true : false;
    }
}