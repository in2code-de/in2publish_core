<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Features\RealUrlSupport\Domain\Model\Task;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Model\Task\AbstractTask;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RealUrlUpdateTask
 */
class RealUrlUpdateTask extends AbstractTask
{
    /**
     *
     */
    public function modifyConfiguration()
    {
        if (!empty($this->configuration['record'])) {
            $record = $this->configuration['record'];
            if ($record instanceof RecordInterface) {
                $data = [];
                $dirtyProperties = $record->getDirtyProperties();
                foreach ($dirtyProperties as $dirtyProperty) {
                    $data[$dirtyProperty] = $record->getLocalProperty($dirtyProperty);
                }
                $this->configuration = [
                    'state' => $record->getState(),
                    'identifier' => $record->getIdentifier(),
                    'table' => $record->getTableName(),
                    'data' => $data,
                ];
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function executeTask(): bool
    {
        $realUrlDataHandler = GeneralUtility::makeInstance(\DmitryDulepov\Realurl\Hooks\DataHandler::class);
        $cdh = GeneralUtility::makeInstance(DataHandler::class);

        $state = $this->configuration['state'];
        $identifier = $this->configuration['identifier'];
        $table = $this->configuration['table'];
        $data = $this->configuration['data'];

        $this->addMessage('Clear RealUrl data for ' . $table . '[' . $identifier . '], state: ' . $state);

        if (RecordInterface::RECORD_STATE_DELETED === $state) {
            $realUrlDataHandler->processCmdmap_deleteAction($table, $identifier);
            $realUrlDataHandler->processCmdmap_postProcess('delete', $table, $identifier);
        } elseif (RecordInterface::RECORD_STATE_MOVED === $state) {
            $realUrlDataHandler->processCmdmap_postProcess('move', $table, $identifier);
        } elseif (RecordInterface::RECORD_STATE_CHANGED === $state) {
            $realUrlDataHandler->processDatamap_afterDatabaseOperations('update', $table, $identifier, $data, $cdh);
        } elseif (RecordInterface::RECORD_STATE_ADDED === $state) {
            $realUrlDataHandler->processDatamap_afterDatabaseOperations('new', $table, 'NEW12345', $data, $cdh);
            $realUrlDataHandler->processCmdmap_postProcess('move', $table, $identifier);
            $realUrlDataHandler->processDatamap_afterDatabaseOperations('update', $table, $identifier, $data, $cdh);
        }
    }
}
