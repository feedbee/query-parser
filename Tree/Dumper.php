<?php

namespace QueryParser\Tree;

use QueryParser\Parser\Expression\Container;
use QueryParser\Parser\Expression\Expression;

class Dumper
{
    static public function dump(/*\Traversable*/
        $container,
        $level = 0,
        $lastElementsMarks = array()
    ) {
        !$level && print self::border("╤") . PHP_EOL;

        $cntrChildrenCount = count($container);
        $i = 0;
        foreach ($container as $item) {
            $isLast = $i == $cntrChildrenCount - 1;

            /** @var Expression $item */
            print self::repeat($level,
                    $lastElementsMarks) . self::border(($isLast ? '└' : '├') . '╴') . self::header($item->dump()) . PHP_EOL;

            $dumpChildren = null;
            $item instanceof Container && $dumpChildren = $item;
            /** @var Operator $item */
            $item instanceof Operator && $dumpChildren = $item->getOperands();

            if ($dumpChildren) {
                $newLastElementsMarks = array_merge($lastElementsMarks, array($isLast));
                self::dump($dumpChildren, $level + 1, $newLastElementsMarks);
            }

            $i++;
        }
    }

    static private function repeat($level, $lastElementsMarks)
    {
        $result = '';
        for ($i = 0; $i < $level; $i++) {
            $result .= self::border(!$lastElementsMarks[$i] ? '│  ' : '   ');
        }

        return $result;
    }

    const COLOR_BORDER_START = "\033[0;33m";
    const COLOR_HEADER_START = "\033[1;36m";
    const COLOR_STOP = "\033[0m";

    static private function border($element)
    {
        return self::COLOR_BORDER_START . $element . self::COLOR_STOP;
    }

    static private function header($text)
    {
        return self::COLOR_HEADER_START . $text . self::COLOR_STOP;
    }
}