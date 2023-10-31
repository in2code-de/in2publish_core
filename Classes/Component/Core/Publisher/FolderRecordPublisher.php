<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\Publisher\Instruction\DeleteFolderInstruction;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;

class FolderRecordPublisher extends AbstractFilesystemPublisher
{
    public function canPublish(Record $record): bool
    {
        return $record instanceof FolderRecord;
    }

    public function publish(Record $record): void
    {
        $recordState = $record->getState();

        $instruction = null;
        switch ($recordState) {
            case Record::S_DELETED:
                $storage = (int)$record->getForeignProps()['storage'];
                $identifier = $record->getForeignProps()['identifier'];
                $instruction = new DeleteFolderInstruction(
                    $storage,
                    $identifier,
                );
                break;
            case Record::S_ADDED:
                $storage = (int)$record->getLocalProps()['storage'];
                $identifier = $record->getLocalProps()['identifier'];
                $instruction = new DeleteFolderInstruction(
                    $storage,
                    $identifier,
                );
                break;
        }
        if (null !== $instruction) {
            $this->instructions[] = $instruction;
        }
    }
}
