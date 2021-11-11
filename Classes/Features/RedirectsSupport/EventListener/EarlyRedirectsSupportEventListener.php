<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\EventListener;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class EarlyRedirectsSupportEventListener
{
    protected const STATEMENT = <<<SQL
CREATE TABLE sys_redirect
(
    tx_in2publishcore_page_uid        int(11) unsigned DEFAULT NULL,
    tx_in2publishcore_foreign_site_id varchar(255)     DEFAULT NULL
) ENGINE = InnoDB;
SQL;

    public function onAlterTableDefinitionStatementsEvent(AlterTableDefinitionStatementsEvent $event): void
    {
        if (ExtensionManagementUtility::isLoaded('redirects')) {
            $event->addSqlData(self::STATEMENT);
        }
    }
}
