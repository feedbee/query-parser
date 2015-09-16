<?php

namespace QueryParser\Parser\Expression;

class Phrase extends Literal
{
    public function __toString()
    {
        return '"' . parent::__toString() . '"';
    }

    public function dump()
    {
        return "p{$this}";
    }
}