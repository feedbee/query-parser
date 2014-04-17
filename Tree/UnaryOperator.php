<?php

namespace Tree;

use Parser\Expression\Container;

class UnaryOperator extends Operator
{
	public function extract()
	{
		return $this->parserOperator . '' . $this->operands[0];
	}
}