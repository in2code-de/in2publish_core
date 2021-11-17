<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SystemInformationExport\Exporter;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

use function php_uname;

use const PHP_OS;
use const PHP_VERSION;

class VersionsExporter implements SystemInformationExporter
{
    protected ConnectionPool $connectionPool;

    public function __construct(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    public function getUniqueKey(): string
    {
        return 'version';
    }

    public function getInformation(): array
    {
        $databases = [];
        foreach ($this->connectionPool->getConnectionNames() as $name) {
            $databases[$name] = $this->connectionPool->getConnectionByName($name)->getServerVersion();
        }

        return [
            'TYPO3' => VersionNumberUtility::getCurrentTypo3Version(),
            'PHP' => PHP_VERSION,
            'DBs' => $databases,
            'OS' => PHP_OS . ' ' . php_uname('r'),
        ];
    }
}
