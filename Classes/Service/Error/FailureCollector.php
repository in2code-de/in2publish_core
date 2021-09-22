<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Error;

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

use Throwable;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\SingletonInterface;

use function min;

class FailureCollector implements SingletonInterface
{
    protected $failures = [];

    protected $highestSeverity = 7;

    public function addLogRecord(LogRecord $logRecord): void
    {
        $normalizedLevel = LogLevel::normalizeLevel($logRecord->getLevel());
        $this->highestSeverity = min($this->highestSeverity, $normalizedLevel);
        $message = $logRecord->getMessage();
        $data = $logRecord->getData();
        if (isset($data['exception']) && $data['exception'] instanceof Throwable) {
            $message .= ' Exception: ' . $data['exception']->getMessage();
        }
        $this->failures[$message][] = $logRecord;
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    public function getMostCriticalLogLevel(): int
    {
        return $this->highestSeverity;
    }
}
