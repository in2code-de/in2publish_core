<?php
declare(strict_types=1);
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

use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DatabaseSchemaService
 */
class DatabaseSchemaService implements SingletonInterface
{
    /**
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * @throws NoSuchCacheException
     */
    public function __construct()
    {
        $this->cache = $this->getCache();
    }

    /**
     * @param string $tableName
     * @return bool
     */
    public function tableExists(string $tableName): bool
    {
        $database = DatabaseUtility::buildLocalDatabaseConnection();

        return $database ? $database->getSchemaManager()->tablesExist([$tableName]) : false;
    }

    /**
     * @throws NoSuchCacheException
     *
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function getCache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('in2publish_core');
    }
}
