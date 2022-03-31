<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor;

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

use Closure;
use Exception;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\ProcessingResult;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessor;

use function array_key_exists;
use function array_keys;
use function array_merge;

abstract class AbstractProcessor implements TcaPreProcessor
{
    protected const ADDITIONAL_ORDER_BY_PATTERN = '/(?P<where>.*)ORDER[\s\n]+BY[\s\n]+(?P<col>\w+(\.\w+)?)(?P<dir>\s(DESC|ASC))?/is';

    /**
     * Overwrite this in your processor!
     * Return the TCA type your processor handles.
     * @var string
     * @api
     */
    protected $type;

    /**
     * Overwrite this in your processor!
     * Fields that are forbidden for the processor type, indexed by the reason
     *    e.g. "itemsProcFunc is not supported by in2publish" => "itemsProcFunc"
     *
     * @var array
     * @api
     */
    protected $forbidden = [];

    /**
     * Overwrite this in your Processor!
     * Field names that must be contained in the configuration, indexed by their reason why
     *    e.g. "can not select without a foreign table" => "foreign_table"
     *
     * @var array
     * @api
     */
    protected $required = [];

    /**
     * Overwrite this in your processor if these are needed!
     * Fields that are optional and are which taken into account for relations solving. Has no specific index
     *    e.g. "foreign_table_where"
     *
     * @var array
     * @api
     */
    protected $allowed = [];

    public function getType(): string
    {
        if (!isset($this->type)) {
            throw new Exception('You must set $this->type in your processor');
        }
        return $this->type;
    }

    public function getTable(): string
    {
        return '*';
    }

    public function getColumn(): string
    {
        return '*';
    }

    public function process(string $table, string $column, array $tca): ProcessingResult
    {
        $reasons = [];
        foreach ($this->forbidden as $key => $reason) {
            if (array_key_exists($key, $tca)) {
                $reasons[] = $reason;
            }
        }
        foreach ($this->required as $key => $reason) {
            if (!array_key_exists($key, $tca)) {
                $reasons[] = $reason;
            }
        }
        foreach ($this->additionalPreProcess($table, $column, $tca) as $reason) {
            $reasons[] = $reason;
        }
        if (!empty($reasons)) {
            return new ProcessingResult(ProcessingResult::INCOMPATIBLE, $reasons);
        }
        $processedTca = [];
        foreach ($this->getImportantFields() as $importantField) {
            if (array_key_exists($importantField, $tca)) {
                $processedTca[$importantField] = $tca[$importantField];
            }
        }
        $resolver = $this->buildResolver($table, $column, $processedTca);
        return new ProcessingResult(
            ProcessingResult::COMPATIBLE,
            ['tca' => $processedTca, 'resolver' => $resolver]
        );
    }

    protected function getImportantFields(): array
    {
        return array_merge(['type'], array_keys($this->required), $this->allowed);
    }

    protected function additionalPreProcess(string $table, string $column, array $tca): array
    {
        return [];
    }

    abstract protected function buildResolver(string $table, string $column, array $processedTca): Closure;
}
