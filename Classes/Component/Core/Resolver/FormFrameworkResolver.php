<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\FileDemand;
use In2code\In2publishCore\Component\Core\Record\Model\Record;

use function explode;
use function str_contains;

class FormFrameworkResolver extends AbstractResolver
{
    public function getTargetTables(): array
    {
        return ['tt_content'];
    }

    public function resolve(Demands $demands, Record $record): void
    {
        $file = $record->getLocalProps()['settings.persistenceIdentifier'] ?? null;
        if (empty($file) || !str_contains($file, ':/')) {
            return;
        }
        [$storage, $identifier] = explode(':', $file);
        $demands->addDemand(new FileDemand((int)$storage, $identifier, $record));
    }
}
