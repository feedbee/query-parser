<?php

require 'query-parser.php';

$tests = array(
	'проверка  трех слов' => new Container(array(
		new Literal('проверка'),
		new Literal('трех'),
		new Literal('слов'),
	)),
	'проверка (трех с половиной) слов' => new Container(array(
		new Literal('проверка'),
		new Container(array(
			new Literal('трех'),
			new Literal('с'),
			new Literal('половиной'),
		)),
		new Literal('слов'),
	)),
	'-проверка трех -слов' => new Container(array(
		new NotOperator(),
		new Literal('проверка'),
		new Literal('трех'),
		new NotOperator(),
		new Literal('слов'),
	)),
	'L ("A" "B)" R' => new Container(array(
		new Literal('L'),
		new Container(array(
			new Phrase('A'),
			new Phrase('B)'),
			new Literal('R'),
		)),
	)),
	'проверка  (((трех))) "слов' => new Container(array(
		new Literal('проверка'),
		new Container(array(new Container(array(new Container(array(
			new Literal('трех'),
		)))))),
		new Phrase('слов'),
	)),
	'(проверка (трех "ФР1""ФР2)")слов' => new Container(array(new Container(array(
		new Literal('проверка'),
		new Container(array(
			new Literal('трех'),
			new Phrase('ФР1'),
			new Phrase('ФР2)'),
		)),
		new Literal('слов'),
	)))),
	'(A|B) | (C - D) "F"' => new Container(array(
		new Container(array(
			new Literal('A'),
			new OrOperator(),
			new Literal('B'),
		)),
		new OrOperator(),
		new Container(array(
			new Literal('C'),
			new NotOperator(),
			new Literal('D'),
		)),
		new Phrase('F'),
	)),
);

$i = $ok = $fail = 0;
foreach ($tests as $input => $etalon) {
	$i++;
	$result = Parser::detectOperators(Parser::parse($input));

	if ($result->isEqualWith($etalon)) {
		echo "\033[0;32m[$i]\033[0m Success: `{$input}`" . PHP_EOL;
		$ok++;
	} else {
		echo "\033[0;31m[$i]\033[0m Failure: `{$input}`" . PHP_EOL;
		$fail++;
		if (1) {
			var_dump($result);
			var_dump($etalon);
		}
	}
}

echo "All tests complated. \033[0;32m$ok\033[0m test" . ($ok > 1 ? 's' : '')
	. " successed, \033[0;31m$fail\033[0m test" . ($fail > 1 ? 's' : '') . " failed." . PHP_EOL;