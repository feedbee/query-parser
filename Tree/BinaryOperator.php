<?php

namespace Tree;

use Parser\Expression\Container;

class BinaryOperator extends Operator
{
	public function extract()
	{
		return new Container(array($this->operands[0], $this->parserOperator, $this->operands[1]));
	}
}