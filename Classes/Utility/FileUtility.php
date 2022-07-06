<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Utility;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
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
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_pop;
use function array_shift;
use function count;
use function glob;
use function is_array;
use function sprintf;
use function trim;
use function unlink;

class FileUtility
{
    protected static ?Logger $logger = null;

    protected static function initializeLogger(): void
    {
        if (static::$logger === null) {
            static::$logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        }
    }

    /**
     * Removes old Backups which are no longer needed
     */
    public static function cleanUpBackups(int $keepBackups, string $tableName, string $backupFolder): void
    {
        static::initializeLogger();

        $backups = glob($backupFolder . '*_' . $tableName . '.*');

        if (!is_array($backups)) {
            return;
        }
        while (count($backups) >= $keepBackups) {
            $backupFileName = array_shift($backups);
            try {
                if (unlink($backupFileName)) {
                    static::$logger->notice('Deleted old backup "' . $backupFileName . '"');
                } else {
                    static::$logger->error('Could not delete backup "' . $backupFileName . '"');
                }
            } catch (Throwable $exception) {
                static::$logger->critical(
                    'An error occurred while deletion of "' . $backupFileName . '"',
                    [
                        'code' => $exception->getCode(),
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                    ]
                );
            }
        }
    }
}
