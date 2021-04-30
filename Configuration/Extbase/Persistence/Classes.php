<?php
declare(strict_types = 1);

return [
    \In2code\In2publishCore\Features\RedirectsSupport\Domain\Model\SysRedirect::class => [
        'tableName' => 'sys_redirect',
        'recordType' => \In2code\In2publishCore\Features\RedirectsSupport\Domain\Model\SysRedirect::class,
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
