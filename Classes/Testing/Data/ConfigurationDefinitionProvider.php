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
    protected $cache = [];

    /**
     * ConfigurationIsCompleteTest constructor.
     */
    public function __construct()
    {
        $this->dispatcher = GeneralUtility::makeInstance(Dispatcher::class);
    }

    /**
     * @return array
     */
    public function getConfigurationDefinition()
    {
        if (empty($this->cache)) {
            $definition = [
                'foreign' => [
                    'rootPath' => 'string',
                    'pathToPhp' => 'string',
                    'context' => 'string',
                    'database' => [
                        'name' => 'string',
                        'username' => 'string',
                        'password' => 'string',
                        'hostname' => 'string',
                        'port' => 'integer',
                    ],
                ],
                'excludeRelatedTables' => [
                    '*:integer' => 'string',
                ],
                'ignoreFieldsForDifferenceView' => [
                    '*:string' => [
                        '*:integer' => 'string',
                    ],
                ],
                'factory' => [
                    'maximumPageRecursion' => 'integer',
                    'maximumContentRecursion' => 'integer',
                    'maximumOverallRecursion' => 'integer',
                    'resolvePageRelations' => 'boolean',
                    'simpleOverviewAndAjax' => 'boolean',
                    'fal' => [
                        'reserveSysFileUids' => 'boolean',
                        'reclaimSysFileEntries' => 'boolean',
                        'autoRepairFolderHash' => 'boolean',
                        'mergeSysFileByIdentifier' => 'boolean',
                        'enableSysFileReferenceUpdate' => 'boolean',
                    ],
                ],
                'filePreviewDomainName' => [
                    'local' => 'string',
                    'foreign' => 'string',
                ],
                'log' => [
                    'logLevel' => 'integer',
                ],
                'view' => [
                    'records' => [
                        'filterButtons' => 'boolean',
                        'breadcrumb' => 'boolean',
                    ],
                    'files' => [
                        'filterButtons' => 'boolean',
                    ],
                ],
                'module' => [
                    'm1' => 'boolean',
                    'm3' => 'boolean',
                    'm4' => 'boolean',
                ],
                'debug' => [
                    'disableParentRecords' => 'boolean',
                    'showForeignKeyFingerprint' => 'boolean',
                    'showRecordDepth' => 'boolean',
                    'showExecutionTime' => 'boolean',
                    'allInformation' => 'boolean',
                    'keepEnvelopes' => 'boolean',
                ],
                'tasks' => [
                    '*:string' => [
                        '*' => '*',
                    ],
                ],
                'disableUserConfig' => 'boolean',
                'backup' => [
                    'publishTableCommand' => [
                        'keepBackups' => 'integer',
                        'backupLocation' => 'string',
                        'addDropTable' => 'boolean',
                        'zipBackup' => 'boolean',
                    ],
                ],
                'tca' => [
                    'processor' => [
                        '*:string' => 'string',
                    ],
                ],
                'adapter' => [
                    'remote' => 'string',
                    'transmission' => 'string',
                ],
            ];
            $this->cache = $this->overruleDefinition($definition);
        }
        return $this->cache;
    }

    /**
     * @param array $definition
     *
     * @return array
     */
    protected function overruleDefinition(array $definition)
    {
        $returnValue = $this->dispatcher->dispatch(__CLASS__, 'overruleDefinition', [$definition]);
        if (isset($returnValue[0])) {
            return $returnValue[0];
        }
        return $definition;
    }
}
