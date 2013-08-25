<?php

namespace Tree;

use Parser\Expression\Container;

class UnaryOperator extends Operator
{
	public function extract()
	{
		return new Container(array($this->parserOperator, $this->operands[0]));
	}
}