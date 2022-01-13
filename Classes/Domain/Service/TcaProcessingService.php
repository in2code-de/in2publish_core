<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Service;

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

use In2code\In2publishCore\Config\ConfigContainer;
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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\SingletonInterface;

use function class_exists;
use function gettype;
use function is_array;
use function is_string;

class TcaProcessingService implements LoggerAwareInterface, SingletonInterface
{
    use LoggerAwareTrait;

    public const COLUMNS = 'columns';
    public const CONFIG = 'config';
    public const CONTROL = 'ctrl';
    public const TYPE = 'type';
    public const TCA = 'TCA';
    public const DELETE = 'delete';
    public const CACHE_KEY_TCA_COMPATIBLE = 'tca_compatible';
    public const CACHE_KEY_TCA_INCOMPATIBLE = 'tca_incompatible';
    public const CACHE_KEY_CONTROLS = 'controls';
    public const DEFAULT_EXTRAS = 'defaultExtras';
    public const SOFT_REF = 'softref';

    protected FrontendInterface $cache;

    protected static TcaProcessingService $instance;

    protected ConfigContainer $configContainer;

    protected array $defaultProcessor = [
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
    protected array $processors = [];

    /**
     * Stores the part of the TCA that can be used for relation resolving
     *
     * @var array<array|null>
     */
    protected array $compatibleTca = [];

    /**
     * Stores the part of the TCA that can not be used for relation resolving including reasons
     *
     * @var array[]
     */
    protected array $incompatibleTca = [];

    /**
     * Stores the controls for each table from TCA
     *
     * @var array[]
     */
    protected array $controls = [];

    public function __construct(FrontendInterface $cache, ConfigContainer $configContainer)
    {
        $this->cache = $cache;
        $this->configContainer = $configContainer;
    }

    public function initializeObject(): void
    {
        $configuredProcessor = $this->configContainer->get('tca.processor');
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

        $this->preProcessTca();
    }

    protected function preProcessTca(): void
    {
        if (
            $this->cache->has(static::CACHE_KEY_TCA_COMPATIBLE)
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

    protected function preProcessTcaReal(): void
    {
        foreach ($GLOBALS['TCA'] as $table => $tableConfiguration) {
            if (!empty($tableConfiguration[static::CONTROL][static::DELETE])) {
                $this->controls[$table][static::DELETE] = $tableConfiguration[static::CONTROL][static::DELETE];
            } else {
                $this->controls[$table][static::DELETE] = '';
            }

            if (isset($tableConfiguration[static::COLUMNS]) && is_array($tableConfiguration[static::COLUMNS])) {
                $this->preProcessTcaColumns($tableConfiguration[static::COLUMNS], $table);
            } else {
                $this->logger->warning('A table without columns section was given to pre process', ['table' => $table]);
            }

            // set an empty array as default, prevents NULL values
            if (empty($this->compatibleTca[$table])) {
                $this->compatibleTca[$table] = [];
            }
        }
    }

    protected function preProcessTcaColumns(array $columnsConfiguration, string $table): void
    {
        foreach ($columnsConfiguration as $column => $columnConfiguration) {
            // if the column has no config section like sys_file_metadata[columns][height]
            if (!isset($columnConfiguration[static::CONFIG])) {
                $this->incompatibleTca[$table][$column] = 'Columns without config section can not hold relations';
                continue;
            }

            $config = $columnConfiguration[static::CONFIG];
            $config[static::DEFAULT_EXTRAS] = $columnConfiguration[static::DEFAULT_EXTRAS] ?? null;
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
                    // Set the reasons why it can not be pre-processed. Useful for Extension authors
                    foreach ($this->processors[$type]->getLastReasons() as $key => $reason) {
                        $this->incompatibleTca[$table][$column]['type'] = $type;
                        $this->incompatibleTca[$table][$column]['reasons'][$key] = $reason;
                    }
                }
            } else {
                $this->incompatibleTca[$table][$column] = 'The type "' . $type . '" can not hold relations';
            }
        }
    }

    public function getIncompatibleTcaParts(): array
    {
        return $this->incompatibleTca;
    }

    public function getCompatibleTcaParts(): array
    {
        return $this->compatibleTca;
    }

    public function getCompatibleTcaColumns(string $table): array
    {
        return $this->compatibleTca[$table] ?? [];
    }

    public function flushCaches(): void
    {
        $this->cache->flush();
    }
}
