<?php

namespace Parser\Expression;

interface CollectionInterface extends \IteratorAggregate, \ArrayAccess, \Countable {
	public function __toString();
}