<?php

require_once('Parser/Expression/CollectionInterface.php');
require_once('Parser/Expression/Expression.php');
require_once('Parser/Expression/Container.php');
require_once('Parser/Expression/Group.php');
require_once('Parser/Expression/Literal.php');
require_once('Parser/Expression/Phrase.php');
require_once('Parser/Expression/Operator.php');
require_once('Parser/Expression/SimpleStandaloneOperator.php');
require_once('Parser/Expression/NotOperator.php');
require_once('Parser/Expression/OrOperator.php');

require_once('Tree/Operator.php');
require_once('Tree/UnaryOperator.php');
require_once('Tree/BinaryOperator.php');
require_once('Tree/Dumper.php');

require_once('Parser/Inverter.php');
require_once('Parser/Parser.php');

require_once('Purifier/PurifierInterface.php');
require_once('Purifier/GroupPurifier.php');
require_once('Purifier/ParserPurifier.php');