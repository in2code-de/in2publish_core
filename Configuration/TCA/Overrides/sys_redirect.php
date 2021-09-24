<?php

declare(strict_types=1);

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Features\RedirectsSupport\DataProvider\ForeignSiteIdentifierItemProcFunc;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

(static function () {
    if (!ExtensionManagementUtility::isLoaded('redirects')) {
        return;
    }

    $configContainer = GeneralUtility::makeInstance(
        ConfigContainer::class
    );

    if ($configContainer->get('features.redirectsSupport.enable')) {
        $GLOBALS['TCA']['sys_redirect']['columns']['tx_in2publishcore_foreign_site_id'] = [
            'label' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:tca.sys_redirect.columns.tx_in2publishcore_foreign_site_id.label',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'eval' => 'trim',
                'items' => [
                    [
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:tca.sys_redirect.columns.tx_in2publishcore_foreign_site_id.config.items.null',
                        null,
                    ],
                    [
                        'LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:source_host_global_text',
                        '*',
                    ],
                ],
                'itemsProcFunc' => ForeignSiteIdentifierItemProcFunc::class . '->addData',
                'default' => null,
            ],
        ];

        $GLOBALS['TCA']['sys_redirect']['columns']['tx_in2publishcore_page_uid'] = [
            'label' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:tca.sys_redirect.columns.tx_in2publishcore_page_uid.label',
            'displayCond' => 'FIELD:tx_in2publishcore_foreign_site_id:REQ:false',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
                'suggestOptions' => [
                    'default' => [
                        'additionalSearchFields' => 'nav_title, url',
                    ],
                ],
                'default' => null,
            ],
        ];

        $GLOBALS['TCA']['sys_redirect']['palettes']['tx_in2publishcore_association'] = [
            'label' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:tca.sys_redirect.palettes.tx_in2publishcore_association.label',
            'showitem' => 'tx_in2publishcore_foreign_site_id, tx_in2publishcore_page_uid',
        ];

        ExtensionManagementUtility::addToAllTCAtypes(
            'sys_redirect',
            '--palette--;;tx_in2publishcore_association',
            '',
            'after:keep_query_parameters'
        );

        $GLOBALS['TCA']['sys_redirect']['columns']['deleted'] = [
            'config' => [
                'type' => 'check',
            ],
        ];
    }
})();
