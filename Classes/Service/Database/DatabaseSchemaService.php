<?php
namespace In2code\In2publishCore\Service\Database;

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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class DatabaseSchemaService
 */
class DatabaseSchemaService implements SingletonInterface
{
    /**
     * @var array
     */
    protected $cache = array();

    /**
     * TODO: evaluate if caching via caching framework would speed this up
     *
     * @return array
     */
    public function getDatabaseSchema()
    {
        if (!isset($this->cache[__FUNCTION__])) {
            $database = $this->getDatabase();

            $schema = array();
            foreach ($database->admin_get_tables() as $tableName => $tableInfo) {
                $schema[$tableName]['_TABLEINFO'] = $tableInfo;
                foreach ($database->admin_get_fields($tableName) as $fieldName => $fieldInfo) {
                    $schema[$tableName][$fieldName] = $fieldInfo;
                }
            }
            $this->cache[__FUNCTION__] = $schema;
        }

        return $this->cache[__FUNCTION__];
    }

    /**
     * @return DatabaseConnection
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    protected function getDatabase()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
