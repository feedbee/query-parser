<?php

namespace QueryParser\Parser\Expression;

class NotOperator extends SimpleStandaloneOperator
{
    static protected $symbol = '-';
    protected $priority = 1;
    protected $direction = self::DIRECTION_R2L;
    protected $type = self::TYPE_UNARY;
}