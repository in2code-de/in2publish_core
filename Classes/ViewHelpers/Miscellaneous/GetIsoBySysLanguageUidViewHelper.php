<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Miscellaneous;

/*
 * Copyright notice
 *
 * (c) 2020 in2code.de and the following authors:
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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class GetIsoBySysLanguageUidViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected const LANGUAGE = 'language';
    protected const TBL_SYS_LANGUAGE = 'sys_language';

    /** @var ConnectionPool */
    protected $connectionPool;

    protected $rtc = [];

    public function __construct(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(self::LANGUAGE, 'int', 'UID of a sys_language record', true);
    }

    public function render(): string
    {
        $language = $this->arguments[self::LANGUAGE];

        if (!isset($this->rtc[$language])) {
            $query = $this->connectionPool->getQueryBuilderForTable(self::TBL_SYS_LANGUAGE);
            $query->select('flag')
                  ->from(self::TBL_SYS_LANGUAGE)
                  ->where($query->expr()->eq('uid', $query->createNamedParameter($language)));
            $statement = $query->execute();
            $this->rtc[$language] = (string)$statement->fetchOne();
        }

        return $this->rtc[$language];
    }
}
