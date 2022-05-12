<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Publisher;

use In2code\In2publishCore\Domain\Model\AbstractDatabaseRecord;
use In2code\In2publishCore\Domain\Model\Record;
use TYPO3\CMS\Core\Database\Connection;

use function array_diff_assoc;

class DatabaseRecordPublisher implements Publisher, TransactionalPublisher
{
    protected Connection $foreignDatabase;

    public function injectForeignDatabase(Connection $foreignDatabase): void
    {
        $this->foreignDatabase = $foreignDatabase;
    }

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
        $this->foreignDatabase->update($table, $newValues, $foreignIdentificationProps);
    }

    public function start(): void
    {
        $this->foreignDatabase->beginTransaction();
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
