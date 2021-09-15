<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Service;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
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

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function implode;
use function preg_replace_callback;
use function str_replace;
use function strpos;

/**
 * Replace markers in TCA definition
 */
class ReplaceMarkersService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    // Also replace optional quotes around the REC_FIELD_ because we will quote the actual value
    protected const REC_FIELD_REGEX = '~\'?###REC_FIELD_(.*?)###\'?~';

    /** @var FlexFormTools */
    protected $flexFormTools;

    /**
     * ReplaceMarkersService constructor.
     */
    public function __construct(FlexFormTools $flexFormTools)
    {
        $this->flexFormTools = $flexFormTools;
    }

    /**
     * replaces ###MARKER### where possible. It's missing
     * a lot of Markers support due to lack of documentation
     * If a Marker could not be replaced a Log is written.
     * This should, however, not be needed.
     *
     * @param RecordInterface $record
     * @param string $string
     * @param string $propertyName
     *
     * @return string
     */
    public function replaceMarkers(RecordInterface $record, string $string, string $propertyName): string
    {
        if (strpos($string, '#') !== false) {
            $string = $this->replaceRecFieldMarker($record, $string);
            $string = $this->replacePageMarker($string, $record);
            $string = $this->replacePageTsConfigMarkers($record, $string, $propertyName);
            $string = $this->replaceStaticMarker($string);
            $this->checkForMarkersAndErrors($string);
        }
        return $string;
    }

    /**
     * replaces ###MARKER### where possible. It's missing
     * a lot of Markers support due to lack of documentation
     * If a Marker could not be replaced a Log is written.
     * This should, however, not be needed.
     *
     * @param RecordInterface $record
     * @param string $string
     * @param string $propertyName
     * @param string $key
     * @return string
     */
    public function replaceFlexFormMarkers(
        RecordInterface $record,
        string $string,
        string $propertyName,
        string $key
    ): string {
        if (strpos($string, '#') !== false) {
            $string = $this->replaceRecFieldMarker($record, $string);
            $string = $this->replacePageMarker($string, $record);
            $string = $this->replaceFlexFormFieldMarkers($record, $string, $propertyName, $key);
            $string = $this->replaceStaticMarker($string);
            $this->checkForMarkersAndErrors($string);
        }
        return $string;
    }

    /**
     * Replace ###REC_FIELD_fieldname### with it's value
     *
     * @param RecordInterface $record
     * @param string $string
     *
     * @return string
     */
    protected function replaceRecFieldMarker(RecordInterface $record, string $string): string
    {
        if (strpos($string, '###REC_FIELD_') !== false) {
            $string = preg_replace_callback(
                self::REC_FIELD_REGEX,
                function ($matches) use ($record) {
                    $propertyName = $matches[1];
                    $propertyValue = $record->getLocalProperty($propertyName);
                    if ($propertyValue === null) {
                        $propertyValue = $record->getForeignProperty($propertyName);
                    }
                    return DatabaseUtility::quoteString((string)$propertyValue);
                },
                $string
            );
        }
        return $string;
    }

    protected function replacePageMarker(string $string, RecordInterface $record): string
    {
        if (false !== strpos($string, '###CURRENT_PID###')) {
            if (null !== ($currentPid = $record->getPageIdentifier())) {
                $string = str_replace('###CURRENT_PID###', (string)$currentPid, $string);
            }
        }
        if (false !== strpos($string, '###THIS_UID###')) {
            if (null !== ($identifier = $record->getIdentifier())) {
                $string = str_replace('###THIS_UID###', $identifier, $string);
            }
        }
        if (false !== strpos($string, '###STORAGE_PID###')) {
            if (null !== ($storagePid = $this->getStoragePidFromPage($record->getPageIdentifier()))) {
                $string = str_replace('###STORAGE_PID###', (string)$storagePid, $string);
            }
        }
        return $string;
    }

    /**
     * Replace default marker names
     *
     * @param RecordInterface $record
     * @param string $string
     * @param string $propertyName
     *
     * @return mixed
     */
    protected function replacePageTsConfigMarkers(RecordInterface $record, string $string, string $propertyName)
    {
        if (false !== strpos($string, '###PAGE_TSCONFIG')) {
            $marker = [
                'PAGE_TSCONFIG_ID' => function ($input) {
                    return (int)$input;
                },
                'PAGE_TSCONFIG_IDLIST' => function ($input) {
                    return implode(
                        ',',
                        GeneralUtility::intExplode(',', $input)
                    );
                },
                'PAGE_TSCONFIG_STR' => function ($input) {
                    return DatabaseUtility::quoteString($input);
                },
            ];

            $pageTsConfig = $this->getPagesTsConfig($record->getPageIdentifier());
            $tableIndex = $record->getTableName() . '.';
            $fieldIndex = $propertyName . '.';
            foreach ($marker as $markerName => $filterFunc) {
                if (false !== strpos($string, '###' . $markerName . '###')) {
                    $value = $pageTsConfig['TCEFORM.'][$tableIndex][$fieldIndex][$markerName] ?? null;
                    $cleanValue = $filterFunc($value);
                    $string = str_replace('###' . $markerName . '###', $cleanValue, $string);
                }
            }
        }
        return $string;
    }

    protected function replaceStaticMarker(string $string): string
    {
        return str_replace(
            [
                '###THIS_CID###',
                '###SITEROOT###',
            ],
            [
                0,
                '#_SITEROOT',
            ],
            $string
        );
    }

    private function replaceFlexFormFieldMarkers(
        RecordInterface $record,
        string $string,
        string $propertyName,
        string $key
    ): string {
        if (false !== strpos($string, '###PAGE_TSCONFIG')) {
            $tableName = $record->getTableName();

            $marker = [
                'PAGE_TSCONFIG_ID' => function ($input) {
                    return (int)$input;
                },
                'PAGE_TSCONFIG_IDLIST' => function ($input) {
                    return implode(
                        ',',
                        GeneralUtility::intExplode(',', $input)
                    );
                },
                'PAGE_TSCONFIG_STR' => function ($input) {
                    return DatabaseUtility::quoteString($input);
                },
            ];

            $pageTs = BackendUtility::getPagesTSconfig($record->getPageIdentifier());
            $dataStructIdentifier = $this->flexFormTools->getDataStructureIdentifier(
                ['config' => $record->getColumnsTca()[$propertyName]],
                $tableName,
                $propertyName,
                $record->getLocalProperties()
            );
            $simpleStructId = $this->getSimplifiedDataStructureIdentifier($dataStructIdentifier);
            if (empty($pageTs['TCEFORM.'][$tableName . '.'][$propertyName . '.'][$simpleStructId . '.'])) {
                return $string;
            }
            $flexPageTs = $pageTs['TCEFORM.'][$tableName . '.'][$propertyName . '.'][$simpleStructId . '.'];

            foreach ($flexPageTs as $sheets) {
                foreach ($sheets as $field => $values) {
                    if ($field === $key . '.') {
                        foreach ($marker as $markerName => $filterFunc) {
                            if (false !== strpos($string, '###' . $markerName . '###')) {
                                $value = $values[$markerName] ?? 0;
                                $cleanValue = $filterFunc($value);
                                $string = str_replace('###' . $markerName . '###', $cleanValue, $string);
                            }
                        }
                        break 2;
                    }
                }
            }
        }
        return $string;
    }

    /**
     * @see \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::getSimplifiedDataStructureIdentifier
     */
    protected function getSimplifiedDataStructureIdentifier(string $dataStructureIdentifier): string
    {
        $identifierArray = json_decode($dataStructureIdentifier, true);

        if (
            isset($identifierArray['type'], $identifierArray['dataStructureKey'])
            && 'tca' === $identifierArray['type']
        ) {
            $explodedKey = explode(',', $identifierArray['dataStructureKey']);
            if (!empty($explodedKey[1]) && $explodedKey[1] !== 'list' && $explodedKey[1] !== '*') {
                return $explodedKey[1];
            }

            if (!empty($explodedKey[0]) && $explodedKey[0] !== 'list' && $explodedKey[0] !== '*') {
                return $explodedKey[0];
            }
        }

        return 'default';
    }

    /**
     * Log if markers are not substituted or if there are errors
     *
     * @param $string
     *
     * @return void
     */
    protected function checkForMarkersAndErrors($string): void
    {
        if (strpos($string, '###') !== false) {
            $this->logger->error('Could not replace marker', ['string' => $string]);
        } elseif (strpos($string, '#') !== false) {
            $this->logger->warning('Marker replacement not implemented', ['string' => $string]);
        }
    }

    /**
     * @param int $pageId
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function getStoragePidFromPage(int $pageId): int
    {
        $rootLine = BackendUtility::BEgetRootLine($pageId);
        foreach ($rootLine as $page) {
            if (!empty($page['storage_pid'])) {
                return (int)$page['storage_pid'];
            }
        }
        return 0;
    }

    protected function getPagesTsConfig(int $pageIdentifier): array
    {
        return BackendUtility::getPagesTSconfig($pageIdentifier);
    }
}
