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
use In2code\In2publishCore\Domain\Service\Processor\CheckProcessor;
use In2code\In2publishCore\Domain\Service\Processor\FlexProcessor;
use In2code\In2publishCore\Domain\Service\Processor\GroupProcessor;
use In2code\In2publishCore\Domain\Service\Processor\ImageManipulationProcessor;
use In2code\In2publishCore\Domain\Service\Processor\InlineProcessor;
use In2code\In2publishCore\Domain\Service\Processor\InputProcessor;
use In2code\In2publishCore\Domain\Service\Processor\NoneProcessor;
use In2code\In2publishCore\Domain\Service\Processor\PassthroughProcessor;
use In2code\In2publishCore\Domain\Service\Processor\RadioProcessor;
use In2code\In2publishCore\Domain\Service\Processor\SelectProcessor;
use In2code\In2publishCore\Domain\Service\Processor\TextProcessor;
use In2code\In2publishCore\Domain\Service\Processor\UserProcessor;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TcaProcessingService
 */
class TcaProcessingService
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
    const DEFAULT_EXTRAS = 'defaultExtras';

    /**
     * @var TcaProcessingService
     */
    protected static $instance = null;

    /**
     * @var array
     */
    protected $defaultProcessor = [
        'check' => CheckProcessor::class,
        'flex' => FlexProcessor::class,
        'group' => GroupProcessor::class,
        'inline' => InlineProcessor::class,
        'input' => InputProcessor::class,
        'none' => NoneProcessor::class,
        'passthrough' => PassthroughProcessor::class,
        'radio' => RadioProcessor::class,
        'select' => SelectProcessor::class,
        'text' => TextProcessor::class,
        'user' => UserProcessor::class,
        'imageManipulation' => ImageManipulationProcessor::class,
    ];

    /**
     * @var AbstractProcessor[]
     */
    protected $processors = [];

    /**
     * Stores the part of the TCA that can be used for relation resolving
     *
     * @var array[]
     */
    protected $compatibleTca = [];

    /**
     * Stores the part of the TCA that can not be used for relation resolving including reasons
     *
     * @var array[]
     */
    protected $incompatibleTca = [];

    /**
     * Stores the controls for each table from TCA
     *
     * @var array[]
     */
    protected $controls = [];

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var VariableFrontend
     */
    protected $cache = null;

    /**
     * TcaProcessingService constructor.
     */
    protected function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('in2publish_core');

        $configuredProcessor = ConfigurationUtility::getConfiguration('tca.processor');
        if (is_array($configuredProcessor)) {
            foreach ($configuredProcessor as $type => $class) {
                if (!class_exists($class)) {
                    $this->logger->critical(
                        'TCA processor "' . $class . '" not found. Using default processor for type "' . $type . '"'
                    );
                    $class = $this->defaultProcessor[$type];
                }
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
     * @return TcaProcessingService
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static;
            static::$instance->preProcessTca();
        }
        return static::$instance;
    }

    /**
     * @return void
     */
    protected function preProcessTca()
    {
        if ($this->cache->has(static::CACHE_KEY_TCA_COMPATIBLE)
            && $this->cache->has(static::CACHE_KEY_TCA_INCOMPATIBLE)
            && $this->cache->has(static::CACHE_KEY_CONTROLS)
        ) {
            $this->compatibleTca = $this->cache->get(static::CACHE_KEY_TCA_COMPATIBLE);
            $this->incompatibleTca = $this->cache->get(static::CACHE_KEY_TCA_INCOMPATIBLE);
            $this->controls = $this->cache->get(static::CACHE_KEY_CONTROLS);
        } else {
            $this->preProcessTcaReal();
            $this->cache->set(static::CACHE_KEY_TCA_COMPATIBLE, $this->compatibleTca);
            $this->cache->set(static::CACHE_KEY_TCA_INCOMPATIBLE, $this->incompatibleTca);
            $this->cache->set(static::CACHE_KEY_CONTROLS, $this->controls);
        }
    }

    /**
     * @return void
     */
    protected function preProcessTcaReal()
    {
        foreach (static::getCompleteTca() as $table => $tableConfiguration) {
            if (!empty($tableConfiguration[static::CONTROL][static::DELETE])) {
                $this->controls[$table][static::DELETE] = $tableConfiguration[static::CONTROL][static::DELETE];
            } else {
                $this->controls[$table][static::DELETE] = '';
            }

            foreach ($tableConfiguration[static::COLUMNS] as $column => $columnConfiguration) {
                // if the column has no config section like sys_file_metadata[columns][height]
                if (!isset($columnConfiguration[static::CONFIG])) {
                    $this->incompatibleTca[$table][$column] = 'Columns without config section can not hold relations';
                    continue;
                }

                $config = $columnConfiguration[static::CONFIG];
                $config[static::DEFAULT_EXTRAS] = isset($columnConfiguration[static::DEFAULT_EXTRAS])
                    ? $columnConfiguration[static::DEFAULT_EXTRAS]
                    : null;
                $type = $config[static::TYPE];

                // If there's no processor for the type it is not a standard type of TYPO3
                // The incident will be logged and the field will be skipped
                if (empty($this->processors[$type])) {
                    if (!is_string($type)) {
                        $type = gettype($type);
                    }
                    $this->logger->critical(
                        'No Processor for "' . $type . '" found. Skipping configuration',
                        [
                            'table' => $table,
                            'column' => $column,
                        ]
                    );
                    continue;
                }

                // if the field potentially holds relations
                if ($this->processors[$type]->canHoldRelations()) {
                    // check if the field is configured for holding relations
                    if ($this->processors[$type]->canPreProcess($config)) {
                        // Set the preprocessed values
                        $this->compatibleTca[$table][$column] = $this->processors[$type]->preProcess($config);
                        $this->compatibleTca[$table][$column][static::TYPE] = $type;
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
                $this->compatibleTca[$table] = [];
            }
        }
    }

    /**
     * @return array
     */
    public static function getIncompatibleTca()
    {
        return static::getInstance()->incompatibleTca;
    }

    /**
     * @return array
     */
    public static function getCompatibleTca()
    {
        return static::getInstance()->compatibleTca;
    }

    /**
     * @return array
     */
    public static function getControls()
    {
        return static::getInstance()->controls;
    }

    /**
     * @return array
     */
    public static function getAllTables()
    {
        return array_keys(static::getCompleteTca());
    }

    /**
     * @param string $table
     * @return bool
     */
    public static function tableExists($table)
    {
        return array_key_exists($table, static::getCompleteTca());
    }

    /**
     * @return mixed
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getCompleteTca()
    {
        return $GLOBALS[static::TCA];
    }

    /**
     * @param string $tableName
     * @return array
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getCompleteTcaForTable($tableName)
    {
        return $GLOBALS[static::TCA][$tableName];
    }

    /**
     * @param string $table
     * @return array
     */
    public static function getColumnsFor($table)
    {
        return (array)static::getInstance()->compatibleTca[$table];
    }

    /**
     * @param string $table
     * @return array
     */
    public static function getControlsFor($table)
    {
        return static::getInstance()->controls[$table];
    }

    /**
     * @param string $table
     * @return bool
     */
    public static function hasDeleteField($table)
    {
        return (static::getInstance()->controls[$table][static::DELETE] !== '');
    }

    /**
     * @param string $table
     * @return string
     */
    public static function getDeleteField($table)
    {
        return static::getInstance()->controls[$table][static::DELETE];
    }

    /**
     * @return void
     */
    public function flushCaches()
    {
        $this->cache->flush();
    }
}
