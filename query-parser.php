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
						$expression->addChild($cnt = new Group);
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
		$string = preg_replace('/(?<!^|\W)\|(?!\W|$)/u', ' | ', $string); // wrap | with spaces
		$string = preg_replace('/(?<=^|\W)-(?=\S+)/u', '- ', $string); // minus in word start position is operator: add space after
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

	static public function grabOperatorsArguments(Container $expression)
	{
		foreach ($expression as $node) {
			if ($node instanceof Container) {
				self::grabOperatorsArguments($node);
			}
		}

		$collection = $expression;
		$invertor = new Invertor($collection);

		$directions = array(Operator::DIRECTION_L2R, Operator::DIRECTION_R2L);
		$priority = Operator::PRIORITY_MIN;
		while ($priority <= Operator::PRIORITY_MAX) {
			foreach ($directions as $direction) {

				$coll = $direction == Operator::DIRECTION_R2L ? $invertor : $collection;
				for ($i = 0; $i < count($coll); ) {
					$item = $coll[$i];
					if ($item instanceof Operator
							&& $item->getPriority() == $priority
							&& $item->getDirection() == $direction) {
						
						$operator = $item;
						$type = $operator->getType();
						if (!$type) { // is type set?
							// skip
							$i++;
							continue;
						}

						if (!isset($coll[$i - 1])) { // has first required argument?
							// error (delete)
							unset($coll[$i]);
							continue;
						}

						if ($type == Operator::TYPE_UNARY) {
							$treeOpertator = new \Tree\UnaryOperator($operator, array($coll[$i - 1]));
							$coll[$i] = $treeOpertator;

							unset($coll[$i - 1]);
							continue;
							// next (no increment needed)

						} else if ($type == Operator::TYPE_BINARY) {
							if (!isset($coll[$i + 1])) { // has second arguments?
								// error (delete)
								unset($coll[$i]);
								continue;
							}

							$treeOpertator = new \Tree\BinaryOperator($operator, array($coll[$i - 1], $coll[$i + 1]));
							$coll[$i] = $treeOpertator;

							unset($coll[$i - 1]);//$i-1
							unset($coll[$i]); //$i+1 (shifted down after previous operation)
							continue;
							// next (no increment needed)
						}
					}
					// skip
					$i++;
				}
			}
			$priority++;
		}

		return $expression;
	}
}

class Invertor implements \IteratorAggregate, \ArrayAccess, \Countable
{
	private $collection;

	public function __construct($collection)
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
		return $this->collection->offsetSet($this->invert($offset), $value);
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

abstract class Expression
{
	abstract public function isEqualWith(Expression $expression);

	abstract public function dump();

	abstract public function __toString();
}

class Container extends Expression implements \IteratorAggregate, \ArrayAccess, \Countable
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
}

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

	public function dump()
	{
		return "l\"{$this}\"";
	}
}

class Phrase extends Literal
{
	public function __toString()
	{
		return '"' . parent::__toString() . '"';
	}

	public function dump()
	{
		return "p{$this}";
	}
}

abstract class Operator extends Expression
{
	const PRIORITY_MIN = 1;
	const PRIORITY_MAX = 3;
	protected $priority = null;

	const DIRECTION_L2R = 'left-to-right';
	const DIRECTION_R2L = 'right-to-left';
	protected $direction = null;

	const TYPE_UNARY = 'unary';
	const TYPE_BINARY = 'binary';
	protected $type = null;

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

	public function getDirection()
	{
		return $this->direction;
	}

	public function getType()
	{
		return $this->type;
	}

	public function dump()
	{
		return "[$this] <po>";
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
	protected $direction = self::DIRECTION_R2L;
	protected $type = self::TYPE_UNARY;
	static protected $symbol = '-';
}

class OrOperator extends SimpleStandaloneOperator
{
	protected $priority = 3;
	protected $direction = self::DIRECTION_L2R;
	protected $type = self::TYPE_BINARY;
	static protected $symbol = '|';
}


namespace Tree;
use Parser\Container;

abstract class Operator extends \Parser\Expression
{
	protected $operands;
	protected $parserOperator;

	public function __construct(\Parser\Operator $parserOperator, array $operands)
	{
		$this->setParserOperator($parserOperator);
		$this->setOperands($operands);
	}

	public function setParserOperator(\Parser\Operator $parserOperator)
	{
		$this->parserOperator = $parserOperator;
	}
	public function getParserOperator()
	{
		return $this->parserOperator;
	}
	
	public function setOperands(array $operands)
	{
		$this->operands = $operands;
	}
	public function getOperands()
	{
		return $this->operands;
	}

	abstract public function extract();

	public function __toString()
	{
		return (string)($this->extract());
	}

	public function dump()
	{
		return "[{$this->getParserOperator()}] <to>";
	}

	public function isEqualWith(\Parser\Expression $expression)
	{
		return (string)$expression == (string)$this;
	}
}

class UnaryOperator extends Operator
{
	public function extract()
	{
		return new Container(array($this->parserOperator, $this->operands[0]));
	}
}

class BinaryOperator extends Operator
{
	public function extract()
	{
		return new Container(array($this->operands[0], $this->parserOperator, $this->operands[1]));
	}
}


class Dumper
{
	static public function dump(/*\Traversable*/ $container, $level = 0)
	{
		!$level && print '╤' . PHP_EOL;
		foreach ($container as $item) {
			if ($item instanceof Container) {
				print str_repeat('│  ', $level) . '├╴' . $item->dump() . PHP_EOL;
				self::dump($item, $level + 1);

			} else if ($item instanceof Operator) {
				print str_repeat('│  ', $level) . '├╴' . $item->dump() . PHP_EOL;
				self::dump($item->getOperands(), $level + 1);

			} else {
				print str_repeat('│  ', $level) . '├╴' . $item->dump() . PHP_EOL;
			}
		}
	}
}