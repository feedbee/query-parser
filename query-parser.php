<?php

class Expression {
	private $string = "";
	private $childNodes = array();

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

	public function parse()
	{
		$stack = array();
		$string = self::adapt($this->string);
		$nodes = array(new Expression);

		for ($i = 0;  $i < mb_strlen($string); $i++) {
			$char = mb_substr($string, $i, 1);

			switch ($char) {
				case ' ':
					if (count($stack) == 1) { // только 1-й уровень
						$nodes[] = new Expression();
					} else {
						end($nodes)->appendString($char);
					}
					break;
				case '"':
					if (count($stack) > 0 && end($stack) == '"') {
						// закрываем фразу
						if (count($stack) == 1) { // только 1-й уровень
							$nodes[] = new Expression;
						} else {
							end($nodes)->appendString($char);
						}
						array_pop($stack);
					} else {
						// открываем фразу
						if (count($stack) == 0) { // только 1-й уровень
							$nodes[] = new Phrase;
						} else {
							end($nodes)->appendString($char);
						}
						array_push($stack, $char);
					}
					break;
				case '(':
					// открываем выражение
					if (count($stack) == 0) { // только 1-й уровень
						$nodes[] = new Expression;
					} else {
						end($nodes)->appendString($char);
					}
					array_push($stack, ')');
					break;
				case ')':
					if (count($stack) > 0 && end($stack) == ')') {
						// есть что закрыть — закрываем выражение
						if (count($stack) == 1) { // только 1-й уровень
							end($nodes)->parse();
							$nodes[] = new Expression;
						} else {
							end($nodes)->appendString($char);
						} 
						array_pop($stack);
					} else {
						// скобка "не в тему"
						end($nodes)->appendString($char);
					}
					break;
				default:
					end($nodes)->appendString($char);
					break; 
			}
		}

		// незакрытые парные элементы
		while (count($stack) > 0) {
			$char = array_pop($stack);
			$this->appendString($char);
			if (count($stack) == 0 && $char == ")") {
				end($nodes)->parse();
			}
		}

		$this->childNodes = $nodes;
	}

	private static function adapt($string)
	{
		return preg_replace('/\s+/', ' ', $string); // replace any count of any space characted with single space
	}
}

class Compiled extends Expression {}

class Literal extends Expression {}

class Phrase extends Literal {}

class Operator extends Expression {
	private $priority = 0;
}

$e = new Expression('проверка  трех слов');
$e = new Expression('проверка (трех с половиной) слов');
$e = new Expression('-проверка трех -слов');
$e = new Expression('(проверка (трех "1""2)")слов');
//$e = new Expression('O ("A" "B)" D');
//$e = new Expression('проверка  (((трех))) "слов');
$e->parse();
var_dump($e);