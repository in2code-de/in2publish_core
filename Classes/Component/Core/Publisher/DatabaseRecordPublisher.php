<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

use In2code\In2publishCore\CommonInjection\ForeignDatabaseReconnectedInjection;
use In2code\In2publishCore\Component\Core\Record\Model\AbstractDatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;

use function array_diff_assoc;

class DatabaseRecordPublisher implements Publisher, TransactionalPublisher
{
    use ForeignDatabaseReconnectedInjection;

    public function canPublish(Record $record): bool
    {
        return $record instanceof AbstractDatabaseRecord;
    }

    public function publish(Record $record)
    {
        $table = $record->getClassification();
        $localProps = $record->getLocalProps();

        if ($record->getState() === Record::S_ADDED) {
            $this->foreignDatabase->insert($table, $localProps);
            return;
        }

        $foreignIdentificationProps = $record->getForeignIdentificationProps();

        if ($record->getState() === Record::S_DELETED) {
            $this->foreignDatabase->delete($table, $foreignIdentificationProps);
            return;
        }

        $foreignProps = $record->getForeignProps();
        $newValues = array_diff_assoc($localProps, $foreignProps);
        if (!empty($newValues)) {
            $this->foreignDatabase->update($table, $newValues, $foreignIdentificationProps);
        }
    }

    public function start(): void
    {
        if (!$this->foreignDatabase->isTransactionActive()) {
            $this->foreignDatabase->beginTransaction();
        }
    }

    public function cancel(): void
    {
        if ($this->foreignDatabase->isTransactionActive()) {
            $this->foreignDatabase->rollBack();
        }
    }

    public function finish(): void
    {
        if ($this->foreignDatabase->isTransactionActive()) {
            $this->foreignDatabase->commit();
        }
    }
}
