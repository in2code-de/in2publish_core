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
 */

/**
 * Interface ProcessorInterface
 */
interface ProcessorInterface
{
    public const FOREIGN_TABLE = 'foreign_table';
    public const FOREIGN_TABLE_WHERE = 'foreign_table_where';
    public const MM = 'MM';
    public const MM_HAS_UID_FIELD = 'MM_hasUidField';
    public const MM_MATCH_FIELDS = 'MM_match_fields';
    public const MM_TABLE_WHERE = 'MM_table_where';
    public const MM_OPPOSITE_USAGE = 'MM_oppositeUsage';
    public const MM_OPPOSITE_FIELD = 'MM_opposite_field';
    public const ROOT_LEVEL = 'rootLevel';
    public const ITEMS_PROC_FUNC = 'itemsProcFunc';

    /**
     * The constructor must be public and must not require any arguments
     */
    public function __construct();

    /**
     * Returns TRUE if the type the processor is made for is suitable for relations
     *
     * @return bool
     */
    public function canHoldRelations();

    /**
     * Returns TRUE if the specific configuration can hold relations
     *
     * @param array $config
     *
     * @return bool
     */
    public function canPreProcess(array $config);

    /**
     * PreProcess the configuration. Returns an array with only necessary information in an standardized format
     *
     * @param array $config
     *
     * @return array
     */
    public function preProcess(array $config);

    /**
     * Returns an array of $field => $reasons that explains why canPreProcess returned false.
     *
     * @return array
     */
    public function getLastReasons();
}
