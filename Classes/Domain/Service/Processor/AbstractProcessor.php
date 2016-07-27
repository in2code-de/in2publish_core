<?php
namespace In2code\In2publishCore\Domain\Service\Processor;

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

use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractProcessor
 */
abstract class AbstractProcessor implements ProcessorInterface
{
    /**
     * Overwrite and set TRUE for types like "select" or "inline"
     *
     * @var bool
     */
    protected $canHoldRelations = false;

    /**
     * Do not overwrite this!
     * Stores the reasons for a column configuration not being suitable for resolving relations
     *
     * @var array
     */
    protected $lastReasons = array();

    /**
     * Overwrite this in your Processor if canHoldRelations is TRUE!
     * Fields that are forbidden for the processor type, indexed by the reasong
     *    e.g. "itemsProcfunc is not supported by in2publish" => "itemsProcFunc"
     *
     * @var array
     */
    protected $forbidden = array();

    /**
     * Overwrite this in your Processor if canHoldRelations is TRUE!
     * Field names that must be contained in the configuration, indexed by their reason why
     *    e.g. "can not select without a foreign table" => "foreign_table"
     *
     * @var array
     */
    protected $required = array();

    /**
     * Overwrite this in your Processor if canHoldRelations is TRUE and these are needed!
     * Fields that are optional and are which taken into account for relations solving. Has no specific index
     *    e.g. "foreign_table_where"
     *
     * @var array
     */
    protected $allowed = array();

    /**
     * Overwrite this in your Processor if canHoldRelations is TRUE!
     *
     *
     *
     * @param array $config
     * @return array
     */
    public function preProcess(array $config)
    {
        $return = array();
        foreach ($this->getImportantFields() as $field) {
            if (!empty($config[$field])) {
                $return[$field] = $config[$field];
            }
        }
        return $return;
    }

    /**
     * @param array $config
     * @return bool
     */
    public function canPreProcess(array $config)
    {
        $this->lastReasons = array();
        foreach ($this->forbidden as $reason => $key) {
            if (array_key_exists($key, $config)) {
                $this->lastReasons[$key] = $reason;
            }
        }
        foreach ($this->required as $reason => $key) {
            if (!array_key_exists($key, $config)) {
                $this->lastReasons[$key] = $reason;
            }
        }
        return empty($this->lastReasons);
    }


    /**********************************************
     *
     *    STUFF YOU SHOULD NOT WORRY ABOUT
     *
     **********************************************/

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * AbstractProcessor constructor.
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(get_class($this));
    }

    /**
     * Return TRUE if the TCA type can hold relations. FALSE if for "stupid" or boolean types like "check" or "radio"
     *
     * @return bool
     */
    public function canHoldRelations()
    {
        return $this->canHoldRelations;
    }

    /**
     * @return array
     */
    public function getLastReasons()
    {
        return $this->lastReasons;
    }

    /**
     * @return array
     */
    protected function getImportantFields()
    {
        return array_merge($this->required, $this->allowed);
    }
}
