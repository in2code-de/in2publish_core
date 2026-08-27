<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Record;

use In2code\In2publishCore\Component\Core\Record\Model\Record;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class GetChangedDescendantsViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('record', Record::class, 'The root record', true);
        $this->registerArgument('maxDepth', 'int', 'Maximum descendant depth', false, 8);
    }

    /**
     * @return array<string, array<string|int, Record>>
     */
    public function render(): array
    {
        /** @var Record $record */
        $record = $this->arguments['record'];
        $visited = [$record->getClassification() => [$record->getId() => true]];
        $changedDescendants = [];
        $this->collectChangedDescendants(
            $record,
            0,
            max(0, $this->arguments['maxDepth']),
            $visited,
            $changedDescendants,
        );
        return $changedDescendants;
    }

    /**
     * @param array<string, array<string|int, true>> $visited
     * @param array<string, array<string|int, Record>> $changedDescendants
     */
    protected function collectChangedDescendants(
        Record $record,
        int $depth,
        int $maxDepth,
        array &$visited,
        array &$changedDescendants
    ): void {
        if ($depth >= $maxDepth) {
            return;
        }

        foreach ($record->getChildren() as $children) {
            foreach ($children as $child) {
                $classification = $child->getClassification();
                $id = $child->getId();
                if (isset($visited[$classification][$id])) {
                    continue;
                }
                $visited[$classification][$id] = true;

                if ('pages' === $classification) {
                    continue;
                }
                if ($child->isChanged() && !str_starts_with($classification, '_')) {
                    $changedDescendants[$classification][$id] = $child;
                }
                $this->collectChangedDescendants(
                    $child,
                    $depth + 1,
                    $maxDepth,
                    $visited,
                    $changedDescendants,
                );
            }
        }
    }
}
