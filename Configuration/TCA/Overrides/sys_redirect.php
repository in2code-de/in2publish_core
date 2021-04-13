<?php

declare(strict_types=1);

$GLOBALS['TCA']['sys_redirect']['columns']['page_uid'] = [
    'label' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:tca.sys_redirect.columns.page_uid.label',
    'config' => [
        'type' => 'select',
        'renderType' => 'selectTree',
        'foreign_table' => 'pages',
        'foreign_table_where' => 'ORDER BY pages.sorting',
        'treeConfig' => [
            'parentField' => 'pid',
        ],
        'maxitems' => 1,
        'default' => null,
    ]
];
$GLOBALS['TCA']['sys_redirect']['columns']['site_id'] = [
    'label' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:tca.sys_redirect.columns.site_id.label',
    'config' => [
        'type' => 'select',
        'renderType' => 'selectSingle',
        'eval' => 'trim',
        'items' => [
            [
                'LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:source_host_global_text',
                '*',
            ],
        ],
        'itemsProcFunc' => \In2code\In2publishCore\Features\RedirectsSupport\DataProvider\ForeignSiteIdentifierItemProvFunc::class . '->addData',
        'default' => '*',
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'sys_redirect',
    'page_uid',
    '',
    'after:keep_query_parameters'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_redirect', 'site_id', '', 'after:page_uid');
