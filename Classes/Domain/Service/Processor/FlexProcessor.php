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

class FlexProcessor extends AbstractProcessor
{
    /**
     * @var bool
     */
    protected $canHoldRelations = true;

    const DS = 'ds';
    const DS_POINTER_FIELD = 'ds_pointerField';
    const DS_POINTER_FIELD_SEARCH_PARENT = 'ds_pointerField_searchParent';
    const DS_POINTER_FIELD_SEARCH_PARENT_SUB_FIELD = 'ds_pointerField_searchParent_subField';
    const SEARCH = 'search';
    const MISSING_POINTER_FIELD = 'can not resolve flexform values without "ds_pointerField" or default value';
    const DEFAULT_VALUE = 'default';

    /**
     * @var array
     */
    protected $forbidden = [
        'ds_pointerField_searchParent is not supported' => self::DS_POINTER_FIELD_SEARCH_PARENT,
        'ds_pointerField_searchParent_subField is not supported' => self::DS_POINTER_FIELD_SEARCH_PARENT_SUB_FIELD,
    ];

    /**
     * @var array
     */
    protected $required = [
        'can not resolve flexform values without "ds"' => self::DS,
    ];

    /**
     * @var array
     */
    protected $allowed = [
        self::SEARCH,
        self::DS_POINTER_FIELD,
    ];

    /**
     * @param array $config
     *
     * @return bool
     */
    public function canPreProcess(array $config): bool
    {
        if (parent::canPreProcess($config) && !array_key_exists(static::DS_POINTER_FIELD, $config)) {
            if (empty($config[static::DS][static::DEFAULT_VALUE])) {
                $this->lastReasons[static::DS_POINTER_FIELD] = self::MISSING_POINTER_FIELD;
            }
        }

        return empty($this->lastReasons);
    }
}
