<?php

namespace Parser\Expression;

class SimpleStandaloneOperator extends Operator
{
	static protected $symbol = null;

	static public function detectAndTransform(Expression $expression)
	{
		if (is_null(static::$symbol)) {
			throw new \Exception('Operator symbol must be declared in successor-classes', 3);
		}
		if ($expression instanceof Literal) {
			$string = ((string)$expression);
			if ($string === static::$symbol) {
				return new static();
			}
		}

		return $expression;
	}

	public function __toString()
	{
		return (string)static::$symbol;
	}
}