<?php

use QueryParser\Parser\Parser;
use QueryParser\Purifier\TreePurifier;
use QueryParser\Tree\HtmlDumper;
use QueryParser\Purifier\SphinxNormalizer;

require 'query-parser.php';

if (empty($_POST['data'])) {
    echo json_encode(array('result' => 'Введите строку для анализа!'));
    die;
}

$result = Parser::grabOperatorsArguments(Parser::detectOperators(Parser::parse(trim($_POST['data']))));
$out = (new SphinxNormalizer())->normalize((new TreePurifier($result))->purify());

//--dumper
$dumper = new HtmlDumper($out);
$tree = $dumper->dump();


echo json_encode(array('result' => (string)$out, 'tree' => $tree));
