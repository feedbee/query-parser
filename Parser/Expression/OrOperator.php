<?php

namespace Parser\Expression;

class OrOperator extends SimpleStandaloneOperator
{
	protected $priority = 3;
	protected $direction = self::DIRECTION_L2R;
	protected $type = self::TYPE_BINARY;
	static protected $symbol = '|';
}