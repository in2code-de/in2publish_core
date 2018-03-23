<?php
namespace In2code\In2publishCore\Config\Provider;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Utility\ExtensionUtility;

/**
 * Class VersionedFileProvider
 */
class VersionedFileProvider extends FileProvider
{
    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getConfig()
    {
        $path = $this->getConfigFilePath();

        $version = ExtensionUtility::getExtensionVersion('in2publish_core');
        list($major, $minor, $patch) = explode('.', $version);
        $candidates = [
            implode('.', [$major, $minor, $patch]),
            implode('.', [$major, $minor]),
            implode('.', [$major]),
        ];

        foreach ($candidates as $candidate) {
            $file = $path . $this->contextService->getContext() . 'Configuration_' . $candidate . '.yaml';
            if (file_exists($file)) {
                return \Spyc::YAMLLoad($file);
            }
        }

        return [];
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return 25;
    }
}
