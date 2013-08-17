<?php
namespace Parser;

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
		$string = preg_replace('/(?<!^|\s)\|(?!\s|$)/u', ' | ', $string); // wrap | with spaces
		$string = preg_replace('/(?<=^|\s)-(?=\S+)/u', '- ', $string); // minus in word start position is operator: add space after
		$string = preg_replace('/\s+/u', ' ', $string); // replace any count of any space characted with single space

		return $string;
	}

	static public function detectOperators(Container $expression)
	{
		$operators = array('NotOperator', 'OrOperator');

		foreach ($expression as $key => $node) {
			if ($node instanceof Container) {
				self::detectOperators($node);
			} else {
				foreach ($operators as $operatorClassName) {
					$operatorClassName = 'Parser\\' . $operatorClassName;
					$result = $operatorClassName::detectAndTranform($node);
					if ($result != $node) {
						$expression->replaceNode($key, $result);
						continue;
					}
				}
			}
		}

		return $expression;
	}
}

abstract class Expression
{
	abstract public function isEqualWith(Expression $expression);
}

class Container extends Expression implements \IteratorAggregate
{
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
		if (count($this->childNodes) < 2) {
			return implode(' ', $this->childNodes);
		}
		return '(' . implode(' ', $this->childNodes) . ')';
	}

	public function getIterator() {
		return new \ArrayIterator($this->childNodes);
	}
}

class Literal extends Expression
{
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

class Phrase extends Literal
{
	public function __toString()
	{
		return '"' . parent::__toString() . '"';
	}
}

class Operator extends Expression
{
	const PRIORITY_MIN = 1;
	const PRIORITY_MAX = 3;
	protected $priority = 0;

	static public function detectAndTranform(Expression $expression)
	{
		throw new Exception('Every operator class must override this static method', 2);
	}

	public function isEqualWith(Expression $expression)
	{
		return $expression instanceof static;
	}

	public function getPriority()
	{
		return $this->priority;
	}
}

class SimpleStandaloneOperator extends Operator
{
	static protected $symbol = null;

	static public function detectAndTranform(Expression $expression)
	{
		if (is_null(static::$symbol)) {
			throw new Exception('Operator symbol must be declared in successor-classes', 3);
		}
		if ($expression instanceof Literal) {
			$string = ((string)$expression);
			if ($string === static::$symbol) {
				return new static();
			}
		}

		return $expression;
	}

	public function __toString()
	{
		return static::$symbol;
	}
}

class NotOperator extends SimpleStandaloneOperator
{
	protected $priority = 1;
	static protected $symbol = '-';
}

class OrOperator extends SimpleStandaloneOperator
{
	protected $priority = 3;
	static protected $symbol = '|';
}