<?php

namespace QueryParser\Parser\Expression;

interface CollectionInterface extends \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @return string
     */
    public function __toString();
}