<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Command;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de
 * Alex Kellner <alexander.kellner@in2code.de>,
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

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Config\ValidationContainer;
use In2code\In2publishCore\Testing\Tests\Application\ForeignDatabaseConfigTest;
use In2code\In2publishCore\Utility\DatabaseUtility;
use In2code\In2publishCore\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_column;
use function base64_encode;
use function json_encode;
use function serialize;
use function version_compare;

/**
 * Class StatusCommandController (always enabled)
 */
class StatusCommandController extends AbstractCommandController
{
    const ALL_COMMAND = 'in2publish_core:status:all';
    const VERSION_COMMAND = 'in2publish_core:status:version';
    const CONFIGURATION_COMMAND = 'in2publish_core:status:configuration';
    const CONFIGURATION_RAW_COMMAND = 'in2publish_core:status:configurationraw';
    const CREATE_MASKS_COMMAND = 'in2publish_core:status:createmasks';
    const GLOBAL_CONFIGURATION = 'in2publish_core:status:globalconfiguration';
    const TYPO3_VERSION = 'in2publish_core:status:typo3version';
    const DB_INIT_QUERY_ENCODED = 'in2publish_core:status:dbinitqueryencoded';
    const SHORT_SITE_CONFIGURATION = 'in2publish_core:status:shortsiteconfiguration';
    const SITE_CONFIGURATION = 'in2publish_core:status:siteconfiguration';
    const DB_CONFIG_TEST = 'in2publish_core:status:dbconfigtest';
    const CONFIG_FORMAT_TEST = 'in2publish_core:status:configformattest';
    const EXIT_NO_SITE = 250;

    /**
     * Prints all information about the in2publish system
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @internal
     */
    public function allCommand()
    {
        $this->versionCommand();
        $this->createMasksCommand();
        $this->globalConfigurationCommand();
        $this->typo3VersionCommand();
        $this->dbInitQueryEncodedCommand();
        if (version_compare(TYPO3_branch, '9.3', '>=')) {
            $this->shortSiteConfigurationCommand();
        }
    }

    /**
     * Prints the version number of the currently installed in2publish_core extension
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @internal
     *
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function versionCommand()
    {
        $this->outputLine('Version: ' . ExtensionUtility::getExtensionVersion('in2publish_core'));
    }

    /**
     * Prints the configured fileCreateMask and folderCreateMask
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @internal
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function createMasksCommand()
    {
        $this->outputLine('FileCreateMask: ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask']);
        $this->outputLine('FolderCreateMask: ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask']);
    }

    /**
     * Prints global configuration values
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @internal
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function globalConfigurationCommand()
    {
        $this->outputLine(
            'Utf8Filesystem: '
            . (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'])
                ? 'empty'
                : $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'])
        );
        $this->outputLine(
            'adminOnly: '
            . ($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] ?? 'empty')
        );
    }

    /**
     * Prints TYPO3 version
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @internal
     */
    public function typo3VersionCommand()
    {
        $this->outputLine('TYPO3: ' . TYPO3_version);
    }

    /**
     * Prints the initCommands as json and base64 encoded string
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @internal
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function dbInitQueryEncodedCommand()
    {
        $dbInit = '';
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['initCommands'])) {
            $dbInit = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['initCommands'];
        }
        $this->outputLine('DBinit: ' . base64_encode(json_encode($dbInit)));
    }

    /**
     * Prints a base64 encoded json array containing all configured sites
     */
    public function shortSiteConfigurationCommand()
    {
        if (version_compare(TYPO3_branch, '9.3', '>=')) {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $shortInfo = [];
            foreach ($siteFinder->getAllSites() as $site) {
                $shortInfo[$site->getIdentifier()] = [
                    'base' => $site->getBase()->__toString(),
                    'rootPageId' => $site->getRootPageId(),
                ];
            }
            $this->outputLine('ShortSiteConfig: ' . base64_encode(json_encode($shortInfo)));
        } else {
            $this->outputLine('Site configurations do not exist in TYPO3 v8');
        }
    }

    /**
     * Returns a serialized site config for the given page id
     *
     * @param int $pageId
     */
    public function siteConfigurationCommand(int $pageId)
    {
        if (version_compare(TYPO3_branch, '9.3', '>=')) {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            try {
                $site = $siteFinder->getSiteByPageId($pageId);
            } catch (SiteNotFoundException $e) {
                $this->sendAndExit(static::EXIT_NO_SITE);
            }
            $this->outputLine('Site: ' . base64_encode(serialize($site)));
        } else {
            $this->outputLine('Site configurations do not exist in TYPO3 v8');
        }
    }

    /**
     * Reads from the local task table and writes all found hashes for the db config test
     *
     * @internal
     */
    public function dbConfigTestCommand()
    {
        $queryBuilder = DatabaseUtility::buildLocalDatabaseConnection()->createQueryBuilder();
        $predicates = $queryBuilder->expr()->eq(
            'task_type',
            $queryBuilder->createNamedParameter(ForeignDatabaseConfigTest::DB_CONFIG_TEST_TYPE)
        );
        $queryBuilder->select('*')->from('tx_in2code_in2publish_task')->where($predicates);
        $result = $queryBuilder->execute()->fetchAll();
        $this->outputLine('DB Config: ' . base64_encode(json_encode(array_column($result, 'configuration'))));
    }

    /**
     * Tests the configuration on foreign for its format
     */
    public function configFormatTestCommand()
    {
        $container = GeneralUtility::makeInstance(ValidationContainer::class);
        $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
        $definition = $configContainer->getForeignDefinition();
        $actual = GeneralUtility::makeInstance(ConfigContainer::class)->get();
        $definition->validate($container, $actual);
        $errors = $container->getErrors();
        $this->outputLine('Config Format Test: ' . base64_encode(json_encode($errors)));
    }
}
