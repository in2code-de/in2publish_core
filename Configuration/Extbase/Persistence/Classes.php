<?php

declare(strict_types=1);

use In2code\In2publishCore\Domain\Model\BackendUser;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Model\SysRedirect;

return [
    SysRedirect::class => [
        'tableName' => 'sys_redirect',
        'recordType' => SysRedirect::class,
        'properties' => [
            'sourceHost' => [
                'fieldName' => 'source_host',
            ],
            'sourcePath' => [
                'fieldName' => 'source_path',
            ],
            'target' => [
                'fieldName' => 'target',
            ],
            'pageUid' => [
                'fieldName' => 'tx_in2publishcore_page_uid',
            ],
            'siteId' => [
                'fieldName' => 'tx_in2publishcore_foreign_site_id',
            ],
        ],
    ],
];
