<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\Definer;

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

use In2code\In2publishCore\Component\ConfigContainer\Builder;
use In2code\In2publishCore\Component\ConfigContainer\Node\Node;
use In2code\In2publishCore\Component\ConfigContainer\Node\NodeCollection;
use In2code\In2publishCore\Component\ConfigContainer\Validator\DirectoryExistsValidator;
use In2code\In2publishCore\Component\ConfigContainer\Validator\IntegerInRangeValidator;
use In2code\In2publishCore\Component\ConfigContainer\Validator\IPv4PortValidator;
use In2code\In2publishCore\Component\ConfigContainer\Validator\ZipExtensionInstalledValidator;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class In2publishCoreDefiner implements DefinerInterface
{
    protected array $defaultIgnoredTables = [
        'be_groups',
        'be_users',
        'cache_treelist',
        'sys_history',
        'sys_log',
        'tx_extensionmanager_domain_model_extension',
        'tx_in2code_in2publish_task',
        'tx_in2code_rpc_data',
        'tx_in2code_rpc_request',
        'tx_in2publishcore_filepublisher_task',
        'tx_in2publishcore_log',
        'tx_in2publishcore_running_request',
    ];

    public function getLocalDefinition(): NodeCollection
    {
        return Builder::start()
                      ->addArray(
                          'foreign',
                          Builder::start()
                                 ->addString('rootPath', '/var/www/html/public')
                                 ->addString('varPath', '/var/www/html/var')
                                 ->addString('pathToPhp', '/usr/bin/env php')
                                 ->addString('context', 'Production/Live')
                                 ->addOptionalString('dispatcher', '')
                                 ->addArray(
                                     'envVars',
                                     Builder::start()->addGenericScalar(Node::T_STRING),
                                     []
                                 )
                                 ->addArray(
                                     'database',
                                     Builder::start()
                                            ->addString('name', 'database_123')
                                            ->addString('username', 'username_123')
                                            ->addOptionalString('password', 'Password_123')
                                            ->addString('hostname', '127.0.0.1')
                                            ->addInteger('port', 3306, [IPv4PortValidator::class])
                                 )
                      )
                      ->addArray(
                          'excludeRelatedTables',
                          Builder::start()->addGenericScalar(Node::T_INTEGER),
                          $this->defaultIgnoredTables
                      )
                      ->addOptionalArray(
                          'ignoreFieldsForDifferenceView',
                          Builder::start()
                                 ->addGenericArray(
                                     Node::T_STRING,
                                     Builder::start()
                                            ->addGenericScalar(Node::T_INTEGER)
                                 ),
                      )
                      ->addArray(
                          'ignoredFields',
                          Builder::start()
                                 ->addGenericArray(
                                     Node::T_STRING,
                                     Builder::start()
                                            ->addGenericArray(
                                                Node::T_STRING,
                                                Builder::start()
                                                       ->addGenericScalar(Node::T_INTEGER)
                                            )
                                 ),
                          [
                              '.*' => [
                                  'ctrl' => [
                                      'tstamp',
                                      'versioningWS',
                                      'transOrigDiffSourceField',
                                  ],
                              ],
                              'pages' => [
                                  'fields' => [
                                      'perms_userid',
                                      'perms_groupid',
                                      'perms_user',
                                      'perms_group',
                                      'perms_everybody',
                                      'SYS_LASTCHANGED',
                                  ],
                              ],
                              'sys_redirect' => [
                                  'fields' => [
                                      'source_host',
                                      'hitcount',
                                      'lasthiton',
                                  ],
                              ],
                              'sys_file' => [
                                  'fields' => [
                                      'last_indexed',
                                  ],
                              ],
                          ]
                      )
                      ->addArray(
                          'factory',
                          Builder::start()
                                 ->addBoolean('resolvePageRelations', false)
                                 ->addBoolean('includeSysFileReference', false)
                                 ->addBoolean('treatRemovedAndDeletedAsDifference', false)
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
                                 ->addBoolean('keepEnvelopes', false)
                                 ->addBoolean('traceDemand', false)
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
                          'features',
                          Builder::start()
                                 ->addArray(
                                     'contextMenuPublishEntry',
                                     Builder::start()
                                            ->addBoolean('enable', false)
                                 )
                      )
                      ->end();
    }

    public function getForeignDefinition(): NodeCollection
    {
        return Builder::start()
                      ->addArray(
                          'backup',
                          Builder::start()
                                 ->addArray(
                                     'publishTableCommand',
                                     Builder::start()
                                            ->addInteger('keepBackups', 2, [IntegerInRangeValidator::class => [0, 10]])
                                            ->addString(
                                                'backupLocation',
                                                '/app/foreign/backup',
                                                [DirectoryExistsValidator::class]
                                            )
                                            ->addBoolean('addDropTable', true)
                                            ->addBoolean('zipBackup', true, [ZipExtensionInstalledValidator::class])
                                 )
                      )
                      ->end();
    }
}
