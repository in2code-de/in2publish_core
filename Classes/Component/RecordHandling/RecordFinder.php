<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RecordHandling;

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

use In2code\In2publishCore\Domain\Model\RecordInterface;

interface RecordFinder
{
    /**
     * Overview: The record is show in the "Publish Overview" module or any other place where the level of detail can
     * be traded for performance. It is sufficient to display the record with its most important relations.
     */
    public function findRecordByUidForOverview(int $uid, string $table, bool $excludePages = false): ?RecordInterface;

    /**
     * Publishing: Build the record with all relations, but without any child pages.
     */
    public function findRecordByUidForPublishing(int $uid, string $table): ?RecordInterface;

    /**
     * @return array<RecordInterface>
     */
    public function findRecordsByProperties(array $properties, string $table, bool $simulateRoot = false): array;
}
