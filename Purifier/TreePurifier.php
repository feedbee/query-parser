<?php

namespace Purifier;

use Parser\Expression\Container;
use Parser\Expression\Group;
use Parser\Expression\Literal;
use Parser\Expression\Phrase;
use Tree\BinaryOperator;
use Tree\UnaryOperator;

class TreePurifier implements PurifierInterface
{
    /** @var  $group Group */
    private $source;

    public function __construct($source)
    {
        $this->source = $source;
    }

    public function purify()
    {
        return $this->traverseSource($this->source);
    }

    public function traverseGroup($source, $first = false)
    {

        $offsetKey = 0;

        foreach ($source as $item) {
            if ($item instanceof Group) {
                $childNodes = $item->getChildNodes();

                if (count($childNodes) == 1 && $childNodes[0] instanceof Group) {
                    $source->replaceNode($offsetKey, $childNodes[0]);
                    $this->traverseGroup($source);
                }

                if (count($childNodes) == 0) {
                    $source->offsetUnset($offsetKey);
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

    public function traverseSource($source)
    {
        $offsetKey = 0;

        $sourceArray = $source;

        if ($source instanceof \Tree\Operator) {
            $sourceArray = $source->getOperands();
        }

        foreach ($sourceArray as $item) {

            if ($item instanceof Phrase && $item->isEmpty()) {
                $source->offsetUnset($offsetKey);
                continue;
            }

            if ($item instanceof Literal) {
                $offsetKey++;
                continue;
            }

            if ($item instanceof Group && count($item->getChildNodes()) == 0) {
                $source->offsetUnset($offsetKey);
                continue;
            }

            $res = $this->traverseGroup($item, true);

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
                    $this->traverseSource($itemOut);
                }
            }

            if ($item instanceof BinaryOperator || $item instanceof UnaryOperator) {

                $operands = array();

                foreach ($item->getOperands() as $itemOut) {
                    $operands[] = $this->traverseSource($itemOut);
                }

                $item->setOperands($operands);

            }

            $offsetKey++;
        }

        return $source;
    }
}