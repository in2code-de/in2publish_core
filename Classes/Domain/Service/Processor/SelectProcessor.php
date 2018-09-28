<?php
declare(strict_types=1);
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

class SelectProcessor extends AbstractProcessor
{
    /**
     * @var bool
     */
    protected $canHoldRelations = true;

    const ALLOW_NON_ID_VALUES = 'allowNonIdValues';
    const FILE_FOLDER = 'fileFolder';
    const SPECIAL = 'special';

    /**
     * @var array
     */
    protected $forbidden = [
        'itemsProcFunc is not supported' => self::ITEMS_PROC_FUNC,
        'fileFolder is not supported' => self::FILE_FOLDER,
        'allowNonIdValues can not be resolved by in2publish' => self::ALLOW_NON_ID_VALUES,
        'MM_oppositeUsage is not supported' => self::MM_OPPOSITE_USAGE,
        'special is not supported' => self::SPECIAL,
    ];

    /**
     * @var array
     */
    protected $required = [
        'Can not select without another table' => self::FOREIGN_TABLE,
    ];

    /**
     * @var array
     */
    protected $allowed = [
        self::FOREIGN_TABLE_WHERE,
        self::MM,
        self::MM_HAS_UID_FIELD,
        self::MM_MATCH_FIELDS,
        self::MM_TABLE_WHERE,
        self::MM_OPPOSITE_FIELD,
        self::ROOT_LEVEL,
    ];
}
