<?php

require 'query-parser.php';

$opts = getopt("d", array('debug'));
define('PARSER_DEBUG_MODE', isset($opts['d']) || isset($opts['debug']));

use Parser\Parser, Parser\Literal, Parser\Phrase, Parser\Container, Parser\Group,
	Parser\NotOperator as ParserNotOperator, Parser\OrOperator as ParserOrOperator,
	Dumper;

$tests = array(
	'проверка  трех слов' => new Container(array(
		new Literal('проверка'),
		new Literal('трех'),
		new Literal('слов'),
	)),
	'проверка (трех с половиной) слов' => new Container(array(
		new Literal('проверка'),
		new Group(array(
			new Literal('трех'),
			new Literal('с'),
			new Literal('половиной'),
		)),
		new Literal('слов'),
	)),
	'-проверка трех -слов' => new Container(array(
		new ParserNotOperator(),
		new Literal('проверка'),
		new Literal('трех'),
		new ParserNotOperator(),
		new Literal('слов'),
	)),
	'L ("A" "B)" R' => new Container(array(
		new Literal('L'),
		new Group(array(
			new Phrase('A'),
			new Phrase('B)'),
			new Literal('R'),
		)),
	)),
	'проверка  (((трех))) "слов' => new Container(array(
		new Literal('проверка'),
		new Group(array(new Group(array(new Group(array(
			new Literal('трех'),
		)))))),
		new Phrase('слов'),
	)),
	'(проверка (трех "ФР1""ФР2)")слов' => new Container(array(new Group(array(
		new Literal('проверка'),
		new Group(array(
			new Literal('трех'),
			new Phrase('ФР1'),
			new Phrase('ФР2)'),
		)),
		new Literal('слов'),
	)))),
	'(A|B) | (C - D) "F"' => new Container(array(
		new Group(array(
			new Literal('A'),
			new ParserOrOperator(),
			new Literal('Bs'),
		)),
		new ParserOrOperator(),
		new Group(array(
			new Literal('C'),
			new ParserNotOperator(),
			new Literal('D'),
		)),
		new Phrase('F'),
	)),
	'-A' => new Container(array(
		new ParserNotOperator(),
		new Literal('A'),
	)),
	'--A' => new Container(array(
		new ParserNotOperator(),
		new ParserNotOperator(),
		new Literal('A'),
	)),
	/* After filters Sphinx special tests
	'-' => new Container(),
	'xx|' => new Container(),
	'xx|yy' => new Container(array(
		new Literal('xx'),
		new ParserOrOperator(),
		new Literal('yy'),
	)),
	'|yy' => new Container(),
	'-xxx -yyy' => new Container(),
	'-(-xx) ss' => new Container(),
	*/
);

$i = $ok = $fail = 0;
foreach ($tests as $input => $etalon) {
	$i++;
	$result = Parser::grabOperatorsArguments(Parser::detectOperators(Parser::parse($input)));

	if ((string)$result == (string)$etalon) {
		echo "\033[0;32m[$i]\033[0m Success: `{$input}`" . PHP_EOL;
		$ok++;

		if (PARSER_DEBUG_MODE) {
			Dumper::dump($result);
			print PHP_EOL;
		}
	} else {
		echo "\033[0;31m[$i]\033[0m Failure: `{$input}`" . PHP_EOL;
		$fail++;

		if (PARSER_DEBUG_MODE) {
			print PHP_EOL . "[$i] Result: " . PHP_EOL;
			Dumper::dump($result);
			print PHP_EOL . "[$i] Etalon: " . PHP_EOL;
			Dumper::dump($etalon);
			print PHP_EOL;
		}
	}
}

echo "All tests complated. \033[0;32m$ok\033[0m test" . ($ok > 1 ? 's' : '')
	. " successed, " . ($fail > 0 ? "\033[0;31m" : '') . "$fail" . ("\033[0m")
	. " test" . ($fail > 1 ? 's' : '') . " failed." . PHP_EOL;