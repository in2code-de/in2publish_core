<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\FalHandling\PostProcessing\EventListener;

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

use In2code\In2publishCore\Component\FalHandling\PostProcessing\PostProcessor;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Event\RecordInstanceWasInstantiated;

class PostProcessingEventListener
{
    private const TABLE_SYS_FILE = 'sys_file';

    protected PostProcessor $postProcessor;

    /** @var RecordInterface[] */
    protected array $registeredInstances = [];

    public function __construct(PostProcessor $postProcessor)
    {
        $this->postProcessor = $postProcessor;
    }

    public function onRecordInstanceWasInstantiated(RecordInstanceWasInstantiated $event): void
    {
        $record = $event->getRecord();
        if (
            self::TABLE_SYS_FILE === $record->getTableName()
            && ($record->localRecordExists() || $record->foreignRecordExists())
        ) {
            $this->registeredInstances[] = $record;
        }
    }

    public function onRootRecordCreationWasFinished(): void
    {
        if (empty($this->registeredInstances)) {
            return;
        }
        $records = $this->registeredInstances;
        $this->registeredInstances = [];
        $this->postProcessor->postProcess($records);
    }
}
