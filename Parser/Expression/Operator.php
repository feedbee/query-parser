<?php

namespace QueryParser\Parser\Expression;

abstract class Operator extends Expression
{
    const PRIORITY_MIN = 1;
    const PRIORITY_MAX = 3;
    const DIRECTION_L2R = 'left-to-right';
    const DIRECTION_R2L = 'right-to-left';
    const TYPE_UNARY = 'unary';
    const TYPE_BINARY = 'binary';
    protected $priority = null;
    protected $direction = null;
    protected $type = null;

    /**
     * @param Expression $expression
     * @throws \Exception
     * @return \QueryParser\Parser\Expression\Expression
     */
    static public function detectAndTransform(Expression $expression)
    {
        unset($expression); // PHPStorm IDE anti-warning hack
        throw new \Exception('Every operator class must override this static method', 2);
    }

    public function isEqualWith(Expression $expression)
    {
        return $expression instanceof static;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function getDirection()
    {
        return $this->direction;
    }

    public function getType()
    {
        return $this->type;
    }

    public function dump()
    {
        return "[$this] <po>";
    }

    public function isEmpty()
    {
        return true;
    }
}