<?php

namespace QueryParser\Tree;

use QueryParser\Parser\Expression\Expression;
use QueryParser\Parser\Expression\Operator as ParserOperator;

abstract class Operator extends Expression
{
    protected $operands;
    protected $parserOperator;

    public function __construct(ParserOperator $parserOperator, array $operands)
    {
        $this->setParserOperator($parserOperator);
        $this->setOperands($operands);
    }

    public function setParserOperator(ParserOperator $parserOperator)
    {
        $this->parserOperator = $parserOperator;
    }

    public function getParserOperator()
    {
        return $this->parserOperator;
    }

    public function setOperands(array $operands)
    {
        $this->operands = $operands;
    }

    public function getOperands()
    {
        return $this->operands;
    }

    abstract public function extract();

    public function __toString()
    {
        return (string)($this->extract());
    }

    public function dump()
    {
        return "[{$this->getParserOperator()}] <to>";
    }

    public function isEqualWith(Expression $expression)
    {
        return (string)$expression == (string)$this;
    }
}