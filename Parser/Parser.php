<?php

namespace QueryParser\Parser;

use QueryParser\Parser\Expression\Container;
use QueryParser\Parser\Expression\Group;
use QueryParser\Parser\Expression\Literal;
use QueryParser\Parser\Expression\Operator;
use QueryParser\Parser\Expression\Phrase;
use QueryParser\Tree\BinaryOperator;
use QueryParser\Tree\UnaryOperator;

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
        $string = str_replace('|', ' | ', $string);
        $string = str_replace('(-', '( -', $string);
        $string = str_replace(')-', ') -', $string);
        $string = preg_replace('/(-{2,})+/u', '-', $string);
        $string = preg_replace('/(?<!^| )-(?=\w|\()/u', ' ', $string);
        $string = preg_replace('/(?<=^| )-(?!\w|\()/u', ' ', $string);
        $string = preg_replace('/(?<!^| )-(?!\w|\()/u', ' ', $string);
        $string = str_replace('-', '- ', $string);
        $string = preg_replace('/\s+/u', ' ', $string); // replace any count of any space characters with single space

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
                    $operatorClassName = 'QueryParser\\Parser\\Expression\\' . $operatorClassName;
                    /** @var $operatorClassName Operator */
                    $result = $operatorClassName::detectAndTransform($node);
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
        $inverter = new Inverter($collection);

        $directions = array(Operator::DIRECTION_L2R, Operator::DIRECTION_R2L);
        $priority = Operator::PRIORITY_MIN;
        while ($priority <= Operator::PRIORITY_MAX) {
            foreach ($directions as $direction) {

                $coll = $direction == Operator::DIRECTION_R2L ? $inverter : $collection;
                for ($i = 0; $i < count($coll);) {
                    $item = $coll[$i];
                    if ($item instanceof Operator
                        && $item->getPriority() == $priority
                        && $item->getDirection() == $direction
                    ) {

                        $operator = $item;
                        $type = $operator->getType();
                        if (!$type) { // is type set?
                            // skip
                            $i++;
                            continue;
                        }

                        if (!isset($coll[$i - 1]) || (!($coll[$i - 1] instanceof \QueryParser\Tree\Operator) && $coll[$i - 1]->isEmpty())) { // has first required argument and this argument is not empty?
                            // error (delete)
                            unset($coll[$i]);
                            continue;
                        }

                        if ($type == Operator::TYPE_UNARY) {
                            $treeOperator = new UnaryOperator($operator, array($coll[$i - 1]));
                            $coll[$i] = $treeOperator;

                            unset($coll[$i - 1]);
                            continue;
                            // next (no increment needed)

                        } else {
                            if ($type == Operator::TYPE_BINARY) {
                                if (!isset($coll[$i + 1]) || get_class($coll[$i]) == get_class($coll[$i + 1]) || (!($coll[$i + 1] instanceof \QueryParser\Tree\Operator) && $coll[$i + 1]->isEmpty())) { // has second arguments? next arg is not operator
                                    // error (delete)
                                    unset($coll[$i]);
                                    continue;
                                }

                                $treeOperator = new BinaryOperator($operator, array($coll[$i - 1], $coll[$i + 1]));
                                $coll[$i] = $treeOperator;

                                unset($coll[$i - 1]);//$i-1
                                unset($coll[$i]); //$i+1 (shifted down after previous operation)
                                continue;
                                // next (no increment needed)
                            }
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