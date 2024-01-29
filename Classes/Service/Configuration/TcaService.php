<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Configuration;

/*
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
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function implode;
use function in_array;
use function trigger_error;
use function ucfirst;

use const E_USER_DEPRECATED;

class TcaService implements SingletonInterface
{
    public function getRecordLabel(array $row, string $table): string
    {
        $labelField = $GLOBALS['TCA'][$table]['ctrl']['label'] ?? null;
        $labelAltField = $GLOBALS['TCA'][$table]['ctrl']['label_alt'] ?? null;
        $labelAltFields = [];
        if (null !== $labelAltField) {
            $labelAltFields = GeneralUtility::trimExplode(',', $labelAltField, true);
        }
        $labelAltForce = $GLOBALS['TCA'][$table]['ctrl']['label_alt_force'] ?? false;

        $labels = [];
        if (null !== $labelField && !empty($row[$labelField])) {
            $labels[] = $row[$labelField];
        }
        if (empty($labels) || true === $labelAltForce) {
            foreach ($labelAltFields as $labelAltField) {
                if (!empty($row[$labelAltField])) {
                    $labels[] = $row[$labelAltField];
                }
            }
        }
        return implode(', ', $labels);
    }

    /**
     * Get table name from locallang and TCA definition
     */
    public function getTableLabel(string $tableName): string
    {
        $label = ucfirst($tableName);

        $titleField = $GLOBALS['TCA'][$tableName]['ctrl']['title'] ?? null;

        if (null !== $titleField) {
            $localizedLabel = $GLOBALS['LANG']->sL($titleField);
            if (!empty($localizedLabel)) {
                $label = $localizedLabel;
            }
        }

        return $label;
    }

    public function isHiddenRootTable(string $tableName): bool
    {
        return isset($GLOBALS['TCA'][$tableName]['ctrl']['hideTable'], $GLOBALS['TCA'][$tableName]['ctrl']['rootLevel'])
            && true === (bool)$GLOBALS['TCA'][$tableName]['ctrl']['hideTable']
            && in_array($GLOBALS['TCA'][$tableName]['ctrl']['rootLevel'], [1, -1], true);
    }

    /**
     * @deprecated Please access $GLOBALS['TCA'] directly. This Method will be removed in in2publish_core v13.
     */
    public function getDeletedField(string $tableName): string
    {
        trigger_error(
            '\In2code\In2publishCore\Service\Configuration\TcaService::getDeletedField is deprecated. Please access $GLOBALS[\'TCA\'] directly. This Method will be removed in in2publish_core v13.',
            E_USER_DEPRECATED,
        );
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['delete'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['delete'];
        }
        return '';
    }

    /**
     * @deprecated Please access $GLOBALS['TCA'] directly. This Method will be removed in in2publish_core v13.
     */
    public function getDisableField(string $tableName): string
    {
        trigger_error(
            '\In2code\In2publishCore\Service\Configuration\TcaService::getDisableField is deprecated. Please access $GLOBALS[\'TCA\'] directly. This Method will be removed in in2publish_core v13.',
            E_USER_DEPRECATED,
        );
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'];
        }
        return '';
    }

    /**
     * @deprecated Please access $GLOBALS['TCA'] directly. This Method will be removed in in2publish_core v13.
     */
    public function getLanguageField(string $tableName): string
    {
        trigger_error(
            '\In2code\In2publishCore\Service\Configuration\TcaService::getLanguageField is deprecated. Please access $GLOBALS[\'TCA\'] directly. This Method will be removed in in2publish_core v13.',
            E_USER_DEPRECATED,
        );
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['languageField'];
        }
        return '';
    }

    /**
     * @deprecated Please access $GLOBALS['TCA'] directly. This Method will be removed in in2publish_core v13.
     */
    public function getTransOrigPointerField(string $tableName): string
    {
        trigger_error(
            '\In2code\In2publishCore\Service\Configuration\TcaService::getTransOrigPointerField is deprecated. Please access $GLOBALS[\'TCA\'] directly. This Method will be removed in in2publish_core v13.',
            E_USER_DEPRECATED,
        );
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'];
        }
        return '';
    }
}
