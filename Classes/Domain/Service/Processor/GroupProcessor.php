<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Service\Processor;

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

use In2code\In2publishCore\Domain\Service\TcaProcessingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function strpos;

class GroupProcessor extends AbstractProcessor
{
    public const INTERNAL_TYPE = 'internal_type';
    public const INTERNAL_TYPE_DB = 'db';
    public const INTERNAL_TYPE_FILE = 'file';
    public const INTERNAL_TYPE_FILE_REFERENCE = 'file_reference';
    public const ALLOWED = 'allowed';
    public const UPLOAD_FOLDER = 'uploadfolder';

    /**
     * @var bool
     */
    protected $canHoldRelations = true;

    /**
     * @var array
     */
    protected $forbidden = [
        'relations are only resolved from the owning side, MM_oppositeUsage marks the opposite side' => self::MM_OPPOSITE_USAGE,
        'MM_opposite_field is set for the foreign side of relations, which must not be resolved' => self::MM_OPPOSITE_FIELD,
    ];

    /**
     * @var array
     */
    protected $required = [
        'the internal type determines the relation target' => self::INTERNAL_TYPE,
    ];

    /**
     * @var array
     */
    protected $allowed = [
        self::ALLOWED,
        self::FOREIGN_TABLE,
        self::MM,
        self::MM_HAS_UID_FIELD,
        self::MM_MATCH_FIELDS,
        self::MM_TABLE_WHERE,
        self::UPLOAD_FOLDER,
    ];

    public function canPreProcess(array $config): bool
    {
        if (!parent::canPreProcess($config)) {
            return false;
        }

        $internalType = $config[static::INTERNAL_TYPE];

        if ($internalType === static::INTERNAL_TYPE_DB) {
            return $this->canPreProcessInternalTypeDb($config);
        }

        if ($internalType === static::INTERNAL_TYPE_FILE) {
            return $this->canPreProcessInternalTypeFile($config);
        }

        if ($internalType === static::INTERNAL_TYPE_FILE_REFERENCE) {
            return $this->canPreProcessInternalTypeFileReference($config);
        }

        $this->lastReasons[static::INTERNAL_TYPE] = 'The internal type "' . $internalType . '" is not supported';
        return false;
    }

    protected function canPreProcessInternalTypeFile(array $config): bool
    {
        if (empty($config[static::UPLOAD_FOLDER])) {
            $this->lastReasons[static::INTERNAL_TYPE] =
                'The internal type "'
                . static::INTERNAL_TYPE_FILE
                . '" is missing an "uploadfolder" and can not be resolved';
            return false;
        }
        return true;
    }

    protected function canPreProcessInternalTypeFileReference(array $config): bool
    {
        if (false === empty($config[static::UPLOAD_FOLDER])) {
            $this->lastReasons[static::INTERNAL_TYPE] =
                'The internal type "'
                . static::INTERNAL_TYPE_FILE_REFERENCE
                . '" has an unwanted "uploadfolder" and can not be resolved';
            return false;
        }
        return true;
    }

    protected function canPreProcessInternalTypeDb(array $config): bool
    {
        $referencesAllowed = isset($config[static::ALLOWED]);
        $referencesTable = isset($config[static::FOREIGN_TABLE]);
        if ($referencesAllowed) {
            return $this->canPreProcessInternalTypeDbAllowed($config[static::ALLOWED]);
        }
        if ($referencesTable) {
            return $this->canPreProcessInternalTypeDbTable($config[static::FOREIGN_TABLE]);
        }
        $this->lastReasons[static::INTERNAL_TYPE] = 'There is neither "allowed" nor "foreign_tables" defined';
        return false;
    }

    public function canPreProcessInternalTypeDbTable(string $table): bool
    {
        if (!TcaProcessingService::tableExists($table)) {
            $this->lastReasons[static::INTERNAL_TYPE] =
                'Can not reference the table "' . $table . '" from "foreign_table. It is not present in the TCA';
            return false;
        }
        return true;
    }

    protected function canPreProcessInternalTypeDbAllowed(string $allowed): bool
    {
        if ($allowed === '') {
            $this->lastReasons[static::INTERNAL_TYPE] = '"allowed" is empty, there is no table to match';
            return false;
        }

        if ($allowed === '*') {
            return true;
        }

        if (strpos($allowed, ',')) {
            $allowedTables = GeneralUtility::trimExplode(',', $allowed);
        } else {
            $allowedTables = [$allowed];
        }
        foreach ($allowedTables as $table) {
            if (!TcaProcessingService::tableExists($table)) {
                $this->lastReasons[static::INTERNAL_TYPE] =
                    'Can not reference the table "' . $table . '" from "allowed. It is not present in the TCA';
                return false;
            }
        }
        return true;
    }
}
