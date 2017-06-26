<?php
namespace In2code\In2publishCore\Domain\Service\Processor;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Domain\Service\TcaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class GroupProcessor
 */
class GroupProcessor extends AbstractProcessor
{
    const INTERNAL_TYPE = 'internal_type';
    const INTERNAL_TYPE_DB = 'db';
    const INTERNAL_TYPE_FILE = 'file';
    const ALLOWED = 'allowed';
    const UPLOAD_FOLDER = 'uploadfolder';

    /**
     * @var bool
     */
    protected $canHoldRelations = true;

    /**
     * @var array
     */
    protected $forbidden = [
        'relations are only resolved from the owning side, MM_oppositeUsage marks the opposite side' =>
            self::MM_OPPOSITE_USAGE,
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

    /**
     * @param array $config
     * @return bool
     */
    public function canPreProcess(array $config)
    {
        if (!parent::canPreProcess($config)) {
            return false;
        }

        $internalType = $config[self::INTERNAL_TYPE];

        if ($internalType === self::INTERNAL_TYPE_DB) {
            return $this->canPreProcessInternalTypeDb($config);
        } elseif ($internalType === self::INTERNAL_TYPE_FILE) {
            return $this->canPreProcessInternalTypeFile($config);
        }

        $this->lastReasons[self::INTERNAL_TYPE] = 'The internal type "' . $internalType . '" is not supported';
        return false;
    }

    /**
     * @param array $config
     * @return bool
     */
    protected function canPreProcessInternalTypeFile(array $config)
    {
        if (empty($config[self::UPLOAD_FOLDER])) {
            $this->lastReasons[self::INTERNAL_TYPE] =
                'group internal type file without "uploadfolder" can not be resolved';
            return false;
        }
        return true;
    }

    /**
     * @param array $config
     * @return bool
     */
    protected function canPreProcessInternalTypeDb(array $config)
    {
        $referencesAllowed = isset($config[self::ALLOWED]);
        $referencesTable = isset($config[self::FOREIGN_TABLE]);
        if ($referencesAllowed) {
            return $this->canPreProcessInternalTypeDbAllowed($config[self::ALLOWED]);
        } elseif ($referencesTable) {
            return $this->canPreProcessInternalTypeDbTable($config[self::FOREIGN_TABLE]);
        }
        $this->lastReasons[self::INTERNAL_TYPE] = 'There is neither "allowed" nor "foreign_tables" defined';
        return false;
    }

    /**
     * @param string $table
     * @return bool
     */
    public function canPreProcessInternalTypeDbTable($table)
    {
        if (!TcaService::tableExists($table)) {
            $this->lastReasons[self::INTERNAL_TYPE] =
                'Can not reference the table "' . $table . '" from "foreign_table. It is not present in the TCA';
            return false;
        }
        return true;
    }

    /**
     * @param string $allowed
     * @return bool
     */
    protected function canPreProcessInternalTypeDbAllowed($allowed)
    {
        if ($allowed === '') {
            $this->lastReasons[self::INTERNAL_TYPE] = '"allowed" is empty, there is no table to match';
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
            if (!TcaService::tableExists($table)) {
                $this->lastReasons[self::INTERNAL_TYPE] =
                    'Can not reference the table "' . $table . '" from "allowed. It is not present in the TCA';
                return false;
            }
        }
        return true;
    }
}
