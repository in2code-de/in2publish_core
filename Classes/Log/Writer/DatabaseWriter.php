<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Log\Writer;

/*
 * Copyright notice
 *
 * (c) 2025 in2code.de and the following authors:
 *
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use LogicException;
use Throwable;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function json_encode;

/**
 * Writes log records into the dedicated in2publish_core log table.
 *
 * The TYPO3 core DatabaseWriter is a dedicated sys_log writer since TYPO3 v14.2 and its
 * "logTable" option is deprecated. This writer maps the fields explicitly instead, so
 * in2publish_core keeps its own log table without triggering deprecations.
 */
class DatabaseWriter extends AbstractWriter
{
    private const LOG_TABLE = 'tx_in2publishcore_log';

    public function writeLog(LogRecord $record): WriterInterface
    {
        try {
            // Avoid ConnectionPool usage prior to boot completion (see TYPO3 #96291).
            if (GeneralUtility::getContainer()->get('boot.state')->complete === false) {
                return $this;
            }
        } catch (LogicException) {
            // A LogicException is thrown if the container is not available yet.
            return $this;
        }

        $data = '';
        $context = $record->getData();
        if (!empty($context)) {
            if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
                $context['exception'] = (string)$context['exception'];
            }
            $data = json_encode($context);
        }

        $fieldValues = [
            'request_id' => $record->getRequestId(),
            'time_micro' => $record->getCreated(),
            'component' => $record->getComponent(),
            'level' => LogLevel::normalizeLevel($record->getLevel()),
            'message' => $record->getMessage(),
            'data' => $data,
        ];

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::LOG_TABLE)
            ->insert(self::LOG_TABLE, $fieldValues);

        return $this;
    }
}
