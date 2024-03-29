<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\HideRecordsDeletedDifferently\EventListener;

/*
 * Copyright notice
 *
 * (c) 2023 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Component\Core\Record\Model\AbstractDatabaseRecord;
use In2code\In2publishCore\Event\DecideIfRecordShouldBeIgnored;

class HideRecordsDeletedDifferentlyEventListener
{
    public function decideIfRecordShouldBeIgnored(DecideIfRecordShouldBeIgnored $event): void
    {
        $record = $event->getRecord();

        // Only database records can be soft deleted
        if (!$record instanceof AbstractDatabaseRecord) {
            return;
        }

        $table = $record->getClassification();

        $deleteField = $GLOBALS['TCA'][$table]['ctrl']['delete'] ?? null;
        // Records without a delete field can not be soft deleted
        if (empty($deleteField)) {
            return;
        }

        $localProps = $record->getLocalProps();
        $foreignProps = $record->getForeignProps();

        if (
            (empty($localProps) && ($foreignProps[$deleteField] ?? false))
            || (empty($foreignProps) && ($localProps[$deleteField] ?? false))
        ) {
            $event->shouldIgnore();
        }
    }
}
