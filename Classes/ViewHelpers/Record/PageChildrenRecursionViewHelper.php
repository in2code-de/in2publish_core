<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Record;

use Closure;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use RuntimeException;
use Traversable;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\ViewHelpers\ForViewHelper;

use function array_pop;
use function array_reverse;
use function count;
use function implode;
use function in_array;
use function is_object;
use function iterator_to_array;

class PageChildrenRecursionViewHelper extends ForViewHelper
{
    /**
     * @var array<string, true>
     */
    protected static array $recordStack = [];

    /**
     * @param array $arguments
     * @param Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @throws Exception
     */
    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $templateVariableContainer = $renderingContext->getVariableProvider();
        if (!isset($arguments['each'])) {
            return '';
        }
        if (is_object($arguments['each']) && !$arguments['each'] instanceof Traversable) {
            throw new Exception(
                'PageChildrenRecursionViewHelper only supports arrays and objects implementing \Traversable interface',
                1248728393,
            );
        }

        if ($arguments['reverse'] === true) {
            // array_reverse only supports arrays
            if (is_object($arguments['each'])) {
                /** @var $each Traversable */
                $each = $arguments['each'];
                $arguments['each'] = iterator_to_array($each);
            }
            $arguments['each'] = array_reverse($arguments['each'], true);
        }
        if (isset($arguments['iteration'])) {
            $iterationData = [
                'index' => 0,
                'cycle' => 1,
                'total' => count($arguments['each']),
            ];
        }

        $output = '';
        foreach ($arguments['each'] as $keyValue => $singleElement) {
            if (!$singleElement instanceof Record) {
                throw new RuntimeException('Can not iterate over other elements than record', 1660921900);
            }
            $uniqueRecordKey = $singleElement->getClassification() . '//' . $singleElement->getId();
            if (in_array($uniqueRecordKey, self::$recordStack, true)) {
                $recordStack = implode(', ', self::$recordStack);
                $output .= "<!-- Infinite recursion for record stack $recordStack, trying to display record $uniqueRecordKey -->";
                continue;
            }
            self::$recordStack[] = $uniqueRecordKey;

            $templateVariableContainer->add($arguments['as'], $singleElement);
            if (isset($arguments['key'])) {
                $templateVariableContainer->add($arguments['key'], $keyValue);
            }
            if (isset($arguments['iteration'])) {
                $iterationData['isFirst'] = $iterationData['cycle'] === 1;
                $iterationData['isLast'] = $iterationData['cycle'] === $iterationData['total'];
                $iterationData['isEven'] = $iterationData['cycle'] % 2 === 0;
                $iterationData['isOdd'] = !$iterationData['isEven'];
                $templateVariableContainer->add($arguments['iteration'], $iterationData);
                $iterationData['index']++;
                $iterationData['cycle']++;
            }
            $output .= $renderChildrenClosure();
            $templateVariableContainer->remove($arguments['as']);
            if (isset($arguments['key'])) {
                $templateVariableContainer->remove($arguments['key']);
            }
            if (isset($arguments['iteration'])) {
                $templateVariableContainer->remove($arguments['iteration']);
            }
            array_pop(self::$recordStack);
        }
        return $output;
    }
}
