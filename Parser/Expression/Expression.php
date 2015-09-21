<?php

namespace QueryParser\Parser\Expression;

abstract class Expression
{
    abstract public function isEqualWith(Expression $expression);

    abstract public function dump();

    abstract public function __toString();
}