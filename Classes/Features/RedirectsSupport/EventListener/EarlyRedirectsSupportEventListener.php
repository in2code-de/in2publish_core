<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\EventListener;

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
