<?php
namespace In2code\In2publishCore\Domain\Factory;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileIndexFactory
 */
class FileIndexFactory
{
    /**
     * @var DriverInterface
     */
    protected $localDriver = null;

    /**
     * @var DriverInterface
     */
    protected $foreignDriver = null;

    /**
     * @var array
     */
    protected $sysFileTca = array();

    /**
     * @var array
     */
    protected $configuration = array();

    /**
     * FileIndexFactory constructor.
     * @param DriverInterface $localDriver
     * @param DriverInterface $foreignDriver
     */
    public function __construct(DriverInterface $localDriver, DriverInterface $foreignDriver)
    {
        $this->localDriver = $localDriver;
        $this->foreignDriver = $foreignDriver;
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $this->foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
        $this->sysFileTca = GeneralUtility::makeInstance('In2code\\In2publishCore\\Service\\Configuration\\TcaService')
                                          ->getConfigurationArrayForTable('sys_file');
        $this->configuration = ConfigurationUtility::getConfiguration('factory.fal');
    }

    /**
     * @param array $localProperties
     * @param array $foreignProperties
     * @return RecordInterface
     */
    public function makeInstance(array $localProperties, array $foreignProperties)
    {
        return GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Model\\Record',
            'sys_file',
            $localProperties,
            $foreignProperties,
            $this->sysFileTca,
            array('localRecordExistsTemporary' => true, 'foreignRecordExistsTemporary' => true)
        );
    }

    /**
     * @param string $side
     * @param array $properties
     * @return RecordInterface
     */
    public function makeInstanceForSide($side, array $properties)
    {
        return GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Model\\Record',
            'sys_file',
            $side === 'local' ? $properties : array(),
            $side === 'foreign' ? $properties : array(),
            $this->sysFileTca,
            array($side . 'RecordExistsTemporary' => true)
        );
    }
}
