<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Utility;

/*
 * Copyright notice
 *
 * (c) 2019 in2code.de and the following authors:
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

use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Messaging\AbstractMessage;

class LogUtility
{
    public static function translateLogLevelToSeverity(int $logLevel): int
    {
        switch (LogLevel::getInternalName($logLevel)) {
            case LogLevel::DEBUG:
                $severity = AbstractMessage::NOTICE;
                break;
            case LogLevel::INFO:
                $severity = AbstractMessage::OK;
                break;
            case LogLevel::NOTICE:
                $severity = AbstractMessage::INFO;
                break;
            case LogLevel::WARNING:
                $severity = AbstractMessage::WARNING;
                break;
            case LogLevel::ERROR:
            case LogLevel::CRITICAL:
            case LogLevel::ALERT:
            case LogLevel::EMERGENCY:
            default:
                $severity = AbstractMessage::ERROR;
                break;
        }
        return $severity;
    }
}
