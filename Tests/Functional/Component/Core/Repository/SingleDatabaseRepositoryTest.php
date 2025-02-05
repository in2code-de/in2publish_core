<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Component\Core\Repository;

use In2code\In2publishCore\Component\Core\Repository\SingleDatabaseRepository;
use In2code\In2publishCore\Component\Core\Service\Database\DatabaseSchemaService;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_column;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Repository\SingleDatabaseRepository
 */
class SingleDatabaseRepositoryTest extends FunctionalTestCase
{
    // Read-only tests do not require database reset
    protected bool $initializeDatabase = false;

    public function testFindByPropertyReturnsRowsSortedByTcaCtrl(): void
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName('Default');
        $singleDataRepository = new SingleDatabaseRepository($connection);

        $rows = $singleDataRepository->findByProperty('pages', 'uid', [1, 3, 6]);
        $sortings = array_column($rows, 'sorting');
        self::assertSame([128, 512, 544], $sortings);
    }

    public function testFindByPropertyWithJoinReturnsJoinedRows(): void
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName('Default');
        $singleDataRepository = new SingleDatabaseRepository($connection);
        $databaseSchemaService = GeneralUtility::makeInstance(DatabaseSchemaService::class);
        $singleDataRepository->injectDatabaseSchemaService($databaseSchemaService);

        $rows = $singleDataRepository->findByPropertyWithJoin(
            'sys_category_record_mm',
            'sys_category',
            'uid_foreign',
            [77, 78],
        );
        self::assertSame([
            '6ed754e2fa2e937739af04a069a6f6d9e96db685' => [
                'mmtbl' => [
                    'uid_local' => 1,
                    'uid_foreign' => 77,
                    'sorting' => 0,
                    'sorting_foreign' => 1,
                    'tablenames' => "pages",
                    'fieldname' => "categories",
                ],
                'table' => [
                    'uid' => 1,
                    'pid' => 1,
                    'tstamp' => 1730129770,
                    'crdate' => 1730129770,
                    'deleted' => 0,
                    'hidden' => 0,
                    'starttime' => 0,
                    'endtime' => 0,
                    'sorting' => 256,
                    'description' => "",
                    'sys_language_uid' => 0,
                    'l10n_parent' => 0,
                    'l10n_state' => '"NULL"',
                    't3_origuid' => 0,
                    'l10n_diffsource' => "",
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    't3ver_stage' => 0,
                    'title' => '"Category 1"',
                    'items' => 0,
                    'parent' => 0,
                    'fe_group' => "0",
                    'images' => 0,
                    'single_pid' => 0,
                    'shortcut' => 0,
                    'import_id' => "",
                    'import_source' => "",
                    'seo_title' => "",
                    'seo_description' => '',
                    'seo_headline' => "",
                    'seo_text' => '',
                    'slug' => '"category-1"',
                ],
            ],
            'a991a8cbb167889a955eecbdba10bb99a9d8ec4d' => [
                'mmtbl' => [
                    'uid_local' => 2,
                    'uid_foreign' => 78,
                    'sorting' => 0,
                    'sorting_foreign' => 1,
                    'tablenames' => "pages",
                    'fieldname' => "categories",
                ],
                'table' => [
                    'uid' => 2,
                    'pid' => 1,
                    'tstamp' => 1730129781,
                    'crdate' => 1730129781,
                    'deleted' => 0,
                    'hidden' => 0,
                    'starttime' => 0,
                    'endtime' => 0,
                    'sorting' => 512,
                    'description' => "",
                    'sys_language_uid' => 0,
                    'l10n_parent' => 0,
                    'l10n_state' => '"NULL"',
                    't3_origuid' => 0,
                    'l10n_diffsource' => "",
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    't3ver_stage' => 0,
                    'title' => '"Category 2"',
                    'items' => 0,
                    'parent' => 0,
                    'fe_group' => "0",
                    'images' => 0,
                    'single_pid' => 0,
                    'shortcut' => 0,
                    'import_id' => "",
                    'import_source' => "",
                    'seo_title' => "",
                    'seo_description' => '',
                    'seo_headline' => "",
                    'seo_text' => '',
                    'slug' => '"category-2"',
                ],
            ],
        ], $rows);
    }

    public function testFindByWhere(): void
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName('Default');
        $singleDataRepository = new SingleDatabaseRepository($connection);

        $rows = $singleDataRepository->findByWhere('pages', 'title LIKE "%Ignored Field%"');
        self::assertSame([
            59 => [
                'uid' => 59,
                'pid' => 3,
                'tstamp' => 1698046782,
                'crdate' => 1697723082,
                'deleted' => 0,
                'hidden' => 0,
                'starttime' => 0,
                'endtime' => 0,
                'fe_group' => "",
                'sorting' => 6912,
                'rowDescription' => "Old row description",
                'editlock' => 0,
                'sys_language_uid' => 0,
                'l10n_parent' => 0,
                'l10n_source' => 0,
                'l10n_state' => null,
                't3_origuid' => 0,
                'l10n_diffsource' => '{"hidden":""}',
                't3ver_oid' => 0,
                't3ver_wsid' => 0,
                't3ver_state' => 0,
                't3ver_stage' => 0,
                'perms_userid' => 1,
                'perms_groupid' => 6,
                'perms_user' => 31,
                'perms_group' => 31,
                'perms_everybody' => 1,
                'title' => "14 - Ignored Field rowDescription",
                'slug' => "/extin2publish-core/14-ignored-field-rowdescription",
                'doktype' => 1,
                'TSconfig' => "",
                'is_siteroot' => 0,
                'php_tree_stop' => 0,
                'url' => "",
                'shortcut' => 0,
                'shortcut_mode' => 0,
                'subtitle' => "The field rowDescription is ignored by the publisher (Tab: Notes Field: Description)",
                'layout' => 0,
                'target' => "",
                'media' => 0,
                'lastUpdated' => 0,
                'keywords' => "",
                'cache_timeout' => 0,
                'cache_tags' => "",
                'newUntil' => 0,
                'description' => "",
                'no_search' => 0,
                'SYS_LASTCHANGED' => 0,
                'abstract' => "",
                'module' => "",
                'extendToSubpages' => 0,
                'author' => "",
                'author_email' => "",
                'nav_title' => "",
                'nav_hide' => 0,
                'content_from_pid' => 0,
                'mount_pid' => 0,
                'mount_pid_ol' => 0,
                'l18n_cfg' => 0,
                'backend_layout' => "",
                'backend_layout_next_level' => "",
                'tsconfig_includes' => "",
                'categories' => 0,
                'tx_impexp_origuid' => 0,
                'seo_title' => "",
                'no_index' => 0,
                'no_follow' => 0,
                'og_title' => "",
                'og_description' => "",
                'og_image' => 0,
                'twitter_title' => "",
                'twitter_description' => "",
                'twitter_image' => 0,
                'twitter_card' => "summary",
                'canonical_link' => "",
                'sitemap_priority' => "0.5",
                'sitemap_changefreq' => "",
                'tx_styleguide_containsdemo' => "",
            ],
        ], $rows);
    }
}
