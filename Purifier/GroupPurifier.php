<?php
/**
 * () - remove empty group
 * (()) - remove useless nesting
 */

namespace Purifier;

use Parser\Expression\Container;
use Parser\Expression\Group;
use Parser\Expression\Literal;
use Parser\Expression\OrOperator;
use Parser\Expression\NotOperator;
use Parser\Expression\Phrase;
use Parser\Expression\Operator;
use Tree\BinaryOperator;
use Tree\UnaryOperator;

class GroupPurifier implements PurifierInterface
{
    /** @var  $group Group */
    private $group;

    private $source;

    private $deletedNode = false;

    public function __construct()
    {
        //$this->source = $source;
    }

    public function purify()
    {
        if ($this->isEmptyGroup()) {
            return null;
        }

        $this->removeUselessNesting();

        return $this->group;
    }

    private function isEmptyGroup()
    {
        $childNodes = $this->group->getChildNodes();

        return empty($childNodes);
    }


    private function removeUselessNesting()
    {
        $childNodes = $this->group->getChildNodes();

        if (count($childNodes) == 1 && $childNodes[0] instanceof Group) {
            $this->group = $childNodes[0];
        }
    }

    public function notTraverse($source, $iteration = 1)
    {

        $operands = $source->getOperands();

        if ($operands[0] instanceof UnaryOperator && get_class($operands[0]->getParserOperator()) == 'Parser\Expression\NotOperator') {
            $childrenOperands = $operands[0]->getOperands();
            $source->setOperands($childrenOperands);
            $iteration++;
            $iteration = $this->notTraverse($source, $iteration);
        }

        return $iteration;
    }

    public function traverse($source, $first = false)
    {

        $offsetKey = 0;

        foreach ($source as $item) {
            if ($item instanceof Group) {
                $childNodes = $item->getChildNodes();

                if (count($childNodes) == 1 && $childNodes[0] instanceof Group) {
                    $source->replaceNode($offsetKey, $childNodes[0]);
                    $this->traverse($source);
                }

                if (count($childNodes) == 0) {
                    $source->offsetUnset($offsetKey);
                    $this->deletedNode = true;
                    continue;
                }
            }
            $offsetKey++;
        }

        if ($first && $source instanceof Group) {
            $childNodes = $source->getChildNodes();

            if (count($childNodes) == 1 && $childNodes[0] instanceof Group) {
                $source = $childNodes[0];
            }

            if (count($childNodes) == 0) {
                $source = null;
            }
        }


        return $source;
    }

    public function deep($source)
    {
        $offsetKey = 0;

        $sourcer = $source;

        if ($source instanceof BinaryOperator || $source instanceof UnaryOperator) {
            $sourcer = $source->getOperands();
        }

        foreach ($sourcer as $item) {

            if ($item instanceof Phrase && $item->isEmpty()) {
                $source->offsetUnset($offsetKey);
                continue;
            }

            if ($item instanceof Literal || $item instanceof Phrase) {
                $offsetKey++;
                continue;
            }

            if ($item instanceof Group && count($item->getChildNodes()) == 0) {
                $source->offsetUnset($offsetKey);
                continue;
            }

            $res = $this->traverse($item, true);

            if ($source instanceof BinaryOperator || $source instanceof UnaryOperator) {
                $operands = $source->getOperands();
                $operands[$offsetKey] = $res;
                $source->setOperands($operands);
            } else {
                if ($source instanceof Group && count($source->getChildNodes()) == 1 && $res instanceof Group) {
                    $source = $res;
                } else {
                    if ($res !== null) {
                        $source->replaceNode($offsetKey, $res);
                    } else {
                        $source->offsetUnset($offsetKey);
                        continue;
                    }
                }
            }


            //clear unary operator
            if ($item instanceof UnaryOperator && get_class($item->getParserOperator()) == 'Parser\Expression\NotOperator') {
                $notOperatorsCount = $this->notTraverse($item);
                //$notOperatorsCount++;
                $operands = $item->getOperands();
                if ($notOperatorsCount % 2 == 0) {

                    if ($source instanceof Container || $source instanceof Group) {
                        $source->replaceNode($offsetKey, $operands[0]);
                    } else {
                        $source = $operands[0];
                        $item = $operands[0];
                    }

                } else {

                    if ($source instanceof Container || $source instanceof Group) {
                        $source->replaceNode($offsetKey, $item);
                    } else {
                        $source->setOperands($item->getOperands());
                    }

                }
            }

            if ($item instanceof Container) {
                foreach ($item->getChildNodes() as $itemOut) {
                    $this->deep($itemOut);
                }
            }

            if ($item instanceof BinaryOperator || $item instanceof UnaryOperator) {

                $operands = array();

                foreach ($item->getOperands() as $itemOut) {
                    $operands[] = $this->deep($itemOut);
                }

                $item->setOperands($operands);

            }

            $offsetKey++;
        }

        return $source;
    }

    public function deepTwo($source)
    {
        $offsetKey = 0;

        $sourcer = $source;

        if ($source instanceof BinaryOperator || $source instanceof UnaryOperator) {
            $sourcer = $source->getOperands();
        }

        foreach ($sourcer as $item) {

            if ($item instanceof Phrase && $item->isEmpty()) {
                $source->offsetUnset($offsetKey);
                continue;
            }

            if ($item instanceof Literal || $item instanceof Phrase) {
                $offsetKey++;
                continue;
            }

            if ($item instanceof Group && count($item->getChildNodes()) == 0) {
                $source->offsetUnset($offsetKey);
                continue;
            }

            $res = $this->traverse($item, true);

            if ($source instanceof BinaryOperator || $source instanceof UnaryOperator) {
                $operands = $source->getOperands();
                $operands[$offsetKey] = $res;
                $source->setOperands($operands);
            } else {
                if ($source instanceof Group && count($source->getChildNodes()) == 1 && $res instanceof Group) {
                    $source = $res;
                } else {
                    if ($res !== null) {
                        $source->replaceNode($offsetKey, $res);
                    } else {
                        $source->offsetUnset($offsetKey);
                        continue;
                    }
                }
            }

            if ($item instanceof Container) {
                foreach ($item->getChildNodes() as $itemOut) {
                    $this->deep($itemOut);
                }
            }

            if ($item instanceof BinaryOperator || $item instanceof UnaryOperator) {

                $operands = array();

                foreach ($item->getOperands() as $itemOut) {
                    $operands[] = $this->deep($itemOut);
                }

                $item->setOperands($operands);
            }

            $offsetKey++;
        }

        return $source;
    }
}