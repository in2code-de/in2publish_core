<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Configuration;

/*
 * Copyright notice
 *
 * (c) 2023 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
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

use function array_keys;

abstract class AbstractPageTypeService implements PageTypeService
{
    protected array $rtc = [];

    /**
     * Finds all tables which are allowed on either self::TYPE_ROOT or self::TYPE_PAGE according to the table's TCA
     * 'rootLevel' setting.
     *
     * @return array<string>
     */
    protected function getAllAllowedTableNames(string $type): array
    {
        if (!isset($this->rtc['_types'])) {
            $allowed = [
                self::TYPE_ROOT => [],
                self::TYPE_PAGE => [],
            ];
            foreach (array_keys($GLOBALS['TCA']) as $table) {
                switch ('pages' === $table ? -1 : (int)($GLOBALS['TCA'][$table]['ctrl']['rootLevel'] ?? 0)) {
                    case -1:
                        $allowed[self::TYPE_ROOT][] = $table;
                        $allowed[self::TYPE_PAGE][] = $table;
                        break;
                    case 0:
                        $allowed[self::TYPE_PAGE][] = $table;
                        break;
                    case 1:
                        $allowed[self::TYPE_ROOT][] = $table;
                        break;
                }
            }
            $this->rtc['_types'] = $allowed;
        }
        return $this->rtc['_types'][$type];
    }
}
