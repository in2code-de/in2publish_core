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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class UserTsProvider
 */
class UserTsProvider implements ProviderInterface, ContextualProvider
{
    public function isAvailable(): bool
    {
        return $this->getBackendUser() instanceof BackendUserAuthentication;
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getConfig(): array
    {
        $config = [];
        $userTs = $this->getBackendUser()->getTSConfig();
        $userTs = GeneralUtility::removeDotsFromTS($userTs);
        if (isset($userTs['tx_in2publish'])) {
            $config = ArrayUtility::normalizeArray((array)$userTs['tx_in2publish']);
        }
        return $config;
    }

    public function getPriority(): int
    {
        return 40;
    }

    /**
     * @return BackendUserAuthentication|null
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
