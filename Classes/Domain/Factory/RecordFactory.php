<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Factory;

/*
 * Copyright notice
 *
 * (c) 2022 in2code.de and the following authors:
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

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Model\MmDatabaseRecord;

use function array_keys;
use function array_unique;
use function implode;
use function preg_match;

class RecordFactory
{
    protected ConfigContainer $configContainer;

    protected array $ignoredFields;

    protected array $rtc = [];

    public function injectConfigContainer(ConfigContainer $configContainer): void
    {
        $this->configContainer = $configContainer;
        $this->ignoredFields = $configContainer->get('ignoreFieldsForDifferenceView');
    }

    public function createDatabaseRecord(string $table, int $id, array $localProps, array $foreignProps): DatabaseRecord
    {
        $tableIgnoredFields = $this->getIgnoredFields($table);
        $record = new DatabaseRecord($table, $id, $localProps, $foreignProps, $tableIgnoredFields);
        return $record;
    }

    public function createMmRecord(
        string $table,
        string $propertyHash,
        array $localProps,
        array $foreignProps
    ): MmDatabaseRecord {
        return new MmDatabaseRecord($table, $propertyHash, $localProps, $foreignProps);
    }

    public function createRootRecord(): DatabaseRecord
    {
        return new DatabaseRecord('pages', 0, [], [], []);
    }

    protected function getIgnoredFields(string $table): array
    {
        if (!isset($this->rtc[$table])) {
            $tableIgnoredFields = [];

            foreach ($this->ignoredFields as $regEx => $ignoredFields) {
                if (1 === preg_match('/' . $regEx . '/', $table)) {
                    foreach ($ignoredFields as $ignoredField) {
                        $tableIgnoredFields[] = $ignoredField;
                    }
                }
            }

            $this->rtc[$table] = array_unique($tableIgnoredFields);
        }
        return $this->rtc[$table];
    }
}
