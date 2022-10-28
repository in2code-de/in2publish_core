<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RecordBreadcrumbs\Domain\Extensions;

use In2code\In2publishCore\Component\Core\Record\Iterator\IterationControls\SkipChildren;
use In2code\In2publishCore\Component\Core\Record\Iterator\RecordIterator;
use In2code\In2publishCore\Component\Core\Record\Model\Record;

trait BreadcrumbExtension
{
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];

        /**
         * @param Record $record
         * @param array<Record> $stack
         * @throws SkipChildren
         */
        $closure = function (Record $record, array $stack) use (&$breadcrumbs): void {
            $classification = $record->getClassification();
            if ('pages' === $classification && count($stack) > 1) {
                throw new SkipChildren();
            }
            if ($record->isChanged()) {
                $breadcrumb = [];
                foreach ($stack as $record) {
                    $breadcrumb[] = "{$record->getClassification()} [{$record->getId()}]";
                }
                $breadcrumbs[] = implode(' / ', $breadcrumb);
            }
        };
        $recordIterator = new RecordIterator();
        $recordIterator->recurse($this, $closure);
        return $breadcrumbs;
    }
}
