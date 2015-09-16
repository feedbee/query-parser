<?php
/**
 * Designed for monospace fonts!
 */
namespace QueryParser\Tree;

use QueryParser\Parser\Expression\Literal;

class HtmlDumper
{
    const END = 1;

    private $source;

    private $iteration;

    private $lastCounter = array();

    private $html = '';

    public function __construct($source)
    {
        $this->source = $source;
    }

    public function dump()
    {
        $this->html .= "<b>Container</b>&nbsp;<span style='color:green'>{$this->source}&nbsp;</span><br>";
        $this->traverse($this->source);
        return $this->html;
    }

    private function traverse($source)
    {
        $this->iteration++;
        $sourceArray = $source;

        if ($source instanceof Operator) {
            $sourceArray = $source->getOperands();
        }

        if (!$source instanceof Literal) {
            $this->lastCounter[$this->iteration] = 0;
            $this->html .= $this->getOffsetLeft(self::END);
        }

        foreach ($sourceArray as $item) {

            $this->lastCounter[$this->iteration] = 1;

            if ($item == $sourceArray[count($sourceArray) - 1]) {
                $this->lastCounter[$this->iteration] = 0;
            }

            $this->html .= $this->getOffsetLeft($item);
            $this->traverse($item);
            $this->iteration--;

            if ($item != $sourceArray[count($sourceArray) - 1]) {
                $this->html .= $this->getOffsetLeft(self::END);
            }
        }
    }

    private function getOffsetLeft($item = null)
    {

        $spaces = '';
        for ($i = 0; $i <= $this->iteration - 1; $i++) {
            if (isset($this->lastCounter[$i]) && $this->lastCounter[$i]) {
                $spaces .= '|';
            }
            $spaces .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        }

        if (is_int($item) && $item == self::END) {
            return rtrim($spaces, '|') . '|<br>';
        }

        $itemNames = explode('\\', get_class($item));
        return $spaces . '+-<b>' . end($itemNames) . "</b>&nbsp;<span style='color:green'>{$item}" . '</span><br>';
    }
}