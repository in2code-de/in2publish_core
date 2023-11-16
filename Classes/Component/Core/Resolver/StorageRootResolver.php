<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\Record\Model\StorageRootFolderRecord;

class StorageRootResolver implements StaticResolver
{
    public function getTargetClassification(): array
    {
        return [FolderRecord::CLASSIFICATION];
    }

    public function getTargetProperties(): array
    {
        return ['identifier'];
    }

    public function resolve(Demands $demands, Record $record): void
    {
        if ($record instanceof StorageRootFolderRecord) {
            $demands->addDemand(new SelectDemand('sys_file_storage', '', 'uid', $record->getProp('storage'), $record));
        }
    }
}
