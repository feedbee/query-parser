<?php

namespace QueryParser\Tree;

class UnaryOperator extends Operator
{
    public function extract()
    {
        return $this->parserOperator . '' . $this->operands[0];
    }
}