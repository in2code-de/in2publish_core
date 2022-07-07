<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Utility;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
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

use Doctrine\DBAL\Driver\Exception;
use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Service\Environment\ForeignEnvironmentService;
use LogicException;
use PDO;
use Throwable;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function in_array;

class DatabaseUtility
{
    protected static ?Logger $logger = null;
    protected static ?Connection $foreignConnection = null;

    /**
     * @throws Throwable
     */
    public static function buildForeignDatabaseConnection(): ?Connection
    {
        static::initializeLogger();
        if (static::$foreignConnection === null) {
            $configuration = GeneralUtility::makeInstance(ConfigContainer::class)->get('foreign.database');
            if (null === $configuration) {
                static::$logger->warning('Can not instantiate the foreign database connection without a configuration');
                static::$foreignConnection = null;
            } else {
                $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                $foreignEnvService = GeneralUtility::makeInstance(ForeignEnvironmentService::class);
                try {
                    $initCommands = $foreignEnvService->getDatabaseInitializationCommands();
                } catch (Throwable $exception) {
                    static::$logger->error(
                        'Exception in ForeignEnvironmentService. ' . $exception->getMessage(),
                        ['exception' => $exception]
                    );
                    throw $exception;
                }

                /** @noinspection PhpInternalEntityUsedInspection */
                if (!in_array('in2publish_foreign', $connectionPool->getConnectionNames(), true)) {
                    $default = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
                    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['in2publish_foreign'] = [
                        'dbname' => $configuration['name'],
                        'driver' => 'mysqli',
                        'host' => $configuration['hostname'],
                        'password' => $configuration['password'],
                        'port' => $configuration['port'],
                        'user' => $configuration['username'],
                        'charset' => $default['charset'],
                        'tableoptions' => $default['tableoptions'] ?? [],
                    ];
                }
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['in2publish_foreign']['initCommands'] = $initCommands;

                try {
                    $foreignConnection = $connectionPool->getConnectionByName('in2publish_foreign');
                    foreach ($foreignConnection->getEventManager()->getListeners() as $event => $listeners) {
                        foreach ($listeners as $listener) {
                            $foreignConnection->getEventManager()->removeEventListener($event, $listener);
                        }
                    }
                    static::$foreignConnection = $foreignConnection;
                    $foreignConnection->connect();
                } catch (Throwable $e) {
                    static::$logger->critical('Can not connect to foreign database', ['exception' => $e]);
                    static::$foreignConnection = null;
                }
            }
        }

        return static::$foreignConnection;
    }

    public static function buildLocalDatabaseConnection(): ?Connection
    {
        try {
            return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @param string $side
     *
     * @return Connection|null
     * @throws Throwable
     */
    public static function buildDatabaseConnectionForSide(string $side): ?Connection
    {
        if ($side === 'local') {
            return static::buildLocalDatabaseConnection();
        }
        if ($side === 'foreign') {
            return static::buildForeignDatabaseConnection();
        }
        throw new LogicException('Unsupported side "' . $side . '"', 1476118055);
    }

    protected static function initializeLogger(): void
    {
        if (static::$logger === null) {
            static::$logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        }
    }

    /**
     * Copied from deprecated QueryGenerator
     * see: Deprecation-92080-DeprecatedQueryGeneratorAndQueryView.html
     *
     * Recursively fetch all descendants of a given page
     *
     * @param int $id uid of the page
     * @param int $depth
     * @param int $begin
     * @param string $permClause
     *
     * @return string comma separated list of descendant pages
     *
     * @throws Exception
     *
     * @noinspection PhpMissingParamTypeInspection
     * @SuppressWarnings(PHPMD)
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection CallableParameterUseCaseInTypeContextInspection
     */
    public static function getTreeList($id, $depth, $begin = 0, $permClause = ''): string
    {
        $depth = (int)$depth;
        $begin = (int)$begin;
        $id = (int)$id;
        if ($id < 0) {
            $id = abs($id);
        }
        if ($begin === 0) {
            $theList = $id;
        } else {
            $theList = '';
        }
        if ($id && $depth > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder
                ->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
                ->orderBy('uid');
            if ($permClause !== '') {
                $queryBuilder->andWhere(self::stripLogicalOperatorPrefix($permClause));
            }
            $statement = $queryBuilder->execute();
            while ($row = $statement->fetchAssociative()) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theSubList = self::getTreeList($row['uid'], $depth - 1, $begin - 1, $permClause);
                    if (!empty($theList) && !empty($theSubList) && ($theSubList[0] !== ',')) {
                        $theList .= ',';
                    }
                    $theList .= $theSubList;
                }
            }
        }
        return (string)$theList;
    }

    /**
     * Copied from deprecated QueryHelper
     * see: Deprecation-92080-DeprecatedQueryGeneratorAndQueryView.html
     *
     * Removes the prefixes AND/OR from the input string.
     *
     * This function should be used when you can't guarantee that the string
     * that you want to use as a WHERE fragment is not prefixed.
     *
     * @param string $constraint The where part fragment with a possible leading "AND" or "OR" operator
     * @return string The modified where part without leading operator
     */
    public static function stripLogicalOperatorPrefix(string $constraint): string
    {
        return preg_replace('/^(?:(AND|OR)[[:space:]]*)+/i', '', trim($constraint)) ?: '';
    }
}
