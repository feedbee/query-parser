<?php

namespace Parser\Expression;

class Group extends Container
{
	public function __toString()
	{
		return '(' . parent::__toString() . ')';
	}

	public function dump()
	{
		return '(·)';
	}
}