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
 * Class ConfigurationDefinitionProvider
 */
class ConfigurationDefinitionProvider implements SingletonInterface
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
    public function getConfigurationDefinition()
    {
        if (empty($this->cache)) {
            $definition = array(
                'database' => array(
                    'foreign' => array(
                        'name' => 'string',
                        'username' => 'string',
                        'password' => 'string',
                        'hostname' => 'string',
                        'port' => 'integer',
                    ),
                ),
                'excludeRelatedTables' => array(
                    '*:integer' => 'string',
                ),
                'ignoreFieldsForDifferenceView' => array(
                    '*:string' => array(
                        '*:integer' => 'string',
                    ),
                ),
                'factory' => array(
                    'maximumPageRecursion' => 'integer',
                    'maximumContentRecursion' => 'integer',
                    'maximumOverallRecursion' => 'integer',
                    'resolvePageRelations' => 'boolean',
                    'simpleOverviewAndAjax' => 'boolean',
                ),
                'filePreviewDomainName' => array(
                    'local' => 'string',
                    'foreign' => 'string',
                ),
                'log' => array(
                    'logLevel' => 'integer',
                ),
                'view' => array(
                    'records' => array(
                        'filterButtons' => 'boolean',
                        'breadcrumb' => 'boolean',
                    ),
                    'files' => array(
                        'filterButtons' => 'boolean',
                    ),
                ),
                'sshConnection' => array(
                    'host' => 'string',
                    'port' => 'integer',
                    'username' => 'string',
                    'privateKeyFileAndPathName' => 'string',
                    'publicKeyFileAndPathName' => 'string',
                    'privateKeyPassphrase' => 'string|NULL',
                    'foreignKeyFingerprint' => 'string',
                    'foreignKeyFingerprintHashingMethod' => 'string',
                    'foreignRootPath' => 'string',
                    'pathToPhp' => 'string',
                    'ignoreChmodFail' => 'boolean',
                ),
                'module' => array(
                    'm1' => 'boolean',
                    'm3' => 'boolean',
                    'm4' => 'boolean',
                ),
                'debug' => array(
                    'disableParentRecords' => 'boolean',
                    'showForeignKeyFingerprint' => 'boolean',
                    'showRecordDepth' => 'boolean',
                    'showExecutionTime' => 'boolean',
                    'allInformation' => 'boolean',
                    'keepEnvelopes' => 'boolean',
                ),
                'tasks' => array(
                    '*:string' => array(
                        '*' => '*',
                    ),
                ),
                'disableUserConfig' => 'boolean',
                'backup' => array(
                    'publishTableCommand' => array(
                        'keepBackups' => 'integer',
                        'backupLocation' => 'string',
                        'addDropTable' => 'boolean',
                        'zipBackup' => 'boolean',
                    ),
                ),
                'tca' => array(
                    'processor' => array(
                        '*:string' => 'string',
                    ),
                ),
            );
            $this->cache = $this->overruleDefinition($definition);
        }
        return $this->cache;
    }

    /**
     * @param array $definition
     * @return array
     */
    protected function overruleDefinition(array $definition)
    {
        $returnValue = $this->dispatcher->dispatch(__CLASS__, __FUNCTION__, array($definition));
        if (isset($returnValue[0])) {
            return $returnValue[0];
        }
        return $definition;
    }
}
