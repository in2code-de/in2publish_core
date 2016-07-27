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

/**
 * Class FlexProcessor
 */
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

    /**
     * @var array
     */
    protected $forbidden = array(
        'ds_pointerField_searchParent is not supported' => self::DS_POINTER_FIELD_SEARCH_PARENT,
        'ds_pointerField_searchParent_subField is not supported' => self::DS_POINTER_FIELD_SEARCH_PARENT_SUB_FIELD,
    );

    /**
     * @var array
     */
    protected $required = array(
        'can not resolve flexform values without "ds"' => self::DS,
        'can not resolve flexform values without "ds_pointerField"' => self::DS_POINTER_FIELD,
    );

    /**
     * @var array
     */
    protected $allowed = array(
        self::SEARCH,
    );
}
