<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Record;

use In2code\In2publishCore\Component\Core\Record\Model\Record;
use RuntimeException;
use Traversable;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

use function array_pop;
use function array_reverse;
use function count;
use function implode;
use function in_array;
use function is_object;
use function iterator_to_array;

class PageChildrenRecursionViewHelper extends AbstractViewHelper
{
    protected static array $recordStack = [];

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('each', 'array', 'The array or \SplObjectStorage to iterated over', true);
        $this->registerArgument('as', 'string', 'The name of the iteration variable', true);
        $this->registerArgument('key', 'string', 'Variable to assign array key to', false);
        $this->registerArgument('reverse', 'boolean', 'If enabled, the iterator will start with the last element', false, false);
        $this->registerArgument('iteration', 'string', 'The name of the variable to store iteration information (index, cycle, isFirst, isLast, isEven, isOdd)');
    }

    /**
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function render(): string
    {
        $arguments = $this->arguments;
        $renderingContext = $this->renderingContext;
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

        $each = $arguments['each'];
        if ($arguments['reverse'] === true) {
            if (is_object($each)) {
                $each = iterator_to_array($each);
            }
            $each = array_reverse($each, true);
        }
        $iterationData = [
            'index' => 0,
            'cycle' => 1,
            'total' => count($each),
        ];

        $output = '';
        foreach ($each as $keyValue => $singleElement) {
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
            $output .= $this->renderChildren();
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
