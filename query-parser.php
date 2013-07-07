<?php

class Parser
{
	const STATE_NULL = null;
	const STATE_LITERAL = 1;
	const STATE_PHRASE = 2;
	public static function parse($string)
	{
		$stack = array();
		$stackExpr = array();
		$state = self::STATE_NULL;
		$string = self::adapt($string);
		$expression = new Container;

		for ($i = 0; $i < mb_strlen($string); $i++) {
			$char = mb_substr($string, $i, 1);

			if ($state == self::STATE_PHRASE && $char != '"') {
				$expression->getLastChild()->appendString($char);
			} else {
				switch ($char) {
					case ' ':
						$state = self::STATE_NULL;
						break;

					case '"':
						if (count($stack) > 0 && end($stack) == '"') {
							// закрываем фразу
							$state = self::STATE_NULL;
							array_pop($stack);
						} else {
							// открываем фразу
							$expression->addChild($ph = new Phrase);
							$state = self::STATE_PHRASE;
							array_push($stack, $char);
						}
						break;

					case '(':
						// открываем выражение
						$expression->addChild($cnt = new Container);
						$state = self::STATE_NULL;
						array_push($stack, ')');
						array_push($stackExpr, $expression);
						$expression = $cnt;
						break;

					case ')':
						if (count($stack) > 0 && end($stack) == ')') {
							// есть что закрыть — закрываем выражение
							$expression = array_pop($stackExpr);
							$state = self::STATE_NULL;
							array_pop($stack);
						} else {
							// скобка "не в тему"
							$expression->getLastChild()->appendString($char);
						}
						break;

					default:
						if ($state == self::STATE_NULL) {
							$expression->addChild(new Literal);
							$state = self::STATE_LITERAL;
						}
						$expression->getLastChild()->appendString($char);
						break; 
				}
			}
		}

		if (count($stackExpr) > 0) {
			return $stackExpr[0];
		}

		return $expression;
	}

	private static function adapt($string)
	{
		return preg_replace('/\s+/', ' ', $string); // replace any count of any space characted with single space
	}
}

abstract class Expression {
	abstract public function isEqualWith(Expression $expression);
}

class Container extends Expression {
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
		if (count($this->childNodes) < 2) {
			return implode(' ', $this->childNodes);
		}
		return '(' . implode(' ', $this->childNodes) . ')';
	}
}

class Literal extends Expression {
	private $string = "";

	public function __construct($string = "")
	{
		$this->string = $string;
	}

	public function appendString($string)
	{
		$this->string .= $string;
	}

	public function isEqualWith(Expression $expression)
	{
		return $expression instanceof static && (string)$expression == (string)$this;
	}

	public function __toString()
	{
		return $this->string;
	}
}

class Phrase extends Literal {
	public function __toString()
	{
		return '"' . parent::__toString() . '"';
	}
}

class Operator extends Expression {
	private $priority = 0;
	public function isEqualWith(Expression $expression)
	{
		throw new Exception("Not implemented", 1);
	}
}

// $e = Parser::parse('проверка  трех слов');
// $e = Parser::parse('проверка (трех с половиной) слов');
// $e = Parser::parse('-проверка трех -слов');
// $e = Parser::parse('O ("A" "B)" D');
// $e = Parser::parse('проверка  (((трех))) "слов');
// $e = Parser::parse('(проверка (трех "ФР1""ФР2)")слов');
// $e = Parser::parse('(A | B) | (C D) "F"');
// var_dump("$e");