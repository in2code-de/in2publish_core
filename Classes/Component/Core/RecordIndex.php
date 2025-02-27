<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core;

use In2code\In2publishCore\Component\Core\Record\Model\Record;

use function array_keys;

class RecordIndex
{
    /**
     * @var RecordCollection<int, Record>
     */
    private RecordCollection $records;
    /** @var array<RecordCollection> */
    private array $recordings = [];

    public function __construct()
    {
        $this->records = new RecordCollection();
    }

    public function addRecord(Record $record): void
    {
        foreach (array_keys($this->recordings) as $recordingName) {
            $this->recordings[$recordingName]->addRecord($record);
        }
        $this->records->addRecord($record);
    }

    public function removeRecordByTableAndIdentifier(string $table, int $identifier): void
    {
        $this->records->removeRecordByTableAndIdentifier($table, $identifier);
    }

    /**
     * @return array<Record>
     */
    public function getRecords(string $classification = null): array
    {
        return $this->records->getRecords($classification);
    }

    /**
     * @param array-key $id
     */
    public function getRecord(string $classification, $id): ?Record
    {
        return $this->records->getRecord($classification, $id);
    }

    /**
     * @return RecordCollection<int, Record>
     */
    public function getRecordCollection(): RecordCollection
    {
        return $this->records;
    }

    /**
     * @return array<Record>
     */
    public function getRecordsByProperties(string $classification, array $properties)
    {
        return $this->records->getRecordsByProperties($classification, $properties);
    }

    public function startRecordingNewRecords(string $name): void
    {
        $this->recordings[$name] = new RecordCollection();
    }

    public function getRecording(string $name): RecordCollection
    {
        return $this->recordings[$name];
    }

    public function stopRecordingAndGetRecords(string $name): RecordCollection
    {
        $records = $this->recordings[$name];
        unset($this->recordings[$name]);
        return $records;
    }
}
