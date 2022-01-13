<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Service\Processor;

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
 ***************************************************************/

use function array_key_exists;
use function array_merge;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren) Required through TYPO3's growing count of TCA types
 */
abstract class AbstractProcessor implements ProcessorInterface
{
    /**
     * Overwrite and set TRUE for types like "select" or "inline"
     */
    protected bool $canHoldRelations = false;

    /**
     * Do not overwrite this!
     * Stores the reasons for a column configuration not being suitable for resolving relations
     */
    protected array $lastReasons = [];

    /**
     * Overwrite this in your Processor if canHoldRelations is TRUE!
     * Fields that are forbidden for the processor type, indexed by the reason
     *    e.g. "itemsProcFunc is not supported by in2publish" => "itemsProcFunc"
     */
    protected array $forbidden = [];

    /**
     * Overwrite this in your Processor if canHoldRelations is TRUE!
     * Field names that must be contained in the configuration, indexed by their reason why
     *    e.g. "can not select without a foreign table" => "foreign_table"
     */
    protected array $required = [];

    /**
     * Overwrite this in your Processor if canHoldRelations is TRUE and these are needed!
     * Fields that are optional and are which taken into account for relations solving. Has no specific index
     *    e.g. "foreign_table_where"
     */
    protected array $allowed = [];

    /**
     * Overwrite this in your Processor if canHoldRelations is TRUE!
     *
     * @param array $config
     *
     * @return array
     */
    public function preProcess(array $config): array
    {
        $return = [];
        foreach ($this->getImportantFields() as $field) {
            if (!empty($config[$field])) {
                $return[$field] = $config[$field];
            }
        }
        return $return;
    }

    public function canPreProcess(array $config): bool
    {
        $this->lastReasons = [];
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
     * Return TRUE if the TCA type can hold relations. FALSE if for "stupid" or boolean types like "check" or "radio"
     *
     * @return bool
     */
    public function canHoldRelations(): bool
    {
        return $this->canHoldRelations;
    }

    public function getLastReasons(): array
    {
        return $this->lastReasons;
    }

    protected function getImportantFields(): array
    {
        return array_merge($this->required, $this->allowed);
    }
}
