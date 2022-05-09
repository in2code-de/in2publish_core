<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Configuration;

/*
 * Copyright notice
 *
 * (c) 2022 in2code.de and the following authors:
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

use In2code\In2publishCore\Config\ConfigContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_unique;
use function explode;
use function is_array;
use function is_string;
use function preg_match;

class IgnoredFieldsService
{
    protected array $ignoredFields;
    protected array $rtc = [];

    public function injectConfigContainer(ConfigContainer $configContainer): void
    {
        $this->ignoredFields = $configContainer->get('ignoredFields');
    }

    public function getIgnoredFields(string $table): array
    {
        if (!isset($this->rtc[$table])) {
            $tableIgnoredFields = [];

            foreach ($this->ignoredFields as $regEx => $ignoreConfig) {
                if (1 === preg_match('/' . $regEx . '/', $table)) {
                    foreach ($ignoreConfig['fields'] ?? [] as $ignoredField) {
                        $tableIgnoredFields[] = $ignoredField;
                    }
                    foreach ($ignoreConfig['ctrl'] ?? [] as $ignoredCtrl) {
                        $ignoredCtrlFieldNames = $this->getValueByPath(
                            $GLOBALS['TCA'][$table]['ctrl'] ?? [],
                            $ignoredCtrl
                        );
                        if (null === $ignoredCtrlFieldNames) {
                            continue;
                        }
                        if ($ignoredCtrl === 'versioningWS') {
                            $ignoredCtrlFieldNames = 't3ver_oid,t3ver_wsid,t3ver_state,t3ver_stage';
                        }
                        $ignoredCtrlFields = GeneralUtility::trimExplode(',', $ignoredCtrlFieldNames);

                        foreach ($ignoredCtrlFields as $ignoredCtrlField) {
                            $tableIgnoredFields[] = $ignoredCtrlField;
                        }
                    }
                }
            }

            $this->rtc[$table] = array_unique($tableIgnoredFields);
        }
        return $this->rtc[$table];
    }

    protected function getValueByPath(array $array, string $path): ?string
    {
        /** @var array|scalar $value */
        $value = $array;
        $pathParts = explode('.', $path);
        foreach ($pathParts as $pathPart) {
            if (is_array($value) && isset($value[$pathPart])) {
                $value = $value[$pathPart];
            } else {
                return null;
            }
        }
        if (!is_string($value)) {
            return null;
        }
        return $value;
    }
}
