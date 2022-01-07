<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Utility;

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

use Doctrine\DBAL\Driver\Exception as DriverException;
use In2code\In2publishCore\Domain\Driver\RemoteFileAbstractionLayerDriver;
use ReflectionException;
use ReflectionProperty;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function get_class;

class StorageDriverExtractor
{
    /**
     * @param ResourceStorage $localStorage
     * @return DriverInterface
     * @throws ReflectionException
     */
    public static function getLocalDriver(ResourceStorage $localStorage): DriverInterface
    {
        $driverProperty = new ReflectionProperty(get_class($localStorage), 'driver');
        $driverProperty->setAccessible(true);
        return $driverProperty->getValue($localStorage);
    }

    /**
     * @param ResourceStorage $localStorage
     * @return RemoteFileAbstractionLayerDriver
     * @throws DriverException
     */
    public static function getForeignDriver(ResourceStorage $localStorage): RemoteFileAbstractionLayerDriver
    {
        $foreignDriver = GeneralUtility::makeInstance(RemoteFileAbstractionLayerDriver::class);
        $foreignDriver->setStorageUid($localStorage->getUid());
        $foreignDriver->initialize();
        return $foreignDriver;
    }
}
