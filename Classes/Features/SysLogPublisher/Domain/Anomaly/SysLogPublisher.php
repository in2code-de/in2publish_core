<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SysLogPublisher\Domain\Anomaly;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
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

use In2code\In2publishCore\CommonInjection\ForeignDatabaseInjection;
use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\Event\RecordWasPublished;

class SysLogPublisher
{
    use LocalDatabaseInjection;
    use ForeignDatabaseInjection;

    protected const TABLE_SYS_LOG = 'sys_log';

    public function publishSysLog(RecordWasPublished $event): void
    {
        $record = $event->getRecord();
        if ('pages' !== $record->getClassification()) {
            return;
        }

        $sysLog = $this->findLatestSysLogForPage($record->getId());
        if (!empty($sysLog)) {
            unset($sysLog['uid']);
            $this->foreignDatabase->insert(self::TABLE_SYS_LOG, $sysLog);
        }
    }

    protected function findLatestSysLogForPage(int $identifier): ?array
    {
        $query = $this->localDatabase->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from(self::TABLE_SYS_LOG)
              ->where($query->expr()->eq('event_pid', $query->createNamedParameter($identifier)))
              ->orderBy('uid', 'DESC')
              ->setMaxResults(1);
        $result = $query->executeQuery();
        $row = $result->fetchAssociative();
        if (!$row) {
            return null;
        }
        return $row;
    }
}
