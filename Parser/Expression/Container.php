<?php

namespace Parser\Expression;

class Container extends Expression implements CollectionInterface
{
	/**
	 * @var Expression[]
	 */
	private $childNodes = array();

	public function __construct(array $nodes = array()) {
		$this->childNodes = $nodes;
	}

	public function addChild(Expression $expression)
	{
		$this->childNodes[] = $expression;
	}

	public function getLastChild()
	{
		return end($this->childNodes);
	}

	public function getChildNodes()
	{
		return $this->childNodes;
	}

	public function replaceNode($key, Expression $node)
	{
		if (!is_array($node)) {
			$node = array($node);
		}
		array_splice($this->childNodes, $key, 1, $node);
	}

	public function isEqualWith(Expression $expression)
	{
		if (!$expression instanceof static) {
			return false;
		}

		$nodes2 = $expression->getChildNodes();
		foreach ($this->childNodes as $key => $node) {
			if (!isset($nodes2[$key])) {
				return false;
			}
			$node2 = $nodes2[$key];
			if (!$node->isEqualWith($node2)) {
				return false;
			}
		}

		return true;
	}

	public function __toString()
	{
		return implode(' ', $this->childNodes);
	}

	public function dump()
	{
		return '{·}';
	}

	public function getIterator() {
		return new \ArrayIterator($this->childNodes);
	}


	public function offsetSet($offset, $value)
	{
		if (is_null($offset)) {
			$this->childNodes[] = $value;
		} else {
			$this->childNodes[$offset] = $value;
			$this->childNodes = array_values($this->childNodes);
		}
	}

	public function offsetExists($offset)
	{
		return isset($this->childNodes[$offset]);
	}

	public function offsetUnset($offset)
	{
		unset($this->childNodes[$offset]);
		$this->childNodes = array_values($this->childNodes);
	}

	public function offsetGet($offset)
	{
		return isset($this->childNodes[$offset]) ? $this->childNodes[$offset] : null;
	}

	public function count()
	{
		return count($this->childNodes);
	}

    public function isEmpty()
    {
        return empty($this->childNodes);
    }
}