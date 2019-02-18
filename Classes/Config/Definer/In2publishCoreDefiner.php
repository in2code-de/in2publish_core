<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Config\Definer;

/*
 * Copyright notice
 *
 * (c) 2018 in2code.de and the following authors:
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

use In2code\In2publishCore\Config\Builder;
use In2code\In2publishCore\Config\Node\Node;
use In2code\In2publishCore\Config\Node\NodeCollection;
use In2code\In2publishCore\Config\Validator\DirectoryExistsValidator;
use In2code\In2publishCore\Config\Validator\IntegerInRangeValidator;
use In2code\In2publishCore\Config\Validator\IPv4PortValidator;
use In2code\In2publishCore\Config\Validator\IterativeTcaProcessorValidator;
use In2code\In2publishCore\Config\Validator\ZipExtensionInstalledValidator;
use In2code\In2publishCore\Domain\Service\Processor\CheckProcessor;
use In2code\In2publishCore\Domain\Service\Processor\FlexProcessor;
use In2code\In2publishCore\Domain\Service\Processor\GroupProcessor;
use In2code\In2publishCore\Domain\Service\Processor\ImageManipulationProcessor;
use In2code\In2publishCore\Domain\Service\Processor\InlineProcessor;
use In2code\In2publishCore\Domain\Service\Processor\InputProcessor;
use In2code\In2publishCore\Domain\Service\Processor\NoneProcessor;
use In2code\In2publishCore\Domain\Service\Processor\PassthroughProcessor;
use In2code\In2publishCore\Domain\Service\Processor\RadioProcessor;
use In2code\In2publishCore\Domain\Service\Processor\SelectProcessor;
use In2code\In2publishCore\Domain\Service\Processor\SlugProcessor;
use In2code\In2publishCore\Domain\Service\Processor\TextProcessor;
use In2code\In2publishCore\Domain\Service\Processor\UserProcessor;

/**
 * Class In2publishCoreDefiner
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class In2publishCoreDefiner implements DefinerInterface
{
    /**
     * @var array
     */
    protected $defaultIgnoredFields = [
        'pages' => [
            'pid',
            'uid',
            't3ver_oid',
            't3ver_id',
            't3ver_wsid',
            't3ver_label',
            't3ver_state',
            't3ver_stage',
            't3ver_count',
            't3ver_tstamp',
            't3ver_move_id',
            't3_origuid',
            'tstamp',
            'sorting',
            'perms_userid',
            'perms_groupid',
            'perms_user',
            'perms_group',
            'perms_everybody',
            'crdate',
            'cruser_id',
            'SYS_LASTCHANGED',
        ],
        'physical_folder' => [
            'absolutePath',
            'ino',
            'mode',
            'nlink',
            'uid',
            'gid',
            'rdev',
            'size',
            'atime',
            'mtime',
            'ctime',
            'blksize',
            'blocks',
        ],
        'sys_file' => [
            'modification_date',
            'creation_date',
            'tstamp',
            'last_indexed',
        ],
    ];

    /**
     * @var array
     */
    protected $defaultIgnoredTables = [
        'be_groups',
        'be_users',
        'sys_history',
        'sys_log',
        'tx_extensionmanager_domain_model_extension',
        'tx_extensionmanager_domain_model_repository',
        'sys_domain',
        'cache_treelist',
        'tx_in2publishcore_log',
        'tx_in2code_in2publish_task',
        'tx_in2code_in2publish_envelope',
    ];

    /**
     * @return NodeCollection
     */
    public function getLocalDefinition()
    {
        return Builder::start()
                      ->addArray(
                          'foreign',
                          Builder::start()
                                 ->addString('rootPath', '/var/www/html')
                                 ->addString('pathToPhp', '/usr/bin/env php')
                                 ->addString('context', 'Production/Live')
                                 ->addArray(
                                     'database',
                                     Builder::start()
                                            ->addString('name', 'database_123')
                                            ->addString('username', 'username_123')
                                            ->addString('password', 'Password_123')
                                            ->addString('hostname', '127.0.0.1')
                                            ->addInteger('port', 3306, [IPv4PortValidator::class])
                                 )
                      )
                      ->addArray(
                          'excludeRelatedTables',
                          Builder::start()->addGenericScalar(Node::T_INTEGER, Node::T_STRING),
                          $this->defaultIgnoredTables
                      )
                      ->addArray(
                          'ignoreFieldsForDifferenceView',
                          Builder::start()
                                 ->addGenericArray(
                                     Node::T_STRING,
                                     Builder::start()
                                            ->addGenericScalar(Node::T_INTEGER, Node::T_STRING)
                                 ),
                          $this->defaultIgnoredFields
                      )
                      ->addArray(
                          'factory',
                          Builder::start()
                                 ->addInteger('maximumPageRecursion', 2)
                                 ->addInteger('maximumContentRecursion', 6)
                                 ->addInteger('maximumOverallRecursion', 8)
                                 ->addBoolean('resolvePageRelations', false)
                                 ->addBoolean('includeSysFileReference', false)
                                 ->addArray(
                                     'fal',
                                     Builder::start()
                                            ->addBoolean('reserveSysFileUids', false)
                                            ->addBoolean('reclaimSysFileEntries', false)
                                            ->addBoolean('autoRepairFolderHash', false)
                                            ->addBoolean('mergeSysFileByIdentifier', false)
                                            ->addBoolean('enableSysFileReferenceUpdate', false)
                                 )
                      )
                      ->addArray(
                          'filePreviewDomainName',
                          Builder::start()
                                 ->addString('local', 'stage.example.com')
                                 ->addString('foreign', 'www.example.com')
                      )
                      ->addArray(
                          'view',
                          Builder::start()
                                 ->addArray(
                                     'records',
                                     Builder::start()
                                            ->addBoolean('filterButtons', true)
                                            ->addBoolean('breadcrumb', false)
                                 )
                                 ->addArray(
                                     'files',
                                     Builder::start()
                                            ->addBoolean('filterButtons', true)
                                 )
                                 ->addString('titleField', 'title')
                      )
                      ->addArray(
                          'module',
                          Builder::start()
                                 ->addBoolean('m1', true)
                                 ->addBoolean('m3', true)
                                 ->addBoolean('m4', true)
                      )
                      ->addArray(
                          'debug',
                          Builder::start()
                                 ->addBoolean('disableParentRecords', false)
                                 ->addBoolean('showForeignKeyFingerprint', false)
                                 ->addBoolean('showRecordDepth', false)
                                 ->addBoolean('showExecutionTime', true)
                                 ->addBoolean('allInformation', false)
                                 ->addBoolean('keepEnvelopes', false)
                      )
                      ->addArray(
                          'backup',
                          Builder::start()
                                 ->addArray(
                                     'publishTableCommand',
                                     Builder::start()
                                            ->addInteger('keepBackups', 2, [IntegerInRangeValidator::class => [0, 10]])
                                            ->addString(
                                                'backupLocation',
                                                '/var/backup/',
                                                [DirectoryExistsValidator::class]
                                            )
                                            ->addBoolean('addDropTable', true)
                                            ->addBoolean('zipBackup', true, [ZipExtensionInstalledValidator::class])
                                 )
                      )
                      ->addArray(
                          'tca',
                          Builder::start()
                                 ->addArray(
                                     'processor',
                                     Builder::start()
                                            ->addString('check', CheckProcessor::class)
                                            ->addString('flex', FlexProcessor::class)
                                            ->addString('group', GroupProcessor::class)
                                            ->addString('inline', InlineProcessor::class)
                                            ->addString('input', InputProcessor::class)
                                            ->addString('none', NoneProcessor::class)
                                            ->addString('passthrough', PassthroughProcessor::class)
                                            ->addString('radio', RadioProcessor::class)
                                            ->addString('select', SelectProcessor::class)
                                            ->addString('text', TextProcessor::class)
                                            ->addString('user', UserProcessor::class)
                                            ->addString('imageManipulation', ImageManipulationProcessor::class)
                                            ->addString('slug', SlugProcessor::class),
                                     null,
                                     [IterativeTcaProcessorValidator::class]
                                 )
                      )
                      ->end();
    }

    /**
     * @return NodeCollection
     */
    public function getForeignDefinition()
    {
        return Builder::start()->end();
    }
}
