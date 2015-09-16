<?php

namespace QueryParser\Parser\Expression;

class OrOperator extends SimpleStandaloneOperator
{
    static protected $symbol = '|';
    protected $priority = 3;
    protected $direction = self::DIRECTION_L2R;
    protected $type = self::TYPE_BINARY;
}