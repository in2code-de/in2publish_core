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

use In2code\In2publishCore\Component\Core\PreProcessing\TcaPreProcessingService;
use ReflectionObject;

use function get_class;
use function is_array;
use function is_object;

class TcaExporter implements SystemInformationExporter
{
    protected TcaPreProcessingService $tcaPreProcessingService;

    public function __construct(TcaPreProcessingService $tcaPreProcessingService)
    {
        $this->tcaPreProcessingService = $tcaPreProcessingService;
    }

    public function getUniqueKey(): string
    {
        return 'TCA';
    }

    public function getInformation(): array
    {
        $compatibleTcaParts = $this->tcaPreProcessingService->getCompatibleTcaParts();
        return [
            'full' => $GLOBALS['TCA'],
            'compatible' => $this->stripObjectsFromArray($compatibleTcaParts),
            'incompatible' => $this->tcaPreProcessingService->getIncompatibleTcaParts(),
        ];
    }

    protected function stripObjectsFromArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_object($value)) {
                $properties = [];
                $reflectionObject = new ReflectionObject($value);
                foreach ($reflectionObject->getProperties() as $property) {
                    $property->setAccessible(true);
                    $propertyValue = $property->getValue($value);
                    if (is_object($propertyValue)) {
                        $propertyValue = get_class($propertyValue);
                    }
                    if (is_array($propertyValue)) {
                        $propertyValue = $this->stripObjectsFromArray($propertyValue);
                    }
                    $properties[$property->getName()] = $propertyValue;
                }
                $array[$key] = [
                    'class' => get_class($value),
                    'properties' => $properties,
                ];
            }
            if (is_array($value)) {
                $array[$key] = $this->stripObjectsFromArray($value);
            }
        }
        return $array;
    }
}
