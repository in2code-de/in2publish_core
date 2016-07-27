<?php
namespace In2code\In2publishCore\Domain\Service;

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

use In2code\In2publishCore\Domain\Service\Processor\AbstractProcessor;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TcaService
 */
class TcaService
{
    const COLUMNS = 'columns';
    const CONFIG = 'config';
    const CONTROL = 'ctrl';
    const TYPE = 'type';
    const TCA = 'TCA';
    const DELETE = 'delete';
    const CACHE_KEY_TCA_COMPATIBLE = 'tca_compatible';
    const CACHE_KEY_TCA_INCOMPATIBLE = 'tca_incompatible';
    const CACHE_KEY_TCA_PROCESSORS = 'tca_processors';
    const CACHE_KEY_CONTROLS = 'controls';

    /**
     * @var TcaService
     */
    protected static $instance = null;

    /**
     * @var array
     */
    protected $defaultProcessor = array(
        'check' => 'In2code\\In2publishCore\\Domain\\Service\\Processor\\CheckProcessor',
        'flex' => 'In2code\\In2publishCore\\Domain\\Service\\Processor\\FlexProcessor',
        'group' => 'In2code\\In2publishCore\\Domain\\Service\\Processor\\GroupProcessor',
        'inline' => 'In2code\\In2publishCore\\Domain\\Service\\Processor\\InlineProcessor',
        'input' => 'In2code\\In2publishCore\\Domain\\Service\\Processor\\InputProcessor',
        'none' => 'In2code\\In2publishCore\\Domain\\Service\\Processor\\NoneProcessor',
        'passthrough' => 'In2code\\In2publishCore\\Domain\\Service\\Processor\\PassthroughProcessor',
        'radio' => 'In2code\\In2publishCore\\Domain\\Service\\Processor\\RadioProcessor',
        'select' => 'In2code\\In2publishCore\\Domain\\Service\\Processor\\SelectProcessor',
        'text' => 'In2code\\In2publishCore\\Domain\\Service\\Processor\\TextProcessor',
        'user' => 'In2code\\In2publishCore\\Domain\\Service\\Processor\\UserProcessor',
    );

    /**
     * @var AbstractProcessor[]
     */
    protected $processors = array();

    /**
     * Stores the part of the TCA that can be used for relation resolving
     *
     * @var array[]
     */
    protected $compatibleTca = array();

    /**
     * Stores the part of the TCA that can not be used for relation resolving including reasons
     *
     * @var array[]
     */
    protected $incompatibleTca = array();

    /**
     * Stores the controls for each table from TCA
     *
     * @var array[]
     */
    protected $controls = array();

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var VariableFrontend
     */
    protected $cache = null;

    /**
     * @return TcaService
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self;
            self::$instance->preProcessTca();
        }
        return self::$instance;
    }

    /**
     * @return TcaService
     */
    protected function __construct()
    {
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(get_class($this));
        $this->cache = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache(
            'in2publish_core'
        );

        $configuredProcessor = ConfigurationUtility::getConfiguration('tca.processor');
        if (is_array($configuredProcessor)) {
            foreach ($configuredProcessor as $type => $class) {
                $this->processors[$type] = new $class();
            }
        } else {
            $this->logger->warning('TCA processors are not defined. Using defaults');
        }
        foreach ($this->defaultProcessor as $type => $class) {
            if (empty($this->processors[$type])) {
                $this->processors[$type] = new $class();
            }
        }
    }

    /**
     * @return void
     */
    protected function preProcessTca()
    {
        if ($this->cache->has(self::CACHE_KEY_TCA_COMPATIBLE)
            && $this->cache->has(self::CACHE_KEY_TCA_INCOMPATIBLE)
            && $this->cache->has(self::CACHE_KEY_CONTROLS)
        ) {
            $this->compatibleTca = $this->cache->get(self::CACHE_KEY_TCA_COMPATIBLE);
            $this->incompatibleTca = $this->cache->get(self::CACHE_KEY_TCA_INCOMPATIBLE);
            $this->controls = $this->cache->get(self::CACHE_KEY_CONTROLS);
        } else {
            $this->preProcessTcaReal();
            $this->cache->set(self::CACHE_KEY_TCA_COMPATIBLE, $this->compatibleTca);
            $this->cache->set(self::CACHE_KEY_TCA_INCOMPATIBLE, $this->incompatibleTca);
            $this->cache->set(self::CACHE_KEY_CONTROLS, $this->controls);
        }
    }

