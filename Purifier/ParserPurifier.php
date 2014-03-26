<?php

namespace Purifier;

use Parser\Expression\Container;
use Parser\Expression\Literal;

class ParserPurifier implements PurifierInterface
{

    private $sourceTree;

    private $outputTree;

    private $item;

    private $globalRes = array(true);


    public function __construct($sourceTree)
    {
        $this->sourceTree = $sourceTree;
        $this->outputTree = new Container();
    }

    public function purify()
    {

        $this->itemTraverse($this->sourceTree);

        return $this->sourceTree;
    }




//    private function recPurify($source)
//    {
//        while (count(array_filter($this->globalRes)) != 0) {
//            $this->globalRes = array();
//            foreach ($source as $key => $item) {
//                $this->purifyChain($source, $source[$key], $key);
//            }
//        }
//
//        foreach ($source as $key => $item) {
//
//            if ($item instanceof Literal) {
//                return;
//            }
//
//            $this->recPurify($source['key']);
//        }
//    }
//
//    private function purifyChain($parent, $item, $key)
//    {
//        if ($item instanceof Literal) {
//            $this->globalRes[] = false;
//            return;
//        }
//
//        $this->item = $item;
//        $this->purifyItem();
//
//        if ($this->item === null) {
//            $item->offsetUnset($key);
//            return;
//        }
//
//        $item = $this->item;
//
//        if ((string)$parent[$key] != (string)$item) {
//            $parent[$key] = $item;
//            $parent[] = true;
//        }
//
//    }
//
//    private function purifyItem()
//    {
//        $class = get_class($this->item);
//        $result = null;
//
//        switch ($class) {
//            case 'Parser\Expression\Group' :
//                $purifier = new GroupPurifier($this->item);
//                $this->item = $purifier->purify();
//                break;
//
//            default: $this->item;
//        }
//
//    }


}