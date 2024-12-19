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

use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

class InstalledExtensionsExporter implements SystemInformationExporter
{
    protected ListUtility $listUtility;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function __construct(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility)
    {
        $this->listUtility = $listUtility;
    }

    public function getUniqueKey(): string
    {
        return 'extensions';
    }

    public function getInformation(): array
    {
        $packages = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $extensions = [];
        foreach ($packages as $package) {
            $extensions[$package['key']] = [
                'title' => $package['title'],
                'state' => $package['state'],
                'version' => $package['version'],
                'installed' => $package['installed'],
            ];
        }
        return $extensions;
    }
}