    /**
     * @return void
     */
    protected function preProcessTcaReal()
    {
        foreach (self::getCompleteTca() as $table => $tableConfiguration) {
            if (!empty($tableConfiguration[self::CONTROL][self::DELETE])) {
                $this->controls[$table][self::DELETE] = $tableConfiguration[self::CONTROL][self::DELETE];
            } else {
                $this->controls[$table][self::DELETE] = '';
            }

            foreach ($tableConfiguration[self::COLUMNS] as $column => $columnConfiguration) {
                $config = $columnConfiguration[self::CONFIG];
                $type = $config[self::TYPE];

                // If there's no processor for the type it is not a standard type of TYPO3
                // The incident will be logged and the field will be skipped
                if (empty($this->processors[$type])) {
                    if (!is_string($type)) {
                        $type = gettype($type);
                    }
                    $this->logger->critical(
                        'No Processor for "' . $type . '" found. Skipping configuration',
                        array(
                            'table' => $table,
                            'column' => $column,
                        )
                    );
                    continue;
                }

                // if the field potentially holds relations
                if ($this->processors[$type]->canHoldRelations()) {
                    // check if the field is configured for holding relations
                    if ($this->processors[$type]->canPreProcess($config)) {
                        // Set the preprocessed values
                        $this->compatibleTca[$table][$column] = $this->processors[$type]->preProcess($config);
                        $this->compatibleTca[$table][$column][self::TYPE] = $type;
                    } else {
                        // Set the reasons why it can not be pre processed. Useful for Extension authors
                        foreach ($this->processors[$type]->getLastReasons() as $key => $reason) {
                            $this->incompatibleTca[$table][$column]['type'] = $type;
                            $this->incompatibleTca[$table][$column]['reasons'][$key] = $reason;
                        }
                    }
                } else {
                    $this->incompatibleTca[$table][$column] = 'The type "' . $type . '" can not hold relations';
                }
            }

            // set an empty array as default, prevents NULL values
            if (empty($this->compatibleTca[$table])) {
                $this->compatibleTca[$table] = array();
            }
        }
    }

    /**
     * @return array
     */
    public static function getIncompatibleTca()
    {
        return self::getInstance()->incompatibleTca;
    }

    /**
     * @return array
     */
    public static function getCompatibleTca()
    {
        return self::getInstance()->compatibleTca;
    }

    /**
     * @return array
     */
    public static function getControls()
    {
        return self::getInstance()->controls;
    }

    /**
     * @return array
     */
    public static function getAllTables()
    {
        return array_keys(self::getCompleteTca());
    }

    /**
     * @param string $table
     * @return bool
     */
    public static function tableExists($table)
    {
        return array_key_exists($table, self::getCompleteTca());
    }

    /**
     * @return mixed
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getCompleteTca()
    {
        return $GLOBALS[self::TCA];
    }

    /**
     * @param string $tableName
     * @return array
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getCompleteTcaForTable($tableName)
    {
        return $GLOBALS[self::TCA][$tableName];
    }

    /**
     * @param string $table
     * @return array
     */
    public static function getColumnsFor($table)
    {
        return (array)self::getInstance()->compatibleTca[$table];
    }

    /**
     * @param string $table
     * @return array
     */
    public static function getControlsFor($table)
    {
        return self::getInstance()->controls[$table];
    }

    /**
     * @param string $table
     * @return bool
     */
    public static function hasDeleteField($table)
    {
        return (self::getInstance()->controls[$table][self::DELETE] !== '');
    }

    /**
     * @param string $table
     * @return array
     */
    public static function getDeleteField($table)
    {
        return self::getInstance()->controls[$table][self::DELETE];
    }

    /**
     * @return void
     */
    public function flushCaches()
    {
        $this->cache->flush();
    }
}
