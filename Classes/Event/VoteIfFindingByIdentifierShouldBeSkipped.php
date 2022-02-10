<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\RecordHandling\RecordFinder;

final class VoteIfFindingByIdentifierShouldBeSkipped extends AbstractVotingEvent
{
    private RecordFinder $recordFinder;

    private int $identifier;

    private string $tableName;

    public function __construct(RecordFinder $recordFinder, int $identifier, string $tableName)
    {
        $this->recordFinder = $recordFinder;
        $this->identifier = $identifier;
        $this->tableName = $tableName;
    }

    public function getRecordFinder(): RecordFinder
    {
        return $this->recordFinder;
    }

    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }
}
