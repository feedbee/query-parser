<?php
namespace QueryParser\Purifier;

use QueryParser\Parser\Expression\Container;
use QueryParser\Parser\Expression\Group;
use QueryParser\Parser\Expression\Literal;
use QueryParser\Parser\Expression\NotOperator;
use QueryParser\Parser\Expression\OrOperator;
use QueryParser\Tree\BinaryOperator;
use QueryParser\Tree\Operator;
use QueryParser\Tree\UnaryOperator;

class SphinxNormalizer
{
    const MAX_CLEAR_ITERATIONS = 100;

    const UNIVERSAL_PLACEHOLDER = '%%universal_placeholder%%';

    private static $isNotOperatorFirst;

    /**
     * @var int
     */
    private $iterationCounter = 0;

    public function __construct()
    {
        self::$isNotOperatorFirst = null;
    }

    public function normalize($source)
    {
        return $this->cyclingClear($source);
    }

    /**
     * @return int
     */
    public function getIterationCounter()
    {
        return $this->iterationCounter;
    }

    /**
     * @return null|bool
     */
    public static function getIsNotOperatorFirst()
    {
        return self::$isNotOperatorFirst;
    }


    /**
     * @param Container $source
     * @return Container
     */
    private function cyclingClear($source)
    {
        $result = $this->process($source);
        $newResult = new \stdClass();
        $this->iterationCounter = 1;

        while (spl_object_hash($result) !== spl_object_hash($newResult) && $this->iterationCounter <= self::MAX_CLEAR_ITERATIONS) {
            $newResult = $result;
            $result = $this->process($source);
            $this->iterationCounter++;
        }

        if (self::$isNotOperatorFirst === null) {
            self::$isNotOperatorFirst = false;
        }

        $container = $result;

        if (self::$isNotOperatorFirst) {
            $container = new Container();
            $container->addChild(new Literal(self::UNIVERSAL_PLACEHOLDER));

            /** @var Container $result */
            foreach ($result->getChildNodes() as $node) {
                $container->addChild($node);
            }
        }

        return $container;
    }

    private function process($source)
    {
        $sourceArray = $source;

        if ($source instanceof Operator) {
            $sourceArray = $source->getOperands();
        }

        $offsetKey = 0;

        foreach ($sourceArray as $itemPos => $item) {

            if ($item instanceof Literal) {

                if (self::$isNotOperatorFirst === null) {
                    self::$isNotOperatorFirst = false;
                }

                $offsetKey++;
                continue;
            }

            if ($item instanceof Operator) {

                $this->trackFirstNotOperator($item);

                if ($this->ruleRemoveDuplicateNotUnaryOperator($item, $source, $offsetKey, $itemPos)) {
                    $this->process($source);
                    break;
                }

                if ($this->ruleNoNotAloneOperatorsInOrOperator($item)) {
                    $this->process($source);
                    break;
                }

                $this->process($item);
            }

            if ($item instanceof Group) {

                if ($this->ruleRemoveGroupWithOnlyLiteral($item, $source, $offsetKey, $itemPos)) {
                    $this->process($source);
                    break;
                }

                if ($this->ruleRemoveGroupWithOnlyUnaryOperator($item, $source, $offsetKey, $itemPos)) {
                    $this->process($source);
                    break;
                }

                if ($this->ruleNoOnlyNotOperatorsInGroup($item)) {
                    $this->process($source);
                    break;
                }

                $this->process($item);
            }
        }
        return $source;
    }

    private function trackFirstNotOperator(Operator $operator)
    {

        if ($operator instanceof UnaryOperator && $operator->getParserOperator() instanceof NotOperator &&
            self::$isNotOperatorFirst === null
        ) {
            self::$isNotOperatorFirst = true;
        }
    }

    //---- Group rules

    private function ruleRemoveGroupWithOnlyLiteral(Group $group, $source, $offset, $itemPos)
    {
        if (count($group->getChildNodes()) === 1 && $group->getChildNodes()[0] instanceof Literal) {

            $this->removeItemFromSource($group, $source, $offset, $itemPos);

            return true;
        }

        return false;
    }

    private function ruleRemoveGroupWithOnlyUnaryOperator(Group $group, $source, $offset, $itemPos)
    {
        if (count($group->getChildNodes()) === 1 && $group->getChildNodes()[0] instanceof UnaryOperator) {

            $this->removeItemFromSource($group, $source, $offset, $itemPos);

            return true;
        }

        return false;
    }

    private function ruleNoOnlyNotOperatorsInGroup(Group $group)
    {
        $isNot = true;
        $children = [];

        foreach ($group->getChildNodes() as $pos => $node) {
            if (!$node instanceof UnaryOperator ||
                ($node instanceof UnaryOperator && !$node->getParserOperator() instanceof NotOperator)) {
                $isNot = false;
            }
            $children[] = $node;
        }

        if ($isNot === true) {
            $group->removeAllChildren();
            array_unshift($children, new Literal(self::UNIVERSAL_PLACEHOLDER));

            foreach ($children as $child) {
                $group->addChild($child);
            }
        }

        return $isNot;
    }

    //---- Operators rules

    private function ruleRemoveDuplicateNotUnaryOperator(Operator $operator, $source, $offset, $itemPos)
    {
        if ($operator instanceof UnaryOperator && $operator->getParserOperator() instanceof NotOperator) {

            $operand = $operator->getOperands()[0];

            if ($operand instanceof UnaryOperator && $operand->getParserOperator() instanceof NotOperator) {

                $this->removeItemFromSource($operator, $source, $offset, $itemPos);

                return true;
            }
        }

        return false;
    }

    private function ruleNoNotAloneOperatorsInOrOperator(Operator $operator)
    {
        if ($operator instanceof BinaryOperator && $operator->getParserOperator() instanceof OrOperator) {

            $isProcessed = false;

            $operands = [];

            foreach ($operator->getOperands() as $operand) {
                $newOperand = $operand;

                if ($operand instanceof UnaryOperator && $operand->getParserOperator() instanceof NotOperator) {
                    $isProcessed = true;
                    $group = new Group();
                    $group->addChild(new Literal(self::UNIVERSAL_PLACEHOLDER));
                    $group->addChild($operand);
                    $newOperand = $group;
                }
                $operands[] = $newOperand;
            }
            $operator->setOperands($operands);

            return $isProcessed;
        }
        return false;
    }

    //--- end rules

    private function removeItemFromSource($item, $source, $offset, $itemPos)
    {
        if ($item instanceof Group) {
            $method = 'getChildNodes';
        } else {
            if ($item instanceof UnaryOperator) {
                $method = 'getOperands';
            } else {
                throw new \RuntimeException(sprintf('Undefined class "%s"', get_class($item)));
            }
        }

        if ($source instanceof Operator) {
            $operands = $source->getOperands();
            $operands[$itemPos] = $item->$method()[0];
            $source->setOperands($operands);
        }

        if ($source instanceof Container) {
            $source->offsetSet($offset, $item->$method()[0]);
        }

    }
}