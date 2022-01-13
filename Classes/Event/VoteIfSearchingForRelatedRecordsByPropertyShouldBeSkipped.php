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
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;

use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * @SuppressWarnings(PHPMD.LongClassName) Event names should be descriptive.
 */
final class VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped extends AbstractVotingEvent
{
    private RecordFinder $recordFinder;

    private RecordInterface $record;

    private string $propertyName;

    private array $columnConfiguration;

    public function __construct(
        RecordFinder $recordFinder,
        RecordInterface $record,
        string $propertyName,
        array $columnConfiguration
    ) {
        $this->recordFinder = $recordFinder;
        $this->record = $record;
        $this->propertyName = $propertyName;
        $this->columnConfiguration = $columnConfiguration;
    }

    /**
     * @deprecated This method is deprecated and will be removed in in2publish_core v11, please use
     *     \In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped::getRecordFinder instead.
     */
    public function getCommonRepository(): CommonRepository
    {
        trigger_error(
            'The method \In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped::getCommonRepository is deprecated and will be removed in in2publish_core v11, please use \In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped::getRecordFinder instead.',
            E_USER_DEPRECATED
        );
        return $this->recordFinder;
    }

    public function getRecordFinder(): RecordFinder
    {
        return $this->recordFinder;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getColumnConfiguration(): array
    {
        return $this->columnConfiguration;
    }
}
