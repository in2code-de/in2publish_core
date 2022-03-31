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

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function explode;
use function gettype;
use function implode;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function json_decode;
use function preg_replace_callback;
use function sprintf;
use function str_replace;
use function strpos;

use const JSON_THROW_ON_ERROR;

/**
 * Replace markers in TCA definition
 */
class ReplaceMarkersService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    // Also replace optional quotes around the REC_FIELD_ because we will quote the actual value
    protected const REC_FIELD_REGEX = '~\'?###REC_FIELD_(.*?)###\'?~';
    protected const SITE_FIELD_REGEX = '(###SITE:([^#]+)###)';

    protected FlexFormTools $flexFormTools;

    protected TcaPreProcessingService $tcaPreProcessingService;

    protected SiteFinder $siteFinder;

    protected Connection $localDatabase;

    public function __construct(
        FlexFormTools $flexFormTools,
        TcaPreProcessingService $tcaPreProcessingService,
        SiteFinder $siteFinder,
        Connection $localDatabase
    ) {
        $this->flexFormTools = $flexFormTools;
        $this->tcaPreProcessingService = $tcaPreProcessingService;
        $this->siteFinder = $siteFinder;
        $this->localDatabase = $localDatabase;
    }

    /**
     * replaces ###MARKER### where possible. It's missing
     * a lot of Markers support due to lack of documentation
     * If a Marker could not be replaced a Log is written.
     * This should, however, not be needed.
     *
     * @param DatabaseRecord $record
     * @param string $string
     * @param string $propertyName
     *
     * @return string
     */
    public function replaceMarkers(DatabaseRecord $record, string $string, string $propertyName): string
    {
        if (strpos($string, '#') !== false) {
            $string = $this->replaceRecFieldMarker($record, $string);
            $string = $this->replacePageMarker($string, $record);
            $string = $this->replacePageTsConfigMarkers($record, $string, $propertyName);
            $string = $this->replaceStaticMarker($string);
            $string = $this->replaceSiteMarker($string, $record);
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
            $string = $this->replaceSiteMarker($string, $record);
            $this->checkForMarkersAndErrors($string);
        }
        return $string;
    }

    /**
     * Replace ###REC_FIELD_fieldname### with it's value
     *
     * @param DatabaseRecord $record
     * @param string $string
     *
     * @return string
     */
    protected function replaceRecFieldMarker(DatabaseRecord $record, string $string): string
    {
        if (strpos($string, '###REC_FIELD_') !== false) {
            $string = preg_replace_callback(
                self::REC_FIELD_REGEX,
                function ($matches) use ($record) {
                    $propertyName = $matches[1];
                    $propertyValue = $record->getProp($propertyName);
                    return $this->localDatabase->quote((string)$propertyValue);
                },
                $string
            );
        }
        return $string;
    }

    protected function replacePageMarker(string $string, DatabaseRecord $record): string
    {
        $pageIdentifier = $record->getPageId();

        if (false !== strpos($string, '###CURRENT_PID###')) {
            $string = str_replace('###CURRENT_PID###', (string)$pageIdentifier, $string);
        }
        if (false !== strpos($string, '###THIS_UID###')) {
            $string = str_replace('###THIS_UID###', (string)$record->getId(), $string);
        }
        if (false !== strpos($string, '###STORAGE_PID###')) {
            $string = str_replace('###STORAGE_PID###', (string)$this->getStoragePidFromPage($pageIdentifier), $string);
        }
        return $string;
    }

    protected function replacePageTsConfigMarkers(DatabaseRecord $record, string $string, string $propertyName): string
    {
        if (false !== strpos($string, '###PAGE_TSCONFIG')) {
            $marker = [
                'PAGE_TSCONFIG_ID' => fn($input): int => (int)$input,
                'PAGE_TSCONFIG_IDLIST' => fn($input): string => implode(',', GeneralUtility::intExplode(',', $input)),
                'PAGE_TSCONFIG_STR' => fn($input): string => $this->localDatabase->quote($input),
            ];

            $pageTsConfig = $this->getPagesTsConfig($record->getPageId());
            $tableIndex = $record->getTable() . '.';
            $fieldIndex = $propertyName . '.';
            foreach ($marker as $markerName => $filterFunc) {
                if (false !== strpos($string, '###' . $markerName . '###')) {
                    $value = $pageTsConfig['TCEFORM.'][$tableIndex][$fieldIndex][$markerName] ?? null;
                    $cleanValue = (string)$filterFunc($value);
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
                'PAGE_TSCONFIG_ID' => fn($input): int => (int)$input,
                'PAGE_TSCONFIG_IDLIST' => fn($input): string => implode(',', GeneralUtility::intExplode(',', $input)),
                'PAGE_TSCONFIG_STR' => fn($input): string => $this->localDatabase->quote($input),
            ];

            $pageTs = BackendUtility::getPagesTSconfig($record->getPageIdentifier());
            $dataStructIdentifier = $this->flexFormTools->getDataStructureIdentifier(
                ['config' => $this->tcaPreProcessingService->getCompatibleTcaColumns($tableName)[$propertyName]],
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
     * Replaces ###SITE:siteConfigKey### markers in TCA with their respective values
     */
    protected function replaceSiteMarker(string $string, RecordInterface $record): string
    {
        if (false === strpos($string, '###SITE:')) {
            return $string;
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($record->getPageIdentifier());
        } catch (SiteNotFoundException $exception) {
            return $string;
        }

        $configuration = $site->getConfiguration();
        return preg_replace_callback(self::SITE_FIELD_REGEX, function (array $match) use ($configuration): string {
            try {
                $value = ArrayUtility::getValueByPath($configuration, $match[1], '.');
            } catch (MissingArrayPathException $exception) {
                $value = '';
            }
            $key = $match[0];
            return (string)$this->quoteParsedSiteConfiguration([$key => $value])[$key];
        }, $string);
    }

    /**
     * @see \TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider::quoteParsedSiteConfiguration
     */
    protected function quoteParsedSiteConfiguration(array $parsedSiteConfiguration): array
    {
        foreach ($parsedSiteConfiguration as $key => $value) {
            if (is_int($value)) {
                // int values are safe, nothing to do here
                continue;
            }
            if (is_string($value)) {
                $parsedSiteConfiguration[$key] = $this->localDatabase->quote($value);
                continue;
            }
            if (is_array($value)) {
                $parsedSiteConfiguration[$key] = implode(',', $this->quoteParsedSiteConfiguration($value));
                continue;
            }
            if (is_bool($value)) {
                $parsedSiteConfiguration[$key] = (int)$value;
                continue;
            }
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot quote site configuration setting "%s" of type "%s", only "int", "bool", "string" and "array" are supported',
                    $key,
                    gettype($value)
                ),
                1630324435
            );
        }

        return $parsedSiteConfiguration;
    }

    /**
     * @see \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::getSimplifiedDataStructureIdentifier
     */
    protected function getSimplifiedDataStructureIdentifier(string $dataStructureIdentifier): string
    {
        $identifierArray = json_decode($dataStructureIdentifier, true, 512, JSON_THROW_ON_ERROR);

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
