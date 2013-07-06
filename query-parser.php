<?php

class Parser
{
	public static function parse($string)
	{
		$stack = array();
		$stackExpr = array();
		$string = self::adapt($string);
		$expression = new ContainerExpression;
		$expression->addChild(new StringExpression());

		for ($i = 0; $i < mb_strlen($string); $i++) {
			$char = mb_substr($string, $i, 1);

			if (count($stack) > 0 && end($stack) == '"' && $char != '"') {
				$expression->getLastChild()->appendString($char);
			} else {
				switch ($char) {
					case ' ':
						$expression->addChild(new StringExpression());
						break;
					case '"':
						if (count($stack) > 0 && end($stack) == '"') {
							// закрываем фразу
							$expression->addChild(new StringExpression);
							array_pop($stack);
						} else {
							// открываем фразу
							$expression->addChild($ph = new Phrase);
							array_push($stack, $char);
						}
						break;
					case '(':
						// открываем выражение
						$expression->addChild($cnt = new ContainerExpression);
						$cnt->addChild(new StringExpression);
						array_push($stack, ')');
						array_push($stackExpr, $expression);
						$expression = $cnt;
						break;
					case ')':
						if (count($stack) > 0 && end($stack) == ')') {
							// есть что закрыть — закрываем выражение
							$expression = array_pop($stackExpr);
							$expression->addChild(new StringExpression);
							array_pop($stack);
						} else {
							// скобка "не в тему"
							$expression->getLastChild()->appendString($char);
						}
						break;
					default:
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

class Expression {

}

class ContainerExpression extends Expression {
	private $childNodes = array();

	public function addChild(Expression $expression)
	{
		$this->childNodes[] = $expression;
	}

	public function getLastChild()
	{
		return end($this->childNodes);
	}

	public function __toString()
	{
		return implode(' ', $this->childNodes);
	}
}

class StringExpression extends Expression {
	private $string = "";

	public function __construct($string = "")
	{
		$this->string = $string;
	}

	public function appendString($string)
	{
		$this->string .= $string;
	}

	public function __toString()
	{
		return $this->string;
	}
}

class Phrase extends StringExpression {}

class Operator extends Expression {
	private $priority = 0;
}

$e = Parser::parse('проверка  трех слов');
$e = Parser::parse('проверка (трех с половиной) слов');
$e = Parser::parse('-проверка трех -слов');
$e = Parser::parse('(проверка (трех "1""2)")слов');
$e = Parser::parse('O ("A" "B)" D');
$e = Parser::parse('проверка  (((трех))) "слов');
var_dump($e);