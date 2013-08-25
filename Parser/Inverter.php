<?php

namespace Parser;

use \Parser\Expression\CollectionInterface;

class Inverter implements \IteratorAggregate, \ArrayAccess, \Countable
{
	/**
	 * @var \Parser\Expression\CollectionInterface
	 */
	private $collection;

	public function __construct(CollectionInterface $collection)
	{
		$this->collection = $collection;
	}

	private function invert($index)
	{
		return count($this->collection) - $index - 1;
	}

	public function __toString()
	{
		return $this->collection->__toString();
	}

	public function getIterator() {
		return $this->collection->getIterator();
	}


	public function offsetSet($offset, $value)
	{
		$this->collection->offsetSet($this->invert($offset), $value);
	}

	public function offsetExists($offset)
	{
		return $this->collection->offsetExists($this->invert($offset));
	}

	public function offsetUnset($offset)
	{
		$this->collection->offsetUnset($this->invert($offset));
	}

	public function offsetGet($offset)
	{
		return $this->collection->offsetGet($this->invert($offset));
	}

	public function count()
	{
		return $this->collection->count();
	}
}