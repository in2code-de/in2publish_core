<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Service;

use In2code\In2publishCore\Component\Core\Reason\Reason;
use In2code\In2publishCore\Component\Core\Reason\Reasons;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Event\CollectReasonsWhyTheRecordIsNotPublishable;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Service\Database\RawRecordService;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

use function implode;

class PublishingStateService
{
    public const REASON_MISSING_DEPENENCY = 1;
    protected EventDispatcher $eventDispatcher;
    protected RawRecordService $rawRecordService;
    protected TcaService $tcaService;

    public function injectEventDispatcher(EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function injectRawRecordService(RawRecordService $rawRecordService): void
    {
        $this->rawRecordService = $rawRecordService;
    }

    public function injectTcaService(TcaService $tcaService): void
    {
        $this->tcaService = $tcaService;
    }

    public function getReasonsWhyTheRecordIsNotPublishable(
        RecordCollection $recordsWhichWillBePublished,
        Record $record
    ) {
        $reasons = new Reasons();

        $dependencies = $record->getDependencies();
        if (!empty($dependencies)) {
            foreach ($dependencies as $dependency) {
                $classification = $dependency->getClassification();
                $properties = $dependency->getProperties();

                if (!$recordsWhichWillBePublished->contains($classification, $properties)) {
                    $tableLabel = $this->tcaService->getTableLabel($classification);
                    if (isset($properties['uid'])) {
                        $uid = $properties['uid'];
                        $row = $this->rawRecordService->getRawRecord($classification, $uid, 'local')
                            ?? $this->rawRecordService->getRawRecord($classification, $uid, 'foreign');
                        $label = $this->tcaService->getRecordLabel($row, $classification);
                        $label .= " ($tableLabel [$uid]";
                        if (isset($row['pid'])) {
                            $label .= " pid: {$row['pid']}";
                        }
                        $label .= ")";
                    } else {
                        $propsString = [];
                        foreach ($properties as $property => $value) {
                            $propsString[] = "$property=$value";
                        }
                        $propsString = implode(', ', $propsString);
                        $label = " ($tableLabel [$propsString])";
                    }
                    $reasons->addReason(
                        new Reason(
                            'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.missing_dependency',
                            [$label]
                        )
                    );
                }
            }
        }

        $event = new CollectReasonsWhyTheRecordIsNotPublishable($record);
        $this->eventDispatcher->dispatch($event);
        if (!$event->isPublishable()) {
            $reasons->addReasons($event->getReasons());
        }

        return $reasons;
    }
}
