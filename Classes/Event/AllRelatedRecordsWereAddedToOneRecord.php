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

use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\RecordInterface;

use function trigger_error;

use const E_USER_DEPRECATED;

final class AllRelatedRecordsWereAddedToOneRecord
{
    /** @var RecordFactory */
    private $recordFactory;

    /** @var RecordInterface */
    private $record;

    public function __construct(RecordFactory $recordFactory, RecordInterface $record)
    {
        $this->recordFactory = $recordFactory;
        $this->record = $record;
    }

    /** @deprecated It is not guaranteed that the record was created using the RecordFactory. You can get an instance of the RecordFactory via GeneralUtility::makeInstance or dependency injection if you absolutely need it, it's a singleton. This getter will be removed in in2publish_core v11. */
    public function getRecordFactory(): RecordFactory
    {
        trigger_error(
            'The method \In2code\In2publishCore\Event\AllRelatedRecordsWereAddedToOneRecord::getRecordFactory is deprecated. It is not guaranteed that the record was created using the RecordFactory. You can get an instance of the RecordFactory via GeneralUtility::makeInstance or dependency injection if you absolutely need it, it\'s a singleton. This getter will be removed in in2publish_core v11.',
            E_USER_DEPRECATED
        );
        return $this->recordFactory;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }
}
