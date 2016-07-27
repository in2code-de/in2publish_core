<?php
namespace In2code\In2publishCore\Testing\Data;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Class RequiredTablesDataProvider
 */
class RequiredTablesDataProvider implements SingletonInterface
{
    /**
     * @var Dispatcher
     */
    protected $dispatcher = null;

    /**
     * @var array
     */
    protected $cache = array();

    /**
     * ConfigurationIsCompleteTest constructor.
     */
    public function __construct()
    {
        $this->dispatcher = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
    }

    /**
     * @return array
     */
    public function getRequiredTables()
    {
        if ((empty($this->cache))) {
            $requiredTables = array(
                'tx_in2code_in2publish_log',
                'tx_in2code_in2publish_task',
            );
            $requiredTables = $this->overruleTables($requiredTables);
            $this->cache = $requiredTables;
        }
        return $this->cache;
    }

    /**
     * @param array $tables
     * @return array
     */
    protected function overruleTables(array $tables)
    {
        $returnValue = $this->dispatcher->dispatch(__CLASS__, __FUNCTION__, array($tables));
        if (isset($returnValue[0])) {
            return $returnValue[0];
        }
        return $tables;
    }
}
