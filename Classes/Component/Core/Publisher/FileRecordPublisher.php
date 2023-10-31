<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\FileHandling\Service\FalDriverServiceInjection;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\AddFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\DeleteFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\MoveFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\ReplaceAndRenameFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\ReplaceFileInstruction;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\AssetTransmitterInjection;

class FileRecordPublisher extends AbstractFilesystemPublisher
{
    use AssetTransmitterInjection;
    use FalDriverServiceInjection;

    public function canPublish(Record $record): bool
    {
        return $record instanceof FileRecord;
    }

    public function publish(Record $record): void
    {
        $recordState = $record->getState();
        $localProps = $record->getLocalProps();
        $foreignProps = $record->getForeignProps();

        $instruction = null;

        switch ($recordState) {
            case Record::S_DELETED:
                $instruction = new DeleteFileInstruction(
                    (int)$foreignProps['storage'],
                    $foreignProps['identifier'],
                );
                break;
            case Record::S_ADDED:
                $storage = (int)$localProps['storage'];
                $transmitTemporaryFile = $this->transmitTemporaryFile($record);
                $identifier = $localProps['identifier'];
                $instruction = new AddFileInstruction(
                    $storage,
                    $transmitTemporaryFile,
                    $identifier,
                );
                break;
            case Record::S_MOVED:
                $storage = (int)$localProps['storage'];
                $oldFileIdentifier = $foreignProps['identifier'];
                $newFileIdentifier = $localProps['identifier'];
                $instruction = new MoveFileInstruction(
                    $storage,
                    $oldFileIdentifier,
                    $newFileIdentifier,
                );
                break;
            case Record::S_CHANGED:
                $storage = (int)$localProps['storage'];
                $localFileIdentifier = $localProps['identifier'];
                $foreignFileIdentifier = $foreignProps['identifier'];
                if ($localFileIdentifier !== $foreignFileIdentifier) {
                    if ($localProps['sha1'] === $foreignProps['sha1']) {
                        $instruction = new MoveFileInstruction(
                            $storage,
                            $foreignFileIdentifier,
                            $localFileIdentifier,
                        );
                    } else {
                        $transmitTemporaryFile = $this->transmitTemporaryFile($record);
                        $instruction = new ReplaceAndRenameFileInstruction(
                            $storage,
                            $foreignFileIdentifier,
                            $localFileIdentifier,
                            $transmitTemporaryFile,
                        );
                    }
                } else {
                    $transmitTemporaryFile = $this->transmitTemporaryFile($record);
                    $instruction = new ReplaceFileInstruction(
                        $storage,
                        $localFileIdentifier,
                        $transmitTemporaryFile,
                    );
                }
                break;
        }
        if (null !== $instruction) {
            $this->instructions[] = $instruction;
        }
    }

    protected function transmitTemporaryFile(Record $record): string
    {
        $storage = (int)$record->getLocalProps()['storage'];
        $identifier = $record->getLocalProps()['identifier'];
        $driver = $this->falDriverService->getDriver($storage);
        $localFile = $driver->getFileForLocalProcessing($identifier);
        return $this->assetTransmitter->transmitTemporaryFile($localFile);
    }
}
