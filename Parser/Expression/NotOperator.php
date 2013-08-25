<?php

namespace Parser\Expression;

class NotOperator extends SimpleStandaloneOperator
{
	protected $priority = 1;
	protected $direction = self::DIRECTION_R2L;
	protected $type = self::TYPE_UNARY;
	static protected $symbol = '-';
}