<?php

use QueryParser\Parser\Parser;
use QueryParser\Purifier\TreePurifier;
use QueryParser\Tree\HtmlDumper;

require 'query-parser.php';

if (empty($_POST['data'])) {
    echo json_encode(array('result' => 'Введите строку для анализа!'));
    die;
}

$result = Parser::grabOperatorsArguments(Parser::detectOperators(Parser::parse(trim($_POST['data']))));
$purifier = new TreePurifier($result);
$out = $purifier->purify();

//--dumper
$dumper = new HtmlDumper($out);
$tree = $dumper->dump();


echo json_encode(array('result' => (string)$out, 'tree' => $tree));
