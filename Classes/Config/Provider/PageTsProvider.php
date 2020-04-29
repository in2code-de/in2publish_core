<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Config\Provider;

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

use In2code\In2publishCore\Utility\ArrayUtility;
use In2code\In2publishCore\Utility\BackendUtility as In2publishBackendUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility as CoreBackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageTsProvider
 */
class PageTsProvider implements ProviderInterface, ContextualProvider
{
    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->getDatabase() instanceof Connection;
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getConfig()
    {
        $uid = In2publishBackendUtility::getPageIdentifier();
        // get the pageTS | Manually pass rootline to disable caching.
        $pageTs = CoreBackendUtility::getPagesTSconfig($uid);

        $configuration = [];
        if (!empty($pageTs['tx_in2publish.'])) {
            $configuration = ArrayUtility::normalizeArray(GeneralUtility::removeDotsFromTS($pageTs['tx_in2publish.']));
        }
        return $configuration;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return 30;
    }

    /**
     * @return null|Connection
     */
    protected function getDatabase()
    {
        return DatabaseUtility::buildLocalDatabaseConnection();
    }
}
