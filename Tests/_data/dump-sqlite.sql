PRAGMA synchronous = OFF;
PRAGMA journal_mode = MEMORY;
BEGIN TRANSACTION;
CREATE TABLE `backend_layout` (
    "uid"           integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    "pid"           integer      NOT NULL DEFAULT 0,
    "tstamp"        integer      NOT NULL DEFAULT 0,
    "crdate"        integer      NOT NULL DEFAULT 0,
    "cruser_id"     integer      NOT NULL DEFAULT 0,
    "deleted"       integer      NOT NULL DEFAULT 0,
    "hidden"        integer      NOT NULL DEFAULT 0,
    "sorting"       integer      NOT NULL DEFAULT 0,
    "description"   text COLLATE BINARY,
    "t3_origuid"    integer      NOT NULL DEFAULT 0,
    "t3ver_oid"     integer      NOT NULL DEFAULT 0,
    "t3ver_id"      integer      NOT NULL DEFAULT 0,
    "t3ver_label"   varchar(255) NOT NULL DEFAULT '',
    "t3ver_wsid"    integer      NOT NULL DEFAULT 0,
    "t3ver_state"   integer      NOT NULL DEFAULT 0,
    "t3ver_stage"   integer      NOT NULL DEFAULT 0,
    "t3ver_count"   integer      NOT NULL DEFAULT 0,
    "t3ver_tstamp"  integer      NOT NULL DEFAULT 0,
    "t3ver_move_id" integer      NOT NULL DEFAULT 0,
    "title"         varchar(255) NOT NULL DEFAULT '',
    "config"        text         NOT NULL,
    "icon"          text COLLATE BINARY
);
CREATE TABLE `be_groups` (
    `uid`                integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`                integer      NOT NULL DEFAULT '0',
    `tstamp`             integer      NOT NULL DEFAULT '0',
    `crdate`             integer      NOT NULL DEFAULT '0',
    `cruser_id`          integer      NOT NULL DEFAULT '0',
    `deleted`            integer      NOT NULL DEFAULT '0',
    `hidden`             integer      NOT NULL DEFAULT '0',
    `description`        text COLLATE BINARY,
    `title`              varchar(50)  NOT NULL DEFAULT '',
    `non_exclude_fields` text COLLATE BINARY,
    `explicit_allowdeny` text COLLATE BINARY,
    `allowed_languages`  varchar(255) NOT NULL DEFAULT '',
    `custom_options`     text COLLATE BINARY,
    `db_mountpoints`     text COLLATE BINARY,
    `pagetypes_select`   varchar(255) NOT NULL DEFAULT '',
    `tables_select`      text COLLATE BINARY,
    `tables_modify`      text COLLATE BINARY,
    `groupMods`          text COLLATE BINARY,
    `file_mountpoints`   text COLLATE BINARY,
    `file_permissions`   text COLLATE BINARY,
    `lockToDomain`       varchar(50)  NOT NULL DEFAULT '',
    `TSconfig`           text COLLATE BINARY,
    `subgroup`           text COLLATE BINARY,
    `workspace_perms`    integer      NOT NULL DEFAULT '1',
    `category_perms`     text COLLATE BINARY
);
CREATE TABLE `be_sessions` (
    `ses_id`         varchar(32) NOT NULL DEFAULT '',
    `ses_iplock`     varchar(39) NOT NULL DEFAULT '',
    `ses_userid`     integer     NOT NULL DEFAULT '0',
    `ses_tstamp`     integer     NOT NULL DEFAULT '0',
    `ses_data`       longblob,
    `ses_backuserid` integer     NOT NULL DEFAULT '0',
    PRIMARY KEY (`ses_id`)
);
CREATE TABLE `be_users` (
    `uid`                    integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`                    integer      NOT NULL DEFAULT '0',
    `tstamp`                 integer      NOT NULL DEFAULT '0',
    `crdate`                 integer      NOT NULL DEFAULT '0',
    `cruser_id`              integer      NOT NULL DEFAULT '0',
    `deleted`                integer      NOT NULL DEFAULT '0',
    `disable`                integer      NOT NULL DEFAULT '0',
    `starttime`              integer      NOT NULL DEFAULT '0',
    `endtime`                integer      NOT NULL DEFAULT '0',
    `description`            text COLLATE BINARY,
    `username`               varchar(50)  NOT NULL DEFAULT '',
    `avatar`                 integer      NOT NULL DEFAULT '0',
    `password`               varchar(100) NOT NULL DEFAULT '',
    `admin`                  integer      NOT NULL DEFAULT '0',
    `usergroup`              varchar(255) NOT NULL DEFAULT '',
    `lang`                   varchar(6)   NOT NULL DEFAULT '',
    `email`                  varchar(255) NOT NULL DEFAULT '',
    `db_mountpoints`         text COLLATE BINARY,
    `options`                integer      NOT NULL DEFAULT '0',
    `realName`               varchar(80)  NOT NULL DEFAULT '',
    `userMods`               text COLLATE BINARY,
    `allowed_languages`      varchar(255) NOT NULL DEFAULT '',
    `uc`                     blob,
    `file_mountpoints`       text COLLATE BINARY,
    `file_permissions`       text COLLATE BINARY,
    `workspace_perms`        integer      NOT NULL DEFAULT '1',
    `lockToDomain`           varchar(50)  NOT NULL DEFAULT '',
    `disableIPlock`          integer      NOT NULL DEFAULT '0',
    `TSconfig`               text COLLATE BINARY,
    `lastlogin`              integer      NOT NULL DEFAULT '0',
    `createdByAction`        integer      NOT NULL DEFAULT '0',
    `usergroup_cached_list`  text COLLATE BINARY,
    `workspace_id`           integer      NOT NULL DEFAULT '0',
    `category_perms`         text COLLATE BINARY,
    `tx_news_categorymounts` varchar(255) NOT NULL DEFAULT ''
);
CREATE TABLE `cache_hash`
(
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `expires`    integer     NOT NULL DEFAULT '0',
    `content`    longblob
);
CREATE TABLE `cache_hash_tags`
(
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `cache_in2publish_core`
(
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `expires`    integer     NOT NULL DEFAULT '0',
    `content`    longblob
);
CREATE TABLE `cache_in2publish_core_tags`
(
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `cache_treelist` (
    `md5hash`  varchar(32) NOT NULL DEFAULT '',
    `pid`      integer     NOT NULL DEFAULT '0',
    `treelist` mediumtext COLLATE BINARY,
    `tstamp`   integer     NOT NULL DEFAULT '0',
    `expires`  integer     NOT NULL DEFAULT '0',
    PRIMARY KEY (`md5hash`)
);
CREATE TABLE `cf_cache_hash` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `expires`    integer      NOT NULL DEFAULT '0',
    `content`    longblob
);
CREATE TABLE `cf_cache_hash_tags` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `cf_cache_imagesizes` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `expires`    integer      NOT NULL DEFAULT '0',
    `content`    longblob
);
CREATE TABLE `cf_cache_imagesizes_tags` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `cf_cache_news_category` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `expires`    integer      NOT NULL DEFAULT '0',
    `content`    longblob
);
CREATE TABLE `cf_cache_news_category_tags` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `cf_cache_pages` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `expires`    integer      NOT NULL DEFAULT '0',
    `content`    longblob
);
CREATE TABLE `cf_cache_pages_tags` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `cf_cache_pagesection` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `expires`    integer      NOT NULL DEFAULT '0',
    `content`    longblob
);
CREATE TABLE `cf_cache_pagesection_tags` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `cf_cache_rootline` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `expires`    integer      NOT NULL DEFAULT '0',
    `content`    longblob
);
CREATE TABLE `cf_cache_rootline_tags` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `cf_extbase_datamapfactory_datamap` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `expires`    integer      NOT NULL DEFAULT '0',
    `content`    longblob
);
CREATE TABLE `cf_extbase_datamapfactory_datamap_tags` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `cf_in2publish` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `expires`    integer      NOT NULL DEFAULT '0',
    `content`    longblob
);
CREATE TABLE `cf_in2publish_core` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `expires`    integer      NOT NULL DEFAULT '0',
    `content`    longblob
);
CREATE TABLE `cf_in2publish_core_tags` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `cf_in2publish_tags` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `cf_tx_solr` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `expires`    integer      NOT NULL DEFAULT '0',
    `content`    longblob
);
CREATE TABLE `cf_tx_solr_configuration` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `expires`    integer      NOT NULL DEFAULT '0',
    `content`    longblob
);
CREATE TABLE `cf_tx_solr_configuration_tags` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `cf_tx_solr_tags` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `fe_groups` (
    `uid`             integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`             integer      NOT NULL DEFAULT '0',
    `tstamp`          integer      NOT NULL DEFAULT '0',
    `crdate`          integer      NOT NULL DEFAULT '0',
    `cruser_id`       integer      NOT NULL DEFAULT '0',
    `deleted`         integer      NOT NULL DEFAULT '0',
    `hidden`          integer      NOT NULL DEFAULT '0',
    `description`     text COLLATE BINARY,
    `tx_extbase_type` varchar(255) NOT NULL DEFAULT '0',
    `title`           varchar(50)  NOT NULL DEFAULT '',
    `lockToDomain`    varchar(50)  NOT NULL DEFAULT '',
    `subgroup`        tinytext COLLATE BINARY,
    `TSconfig`        text COLLATE BINARY
);
CREATE TABLE `fe_sessions` (
    `ses_id`        varchar(32) NOT NULL DEFAULT '',
    `ses_iplock`    varchar(39) NOT NULL DEFAULT '',
    `ses_userid`    integer     NOT NULL DEFAULT '0',
    `ses_tstamp`    integer     NOT NULL DEFAULT '0',
    `ses_data`      blob,
    `ses_permanent` integer     NOT NULL DEFAULT '0',
    `ses_anonymous` integer     NOT NULL DEFAULT '0',
    PRIMARY KEY (`ses_id`)
);
CREATE TABLE `fe_users` (
    `uid`             integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`             integer      NOT NULL DEFAULT '0',
    `tstamp`          integer      NOT NULL DEFAULT '0',
    `crdate`          integer      NOT NULL DEFAULT '0',
    `cruser_id`       integer      NOT NULL DEFAULT '0',
    `deleted`         integer      NOT NULL DEFAULT '0',
    `disable`         integer      NOT NULL DEFAULT '0',
    `starttime`       integer      NOT NULL DEFAULT '0',
    `endtime`         integer      NOT NULL DEFAULT '0',
    `description`     text COLLATE BINARY,
    `tx_extbase_type` varchar(255) NOT NULL DEFAULT '0',
    `username`        varchar(255) NOT NULL DEFAULT '',
    `password`        varchar(100) NOT NULL DEFAULT '',
    `usergroup`       tinytext COLLATE BINARY,
    `name`            varchar(160) NOT NULL DEFAULT '',
    `first_name`      varchar(50)  NOT NULL DEFAULT '',
    `middle_name`     varchar(50)  NOT NULL DEFAULT '',
    `last_name`       varchar(50)  NOT NULL DEFAULT '',
    `address`         varchar(255) NOT NULL DEFAULT '',
    `telephone`       varchar(30)  NOT NULL DEFAULT '',
    `fax`             varchar(30)  NOT NULL DEFAULT '',
    `email`           varchar(255) NOT NULL DEFAULT '',
    `lockToDomain`    varchar(50)  NOT NULL DEFAULT '',
    `uc`              blob,
    `title`           varchar(40)  NOT NULL DEFAULT '',
    `zip`             varchar(10)  NOT NULL DEFAULT '',
    `city`            varchar(50)  NOT NULL DEFAULT '',
    `country`         varchar(40)  NOT NULL DEFAULT '',
    `www`             varchar(80)  NOT NULL DEFAULT '',
    `company`         varchar(80)  NOT NULL DEFAULT '',
    `image`           tinytext COLLATE BINARY,
    `TSconfig`        text COLLATE BINARY,
    `lastlogin`       integer      NOT NULL DEFAULT '0',
    `is_online`       integer      NOT NULL DEFAULT '0',
    `downloads`       integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `pages` (
    `uid`                       integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`                       integer      NOT NULL DEFAULT '0',
    `tstamp`                    integer      NOT NULL DEFAULT '0',
    `crdate`                    integer      NOT NULL DEFAULT '0',
    `cruser_id`                 integer      NOT NULL DEFAULT '0',
    `deleted`                   integer      NOT NULL DEFAULT '0',
    `hidden`                    integer      NOT NULL DEFAULT '0',
    `starttime`                 integer      NOT NULL DEFAULT '0',
    `endtime`                   integer      NOT NULL DEFAULT '0',
    `fe_group`                  varchar(255) NOT NULL DEFAULT '0',
    `sorting`                   integer      NOT NULL DEFAULT '0',
    `rowDescription`            text COLLATE BINARY,
    `editlock`                  integer      NOT NULL DEFAULT '0',
    `sys_language_uid`          integer      NOT NULL DEFAULT '0',
    `l10n_parent`               integer      NOT NULL DEFAULT '0',
    `l10n_source`               integer      NOT NULL DEFAULT '0',
    `l10n_state`                text COLLATE BINARY,
    `t3_origuid`                integer      NOT NULL DEFAULT '0',
    `l10n_diffsource`           blob,
    `t3ver_oid`                 integer      NOT NULL DEFAULT '0',
    `t3ver_id`                  integer      NOT NULL DEFAULT '0',
    `t3ver_label`               varchar(255) NOT NULL DEFAULT '',
    `t3ver_wsid`                integer      NOT NULL DEFAULT '0',
    `t3ver_state`               integer      NOT NULL DEFAULT '0',
    `t3ver_stage`               integer      NOT NULL DEFAULT '0',
    `t3ver_count`               integer      NOT NULL DEFAULT '0',
    `t3ver_tstamp`              integer      NOT NULL DEFAULT '0',
    `t3ver_move_id`             integer      NOT NULL DEFAULT '0',
    `perms_userid`              integer      NOT NULL DEFAULT '0',
    `perms_groupid`             integer      NOT NULL DEFAULT '0',
    `perms_user`                integer      NOT NULL DEFAULT '0',
    `perms_group`               integer      NOT NULL DEFAULT '0',
    `perms_everybody`           integer      NOT NULL DEFAULT '0',
    `title`                     varchar(255) NOT NULL DEFAULT '',
    `slug`                      varchar(2048)         DEFAULT NULL,
    `doktype`                   integer      NOT NULL DEFAULT '0',
    `TSconfig`                  text COLLATE BINARY,
    `is_siteroot`               integer      NOT NULL DEFAULT '0',
    `php_tree_stop`             integer      NOT NULL DEFAULT '0',
    `url`                       varchar(255) NOT NULL DEFAULT '',
    `shortcut`                  integer      NOT NULL DEFAULT '0',
    `shortcut_mode`             integer      NOT NULL DEFAULT '0',
    `subtitle`                  varchar(255) NOT NULL DEFAULT '',
    `layout`                    integer      NOT NULL DEFAULT '0',
    `target`                    varchar(80)  NOT NULL DEFAULT '',
    `media`                     integer      NOT NULL DEFAULT '0',
    `lastUpdated`               integer      NOT NULL DEFAULT '0',
    `keywords`                  text COLLATE BINARY,
    `cache_timeout`             integer      NOT NULL DEFAULT '0',
    `cache_tags`                varchar(255) NOT NULL DEFAULT '',
    `newUntil`                  integer      NOT NULL DEFAULT '0',
    `description`               text COLLATE BINARY,
    `no_search`                 integer      NOT NULL DEFAULT '0',
    `SYS_LASTCHANGED`           integer      NOT NULL DEFAULT '0',
    `abstract`                  text COLLATE BINARY,
    `module`                    varchar(255) NOT NULL DEFAULT '',
    `extendToSubpages`          integer      NOT NULL DEFAULT '0',
    `author`                    varchar(255) NOT NULL DEFAULT '',
    `author_email`              varchar(255) NOT NULL DEFAULT '',
    `nav_title`                 varchar(255) NOT NULL DEFAULT '',
    `nav_hide`                  integer      NOT NULL DEFAULT '0',
    `content_from_pid`          integer      NOT NULL DEFAULT '0',
    `mount_pid`                 integer      NOT NULL DEFAULT '0',
    `mount_pid_ol`              integer      NOT NULL DEFAULT '0',
    `alias`                     varchar(32)  NOT NULL DEFAULT '',
    `l18n_cfg`                  integer      NOT NULL DEFAULT '0',
    `fe_login_mode`             integer      NOT NULL DEFAULT '0',
    `backend_layout`            varchar(64)  NOT NULL DEFAULT '',
    `backend_layout_next_level` varchar(64)  NOT NULL DEFAULT '',
    `tsconfig_includes`         text COLLATE BINARY,
    `legacy_overlay_uid`        integer      NOT NULL DEFAULT '0',
    `categories`                integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `sys_be_shortcuts` (
    `uid`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `userid`      integer      NOT NULL DEFAULT '0',
    `module_name` varchar(255) NOT NULL DEFAULT '',
    `url`         text COLLATE BINARY,
    `description` varchar(255) NOT NULL DEFAULT '',
    `sorting`     integer      NOT NULL DEFAULT '0',
    `sc_group`    integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `sys_category` (
    `uid`              integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`              integer      NOT NULL DEFAULT '0',
    `tstamp`           integer      NOT NULL DEFAULT '0',
    `crdate`           integer      NOT NULL DEFAULT '0',
    `cruser_id`        integer      NOT NULL DEFAULT '0',
    `deleted`          integer      NOT NULL DEFAULT '0',
    `hidden`           integer      NOT NULL DEFAULT '0',
    `starttime`        integer      NOT NULL DEFAULT '0',
    `endtime`          integer      NOT NULL DEFAULT '0',
    `sorting`          integer      NOT NULL DEFAULT '0',
    `description`      text COLLATE BINARY,
    `sys_language_uid` integer      NOT NULL DEFAULT '0',
    `l10n_parent`      integer      NOT NULL DEFAULT '0',
    `l10n_state`       text COLLATE BINARY,
    `t3_origuid`       integer      NOT NULL DEFAULT '0',
    `l10n_diffsource`  blob,
    `t3ver_oid`        integer      NOT NULL DEFAULT '0',
    `t3ver_id`         integer      NOT NULL DEFAULT '0',
    `t3ver_label`      varchar(255) NOT NULL DEFAULT '',
    `t3ver_wsid`       integer      NOT NULL DEFAULT '0',
    `t3ver_state`      integer      NOT NULL DEFAULT '0',
    `t3ver_stage`      integer      NOT NULL DEFAULT '0',
    `t3ver_count`      integer      NOT NULL DEFAULT '0',
    `t3ver_tstamp`     integer      NOT NULL DEFAULT '0',
    `t3ver_move_id`    integer      NOT NULL DEFAULT '0',
    `title`            tinytext     NOT NULL,
    `parent`           integer      NOT NULL DEFAULT '0',
    `items`            integer      NOT NULL DEFAULT '0',
    `fe_group`         varchar(100) NOT NULL DEFAULT '0',
    `images`           integer               DEFAULT '0',
    `single_pid`       integer      NOT NULL DEFAULT '0',
    `shortcut`         integer      NOT NULL DEFAULT '0',
    `import_id`        varchar(100) NOT NULL DEFAULT '',
    `import_source`    varchar(100) NOT NULL DEFAULT '',
    `seo_title`        varchar(255) NOT NULL DEFAULT '',
    `seo_description`  text COLLATE BINARY,
    `seo_headline`     varchar(255) NOT NULL DEFAULT '',
    `seo_text`         text COLLATE BINARY,
    `slug`             varchar(2048)         DEFAULT NULL
);
CREATE TABLE `sys_category_record_mm` (
    `uid_local`       integer      NOT NULL DEFAULT '0',
    `uid_foreign`     integer      NOT NULL DEFAULT '0',
    `tablenames`      varchar(255) NOT NULL DEFAULT '',
    `fieldname`       varchar(255) NOT NULL DEFAULT '',
    `sorting`         integer      NOT NULL DEFAULT '0',
    `sorting_foreign` integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `sys_collection` (
    `uid`              integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`              integer      NOT NULL DEFAULT '0',
    `tstamp`           integer      NOT NULL DEFAULT '0',
    `crdate`           integer      NOT NULL DEFAULT '0',
    `cruser_id`        integer      NOT NULL DEFAULT '0',
    `deleted`          integer      NOT NULL DEFAULT '0',
    `hidden`           integer      NOT NULL DEFAULT '0',
    `starttime`        integer      NOT NULL DEFAULT '0',
    `endtime`          integer      NOT NULL DEFAULT '0',
    `fe_group`         varchar(255) NOT NULL DEFAULT '0',
    `description`      text COLLATE BINARY,
    `sys_language_uid` integer      NOT NULL DEFAULT '0',
    `l10n_parent`      integer      NOT NULL DEFAULT '0',
    `l10n_state`       text COLLATE BINARY,
    `t3_origuid`       integer      NOT NULL DEFAULT '0',
    `l10n_diffsource`  blob,
    `t3ver_oid`        integer      NOT NULL DEFAULT '0',
    `t3ver_id`         integer      NOT NULL DEFAULT '0',
    `t3ver_label`      varchar(255) NOT NULL DEFAULT '',
    `t3ver_wsid`       integer      NOT NULL DEFAULT '0',
    `t3ver_state`      integer      NOT NULL DEFAULT '0',
    `t3ver_stage`      integer      NOT NULL DEFAULT '0',
    `t3ver_count`      integer      NOT NULL DEFAULT '0',
    `t3ver_tstamp`     integer      NOT NULL DEFAULT '0',
    `t3ver_move_id`    integer      NOT NULL DEFAULT '0',
    `title`            tinytext COLLATE BINARY,
    `type`             varchar(32)  NOT NULL DEFAULT 'static',
    `table_name`       tinytext COLLATE BINARY,
    `items`            integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `sys_collection_entries` (
    `uid`         integer     NOT NULL PRIMARY KEY AUTOINCREMENT,
    `uid_local`   integer     NOT NULL DEFAULT '0',
    `uid_foreign` integer     NOT NULL DEFAULT '0',
    `tablenames`  varchar(64) NOT NULL DEFAULT '',
    `sorting`     integer     NOT NULL DEFAULT '0'
);
CREATE TABLE `sys_file` (
    `uid`               integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`               integer      NOT NULL DEFAULT '0',
    `tstamp`            integer      NOT NULL DEFAULT '0',
    `last_indexed`      integer      NOT NULL DEFAULT '0',
    `missing`           integer      NOT NULL DEFAULT '0',
    `storage`           integer      NOT NULL DEFAULT '0',
    `type`              varchar(10)  NOT NULL DEFAULT '',
    `metadata`          integer      NOT NULL DEFAULT '0',
    `identifier`        text COLLATE BINARY,
    `identifier_hash`   varchar(40)  NOT NULL DEFAULT '',
    `folder_hash`       varchar(40)  NOT NULL DEFAULT '',
    `extension`         varchar(255) NOT NULL DEFAULT '',
    `mime_type`         varchar(255) NOT NULL DEFAULT '',
    `name`              tinytext COLLATE BINARY,
    `sha1`              varchar(40)  NOT NULL DEFAULT '',
    `size`              integer      NOT NULL DEFAULT '0',
    `creation_date`     integer      NOT NULL DEFAULT '0',
    `modification_date` integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `sys_file_collection` (
    `uid`              integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`              integer      NOT NULL DEFAULT '0',
    `tstamp`           integer      NOT NULL DEFAULT '0',
    `crdate`           integer      NOT NULL DEFAULT '0',
    `cruser_id`        integer      NOT NULL DEFAULT '0',
    `deleted`          integer      NOT NULL DEFAULT '0',
    `hidden`           integer      NOT NULL DEFAULT '0',
    `starttime`        integer      NOT NULL DEFAULT '0',
    `endtime`          integer      NOT NULL DEFAULT '0',
    `description`      text COLLATE BINARY,
    `sys_language_uid` integer      NOT NULL DEFAULT '0',
    `l10n_parent`      integer      NOT NULL DEFAULT '0',
    `l10n_state`       text COLLATE BINARY,
    `t3_origuid`       integer      NOT NULL DEFAULT '0',
    `l10n_diffsource`  blob,
    `t3ver_oid`        integer      NOT NULL DEFAULT '0',
    `t3ver_id`         integer      NOT NULL DEFAULT '0',
    `t3ver_label`      varchar(255) NOT NULL DEFAULT '',
    `t3ver_wsid`       integer      NOT NULL DEFAULT '0',
    `t3ver_state`      integer      NOT NULL DEFAULT '0',
    `t3ver_stage`      integer      NOT NULL DEFAULT '0',
    `t3ver_count`      integer      NOT NULL DEFAULT '0',
    `t3ver_tstamp`     integer      NOT NULL DEFAULT '0',
    `t3ver_move_id`    integer      NOT NULL DEFAULT '0',
    `title`            tinytext COLLATE BINARY,
    `type`             varchar(30)  NOT NULL DEFAULT 'static',
    `files`            integer      NOT NULL DEFAULT '0',
    `storage`          integer      NOT NULL DEFAULT '0',
    `folder`           text COLLATE BINARY,
    `recursive`        integer      NOT NULL DEFAULT '0',
    `category`         integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `sys_file_metadata` (
    `uid`              integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`              integer      NOT NULL DEFAULT '0',
    `tstamp`           integer      NOT NULL DEFAULT '0',
    `crdate`           integer      NOT NULL DEFAULT '0',
    `cruser_id`        integer      NOT NULL DEFAULT '0',
    `sys_language_uid` integer      NOT NULL DEFAULT '0',
    `l10n_parent`      integer      NOT NULL DEFAULT '0',
    `l10n_state`       text COLLATE BINARY,
    `t3_origuid`       integer      NOT NULL DEFAULT '0',
    `l10n_diffsource`  blob,
    `t3ver_oid`        integer      NOT NULL DEFAULT '0',
    `t3ver_id`         integer      NOT NULL DEFAULT '0',
    `t3ver_label`      varchar(255) NOT NULL DEFAULT '',
    `t3ver_wsid`       integer      NOT NULL DEFAULT '0',
    `t3ver_state`      integer      NOT NULL DEFAULT '0',
    `t3ver_stage`      integer      NOT NULL DEFAULT '0',
    `t3ver_count`      integer      NOT NULL DEFAULT '0',
    `t3ver_tstamp`     integer      NOT NULL DEFAULT '0',
    `t3ver_move_id`    integer      NOT NULL DEFAULT '0',
    `file`             integer      NOT NULL DEFAULT '0',
    `title`            tinytext COLLATE BINARY,
    `width`            integer      NOT NULL DEFAULT '0',
    `height`           integer      NOT NULL DEFAULT '0',
    `description`      text COLLATE BINARY,
    `alternative`      text COLLATE BINARY,
    `fe_groups`        tinytext COLLATE BINARY,
    `categories`       integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `sys_file_processedfile` (
    `uid`               integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `tstamp`            integer      NOT NULL DEFAULT '0',
    `crdate`            integer      NOT NULL DEFAULT '0',
    `storage`           integer      NOT NULL DEFAULT '0',
    `original`          integer      NOT NULL DEFAULT '0',
    `identifier`        varchar(512) NOT NULL DEFAULT '',
    `name`              tinytext COLLATE BINARY,
    `configuration`     text COLLATE BINARY,
    `configurationsha1` varchar(40)  NOT NULL DEFAULT '',
    `originalfilesha1`  varchar(40)  NOT NULL DEFAULT '',
    `task_type`         varchar(200) NOT NULL DEFAULT '',
    `checksum`          varchar(10)  NOT NULL DEFAULT '',
    `width`             integer               DEFAULT '0',
    `height`            integer               DEFAULT '0'
);
CREATE TABLE `sys_file_reference` (
    `uid`              integer       NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`              integer       NOT NULL DEFAULT '0',
    `tstamp`           integer       NOT NULL DEFAULT '0',
    `crdate`           integer       NOT NULL DEFAULT '0',
    `cruser_id`        integer       NOT NULL DEFAULT '0',
    `deleted`          integer       NOT NULL DEFAULT '0',
    `hidden`           integer       NOT NULL DEFAULT '0',
    `sys_language_uid` integer       NOT NULL DEFAULT '0',
    `l10n_parent`      integer       NOT NULL DEFAULT '0',
    `l10n_state`       text COLLATE BINARY,
    `l10n_diffsource`  blob,
    `t3ver_oid`        integer       NOT NULL DEFAULT '0',
    `t3ver_id`         integer       NOT NULL DEFAULT '0',
    `t3ver_label`      varchar(255)  NOT NULL DEFAULT '',
    `t3ver_wsid`       integer       NOT NULL DEFAULT '0',
    `t3ver_state`      integer       NOT NULL DEFAULT '0',
    `t3ver_stage`      integer       NOT NULL DEFAULT '0',
    `t3ver_count`      integer       NOT NULL DEFAULT '0',
    `t3ver_tstamp`     integer       NOT NULL DEFAULT '0',
    `t3ver_move_id`    integer       NOT NULL DEFAULT '0',
    `uid_local`        integer       NOT NULL DEFAULT '0',
    `uid_foreign`      integer       NOT NULL DEFAULT '0',
    `tablenames`       varchar(64)   NOT NULL DEFAULT '',
    `fieldname`        varchar(64)   NOT NULL DEFAULT '',
    `sorting_foreign`  integer       NOT NULL DEFAULT '0',
    `table_local`      varchar(64)   NOT NULL DEFAULT '',
    `title`            tinytext COLLATE BINARY,
    `description`      text COLLATE BINARY,
    `alternative`      text COLLATE BINARY,
    `link`             varchar(1024) NOT NULL DEFAULT '',
    `crop`             varchar(4000) NOT NULL DEFAULT '',
    `autoplay`         integer       NOT NULL DEFAULT '0',
    `showinpreview`    integer       NOT NULL DEFAULT '0'
);
CREATE TABLE `sys_file_storage` (
    `uid`                   integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`                   integer      NOT NULL DEFAULT '0',
    `tstamp`                integer      NOT NULL DEFAULT '0',
    `crdate`                integer      NOT NULL DEFAULT '0',
    `cruser_id`             integer      NOT NULL DEFAULT '0',
    `deleted`               integer      NOT NULL DEFAULT '0',
    `description`           text COLLATE BINARY,
    `name`                  varchar(255) NOT NULL DEFAULT '',
    `driver`                tinytext COLLATE BINARY,
    `configuration`         text COLLATE BINARY,
    `is_default`            integer      NOT NULL DEFAULT '0',
    `is_browsable`          integer      NOT NULL DEFAULT '0',
    `is_public`             integer      NOT NULL DEFAULT '0',
    `is_writable`           integer      NOT NULL DEFAULT '0',
    `is_online`             integer      NOT NULL DEFAULT '1',
    `auto_extract_metadata` integer      NOT NULL DEFAULT '1',
    `processingfolder`      tinytext COLLATE BINARY
);
CREATE TABLE `sys_filemounts` (
    `uid`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`         integer      NOT NULL DEFAULT '0',
    `tstamp`      integer      NOT NULL DEFAULT '0',
    `deleted`     integer      NOT NULL DEFAULT '0',
    `hidden`      integer      NOT NULL DEFAULT '0',
    `sorting`     integer      NOT NULL DEFAULT '0',
    `description` text COLLATE BINARY,
    `title`       varchar(255) NOT NULL DEFAULT '',
    `path`        varchar(255) NOT NULL DEFAULT '',
    `base`        integer      NOT NULL DEFAULT '0',
    `read_only`   integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `sys_history` (
    `uid`            integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`            integer      NOT NULL DEFAULT '0',
    `tstamp`         integer      NOT NULL DEFAULT '0',
    `actiontype`     integer      NOT NULL DEFAULT '0',
    `usertype`       varchar(2)   NOT NULL DEFAULT 'BE',
    `userid`         integer               DEFAULT NULL,
    `originaluserid` integer               DEFAULT NULL,
    `recuid`         integer      NOT NULL DEFAULT '0',
    `tablename`      varchar(255) NOT NULL DEFAULT '',
    `history_data`   mediumtext COLLATE BINARY,
    `workspace`      integer               DEFAULT '0'
);
CREATE TABLE `sys_language` (
    `uid`                 integer     NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`                 integer     NOT NULL DEFAULT '0',
    `tstamp`              integer     NOT NULL DEFAULT '0',
    `hidden`              integer     NOT NULL DEFAULT '0',
    `sorting`             integer     NOT NULL DEFAULT '0',
    `title`               varchar(80) NOT NULL DEFAULT '',
    `flag`                varchar(20) NOT NULL DEFAULT '',
    `language_isocode`    varchar(2)  NOT NULL DEFAULT '',
    `static_lang_isocode` integer     NOT NULL DEFAULT '0'
);
CREATE TABLE `sys_lockedrecords` (
    `uid`          integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `userid`       integer      NOT NULL DEFAULT '0',
    `tstamp`       integer      NOT NULL DEFAULT '0',
    `record_table` varchar(255) NOT NULL DEFAULT '',
    `record_uid`   integer      NOT NULL DEFAULT '0',
    `record_pid`   integer      NOT NULL DEFAULT '0',
    `username`     varchar(50)  NOT NULL DEFAULT '',
    `feuserid`     integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `sys_log` (
    `uid`        integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`        integer      NOT NULL DEFAULT '0',
    `tstamp`     integer      NOT NULL DEFAULT '0',
    `userid`     integer      NOT NULL DEFAULT '0',
    `action`     integer      NOT NULL DEFAULT '0',
    `recuid`     integer      NOT NULL DEFAULT '0',
    `tablename`  varchar(255) NOT NULL DEFAULT '',
    `recpid`     integer      NOT NULL DEFAULT '0',
    `error`      integer      NOT NULL DEFAULT '0',
    `details`    text COLLATE BINARY,
    `type`       integer      NOT NULL DEFAULT '0',
    `details_nr` integer      NOT NULL DEFAULT '0',
    `IP`         varchar(39)  NOT NULL DEFAULT '',
    `log_data`   text COLLATE BINARY,
    `event_pid`  integer      NOT NULL DEFAULT '-1',
    `workspace`  integer      NOT NULL DEFAULT '0',
    `NEWid`      varchar(30)  NOT NULL DEFAULT '',
    `request_id` varchar(13)  NOT NULL DEFAULT '',
    `time_micro` double       NOT NULL DEFAULT '0',
    `component`  varchar(255) NOT NULL DEFAULT '',
    `level`      integer      NOT NULL DEFAULT '0',
    `message`    text COLLATE BINARY,
    `data`       text COLLATE BINARY
);
CREATE TABLE `sys_news` (
    `uid`       integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`       integer      NOT NULL DEFAULT '0',
    `tstamp`    integer      NOT NULL DEFAULT '0',
    `crdate`    integer      NOT NULL DEFAULT '0',
    `cruser_id` integer      NOT NULL DEFAULT '0',
    `deleted`   integer      NOT NULL DEFAULT '0',
    `hidden`    integer      NOT NULL DEFAULT '0',
    `starttime` integer      NOT NULL DEFAULT '0',
    `endtime`   integer      NOT NULL DEFAULT '0',
    `title`     varchar(255) NOT NULL DEFAULT '',
    `content`   mediumtext COLLATE BINARY
);
CREATE TABLE `sys_refindex` (
    `hash`        varchar(32)   NOT NULL DEFAULT '',
    `tablename`   varchar(255)  NOT NULL DEFAULT '',
    `recuid`      integer       NOT NULL DEFAULT '0',
    `field`       varchar(64)   NOT NULL DEFAULT '',
    `flexpointer` varchar(255)  NOT NULL DEFAULT '',
    `softref_key` varchar(30)   NOT NULL DEFAULT '',
    `softref_id`  varchar(40)   NOT NULL DEFAULT '',
    `sorting`     integer       NOT NULL DEFAULT '0',
    `deleted`     integer       NOT NULL DEFAULT '0',
    `workspace`   integer       NOT NULL DEFAULT '0',
    `ref_table`   varchar(255)  NOT NULL DEFAULT '',
    `ref_uid`     integer       NOT NULL DEFAULT '0',
    `ref_string`  varchar(1024) NOT NULL DEFAULT '',
    PRIMARY KEY (`hash`)
);
CREATE TABLE `sys_registry` (
    `uid`             integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `entry_namespace` varchar(128) NOT NULL DEFAULT '',
    `entry_key`       varchar(128) NOT NULL DEFAULT '',
    `entry_value`     longblob,
    UNIQUE (`entry_namespace`, `entry_key`)
);
CREATE TABLE `sys_template` (
    `uid`                       integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`                       integer      NOT NULL DEFAULT '0',
    `tstamp`                    integer      NOT NULL DEFAULT '0',
    `crdate`                    integer      NOT NULL DEFAULT '0',
    `cruser_id`                 integer      NOT NULL DEFAULT '0',
    `deleted`                   integer      NOT NULL DEFAULT '0',
    `hidden`                    integer      NOT NULL DEFAULT '0',
    `starttime`                 integer      NOT NULL DEFAULT '0',
    `endtime`                   integer      NOT NULL DEFAULT '0',
    `sorting`                   integer      NOT NULL DEFAULT '0',
    `description`               text COLLATE BINARY,
    `t3_origuid`                integer      NOT NULL DEFAULT '0',
    `t3ver_oid`                 integer      NOT NULL DEFAULT '0',
    `t3ver_id`                  integer      NOT NULL DEFAULT '0',
    `t3ver_label`               varchar(255) NOT NULL DEFAULT '',
    `t3ver_wsid`                integer      NOT NULL DEFAULT '0',
    `t3ver_state`               integer      NOT NULL DEFAULT '0',
    `t3ver_stage`               integer      NOT NULL DEFAULT '0',
    `t3ver_count`               integer      NOT NULL DEFAULT '0',
    `t3ver_tstamp`              integer      NOT NULL DEFAULT '0',
    `t3ver_move_id`             integer      NOT NULL DEFAULT '0',
    `title`                     varchar(255) NOT NULL DEFAULT '',
    `sitetitle`                 varchar(255) NOT NULL DEFAULT '',
    `root`                      integer      NOT NULL DEFAULT '0',
    `clear`                     integer      NOT NULL DEFAULT '0',
    `include_static_file`       text COLLATE BINARY,
    `constants`                 text COLLATE BINARY,
    `config`                    text COLLATE BINARY,
    `nextLevel`                 varchar(5)   NOT NULL DEFAULT '',
    `basedOn`                   tinytext COLLATE BINARY,
    `includeStaticAfterBasedOn` integer      NOT NULL DEFAULT '0',
    `static_file_mode`          integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `tt_content` (
    `uid`                        integer       NOT NULL PRIMARY KEY AUTOINCREMENT,
    `rowDescription`             text COLLATE BINARY,
    `pid`                        integer       NOT NULL DEFAULT '0',
    `tstamp`                     integer       NOT NULL DEFAULT '0',
    `crdate`                     integer       NOT NULL DEFAULT '0',
    `cruser_id`                  integer       NOT NULL DEFAULT '0',
    `deleted`                    integer       NOT NULL DEFAULT '0',
    `hidden`                     integer       NOT NULL DEFAULT '0',
    `starttime`                  integer       NOT NULL DEFAULT '0',
    `endtime`                    integer       NOT NULL DEFAULT '0',
    `fe_group`                   varchar(255)  NOT NULL DEFAULT '0',
    `sorting`                    integer       NOT NULL DEFAULT '0',
    `editlock`                   integer       NOT NULL DEFAULT '0',
    `sys_language_uid`           integer       NOT NULL DEFAULT '0',
    `l18n_parent`                integer       NOT NULL DEFAULT '0',
    `l10n_source`                integer       NOT NULL DEFAULT '0',
    `l10n_state`                 text COLLATE BINARY,
    `t3_origuid`                 integer       NOT NULL DEFAULT '0',
    `l18n_diffsource`            blob,
    `t3ver_oid`                  integer       NOT NULL DEFAULT '0',
    `t3ver_id`                   integer       NOT NULL DEFAULT '0',
    `t3ver_label`                varchar(255)  NOT NULL DEFAULT '',
    `t3ver_wsid`                 integer       NOT NULL DEFAULT '0',
    `t3ver_state`                integer       NOT NULL DEFAULT '0',
    `t3ver_stage`                integer       NOT NULL DEFAULT '0',
    `t3ver_count`                integer       NOT NULL DEFAULT '0',
    `t3ver_tstamp`               integer       NOT NULL DEFAULT '0',
    `t3ver_move_id`              integer       NOT NULL DEFAULT '0',
    `CType`                      varchar(255)  NOT NULL DEFAULT '',
    `header`                     varchar(255)  NOT NULL DEFAULT '',
    `header_position`            varchar(255)  NOT NULL DEFAULT '',
    `bodytext`                   mediumtext COLLATE BINARY,
    `bullets_type`               integer       NOT NULL DEFAULT '0',
    `uploads_description`        integer       NOT NULL DEFAULT '0',
    `uploads_type`               integer       NOT NULL DEFAULT '0',
    `assets`                     integer       NOT NULL DEFAULT '0',
    `image`                      integer       NOT NULL DEFAULT '0',
    `imagewidth`                 integer       NOT NULL DEFAULT '0',
    `imageorient`                integer       NOT NULL DEFAULT '0',
    `imagecols`                  integer       NOT NULL DEFAULT '0',
    `imageborder`                integer       NOT NULL DEFAULT '0',
    `media`                      integer       NOT NULL DEFAULT '0',
    `layout`                     integer       NOT NULL DEFAULT '0',
    `frame_class`                varchar(60)   NOT NULL DEFAULT 'default',
    `cols`                       integer       NOT NULL DEFAULT '0',
    `spaceBefore`                integer       NOT NULL DEFAULT '0',
    `spaceAfter`                 integer       NOT NULL DEFAULT '0',
    `space_before_class`         varchar(60)   NOT NULL DEFAULT '',
    `space_after_class`          varchar(60)   NOT NULL DEFAULT '',
    `records`                    text COLLATE BINARY,
    `pages`                      text COLLATE BINARY,
    `colPos`                     integer       NOT NULL DEFAULT '0',
    `subheader`                  varchar(255)  NOT NULL DEFAULT '',
    `header_link`                varchar(1024) NOT NULL DEFAULT '',
    `image_zoom`                 integer       NOT NULL DEFAULT '0',
    `header_layout`              varchar(30)   NOT NULL DEFAULT '0',
    `list_type`                  varchar(255)  NOT NULL DEFAULT '',
    `sectionIndex`               integer       NOT NULL DEFAULT '0',
    `linkToTop`                  integer       NOT NULL DEFAULT '0',
    `file_collections`           text COLLATE BINARY,
    `filelink_size`              integer       NOT NULL DEFAULT '0',
    `filelink_sorting`           varchar(17)   NOT NULL DEFAULT '',
    `filelink_sorting_direction` varchar(4)    NOT NULL DEFAULT '',
    `target`                     varchar(30)   NOT NULL DEFAULT '',
    `date`                       integer       NOT NULL DEFAULT '0',
    `recursive`                  integer       NOT NULL DEFAULT '0',
    `imageheight`                integer       NOT NULL DEFAULT '0',
    `pi_flexform`                mediumtext COLLATE BINARY,
    `accessibility_title`        varchar(30)   NOT NULL DEFAULT '',
    `accessibility_bypass`       integer       NOT NULL DEFAULT '0',
    `accessibility_bypass_text`  varchar(30)   NOT NULL DEFAULT '',
    `selected_categories`        text COLLATE BINARY,
    `category_field`             varchar(64)   NOT NULL DEFAULT '',
    `table_class`                varchar(60)   NOT NULL DEFAULT '',
    `table_caption`              varchar(255)           DEFAULT NULL,
    `table_delimiter`            integer       NOT NULL DEFAULT '0',
    `table_enclosure`            integer       NOT NULL DEFAULT '0',
    `table_header_position`      integer       NOT NULL DEFAULT '0',
    `table_tfoot`                integer       NOT NULL DEFAULT '0',
    `tx_news_related_news`       integer       NOT NULL DEFAULT '0',
    `tx_theme_related_content`   varchar(255)  NOT NULL DEFAULT '',
    `tx_theme_related_string`    varchar(255)  NOT NULL DEFAULT '',
    `tx_theme_related_parent`    integer       NOT NULL DEFAULT '0',
    `categories`                 integer       NOT NULL DEFAULT '0'
);
CREATE TABLE `tx_extensionmanager_domain_model_extension` (
    `uid`                     integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`                     integer      NOT NULL DEFAULT '0',
    `extension_key`           varchar(60)  NOT NULL DEFAULT '',
    `repository`              integer      NOT NULL DEFAULT '1',
    `version`                 varchar(15)  NOT NULL DEFAULT '',
    `alldownloadcounter`      integer      NOT NULL DEFAULT '0',
    `downloadcounter`         integer      NOT NULL DEFAULT '0',
    `title`                   varchar(150) NOT NULL DEFAULT '',
    `description`             mediumtext COLLATE BINARY,
    `state`                   integer      NOT NULL DEFAULT '0',
    `review_state`            integer      NOT NULL DEFAULT '0',
    `category`                integer      NOT NULL DEFAULT '0',
    `last_updated`            integer      NOT NULL DEFAULT '0',
    `serialized_dependencies` mediumtext COLLATE BINARY,
    `author_name`             varchar(255) NOT NULL DEFAULT '',
    `author_email`            varchar(255) NOT NULL DEFAULT '',
    `ownerusername`           varchar(50)  NOT NULL DEFAULT '',
    `md5hash`                 varchar(35)  NOT NULL DEFAULT '',
    `update_comment`          mediumtext COLLATE BINARY,
    `authorcompany`           varchar(255) NOT NULL DEFAULT '',
    `integer_version`         integer      NOT NULL DEFAULT '0',
    `current_version`         integer      NOT NULL DEFAULT '0',
    `lastreviewedversion`     integer      NOT NULL DEFAULT '0',
    UNIQUE (`extension_key`, `version`, `repository`)
);
CREATE TABLE `tx_extensionmanager_domain_model_repository` (
    `uid`             integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`             integer      NOT NULL DEFAULT '0',
    `title`           varchar(150) NOT NULL DEFAULT '',
    `description`     mediumtext COLLATE BINARY,
    `wsdl_url`        varchar(100) NOT NULL DEFAULT '',
    `mirror_list_url` varchar(100) NOT NULL DEFAULT '',
    `last_update`     integer      NOT NULL DEFAULT '0',
    `extension_count` integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `tx_falsecuredownload_download` (
    `uid`    integer NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`    integer NOT NULL DEFAULT '0',
    `tstamp` integer NOT NULL DEFAULT '0',
    `crdate` integer NOT NULL DEFAULT '0',
    `feuser` integer NOT NULL DEFAULT '0',
    `file`   integer NOT NULL DEFAULT '0'
);
CREATE TABLE `tx_falsecuredownload_folder` (
    `uid`         integer     NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`         integer     NOT NULL DEFAULT '0',
    `tstamp`      integer     NOT NULL DEFAULT '0',
    `crdate`      integer     NOT NULL DEFAULT '0',
    `storage`     integer     NOT NULL DEFAULT '0',
    `folder`      text COLLATE BINARY,
    `folder_hash` varchar(40) NOT NULL DEFAULT '',
    `fe_groups`   tinytext COLLATE BINARY
);
CREATE TABLE `tx_in2code_in2publish_envelope` (
    `uid`      integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `command`  varchar(255) NOT NULL DEFAULT '',
    `request`  text COLLATE BINARY,
    `response` longtext COLLATE BINARY
);
CREATE TABLE `tx_in2code_in2publish_task` (
    `uid`             integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `task_type`       varchar(255) NOT NULL DEFAULT '',
    `configuration`   longtext COLLATE BINARY,
    `creation_date`   datetime              DEFAULT NULL,
    `execution_begin` datetime              DEFAULT NULL,
    `execution_end`   datetime              DEFAULT NULL,
    `messages`        longtext COLLATE BINARY
);
CREATE TABLE `tx_in2publish_notification` (
    `uid`     integer       NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`     integer       NOT NULL DEFAULT '0',
    `be_user` integer       NOT NULL DEFAULT '0',
    `title`   varchar(255)  NOT NULL DEFAULT '',
    `link`    varchar(255)  NOT NULL DEFAULT '',
    `dir`     varchar(4)    NOT NULL DEFAULT '',
    `lang`    varchar(16)   NOT NULL DEFAULT '',
    `body`    varchar(1023) NOT NULL DEFAULT '',
    `icon`    varchar(255)  NOT NULL DEFAULT '',
    `data`    varchar(1023) NOT NULL DEFAULT ''
);
CREATE TABLE `tx_in2publish_wfpn_demand` (
    `uid`               integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`               integer      NOT NULL DEFAULT '0',
    `be_user`           integer      NOT NULL DEFAULT '0',
    `record_table`      varchar(255) NOT NULL DEFAULT '',
    `record_identifier` integer      NOT NULL DEFAULT '0',
    `opt_in`            integer      NOT NULL DEFAULT '0',
    `timestamp`         integer      NOT NULL DEFAULT '0',
    UNIQUE (`be_user`, `record_table`, `record_identifier`)
);
CREATE TABLE `tx_in2publish_workflow_history` (
    `uid`                    integer     NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`                    integer     NOT NULL DEFAULT '0',
    `record_table`           varchar(64) NOT NULL DEFAULT '',
    `record_identifier`      integer     NOT NULL DEFAULT '0',
    `record_pid`             integer     NOT NULL DEFAULT '0',
    `record_language`        integer              DEFAULT NULL,
    `record_language_parent` integer              DEFAULT NULL,
    `record_page_uid`        integer              DEFAULT NULL,
    `backend_user`           integer     NOT NULL DEFAULT '0',
    `creation_date`          integer     NOT NULL DEFAULT '0',
    `state_identifier`       integer     NOT NULL DEFAULT '0',
    `message`                text COLLATE BINARY,
    `scheduled_publish`      integer              DEFAULT NULL,
    `obsolete`               integer     NOT NULL DEFAULT '0'
);
CREATE TABLE `tx_in2publish_workflow_state` (
    `uid`                    integer     NOT NULL PRIMARY KEY AUTOINCREMENT,
    `record_table`           varchar(64) NOT NULL DEFAULT '',
    `record_identifier`      integer     NOT NULL DEFAULT '0',
    `record_pid`             integer     NOT NULL DEFAULT '0',
    `record_language`        integer              DEFAULT NULL,
    `record_language_parent` integer              DEFAULT NULL,
    `record_page_uid`        integer              DEFAULT NULL,
    `backend_user`           integer     NOT NULL DEFAULT '0',
    `creation_date`          integer     NOT NULL DEFAULT '0',
    `state_identifier`       integer     NOT NULL DEFAULT '0',
    `message`                text COLLATE BINARY,
    `scheduled_publish`      integer              DEFAULT NULL
);
CREATE TABLE `tx_in2publishcore_log` (
    `uid`        integer        NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`        integer        NOT NULL DEFAULT '0',
    `request_id` varchar(13)    NOT NULL DEFAULT '',
    `time_micro` decimal(15, 4) NOT NULL DEFAULT '0.0000',
    `component`  varchar(255)   NOT NULL DEFAULT '',
    `level`      integer        NOT NULL DEFAULT '0',
    `message`    text COLLATE BINARY,
    `data`       text COLLATE BINARY
);
CREATE TABLE `tx_news_domain_model_link` (
    `uid`              integer     NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`              integer     NOT NULL DEFAULT '0',
    `tstamp`           integer     NOT NULL DEFAULT '0',
    `crdate`           integer     NOT NULL DEFAULT '0',
    `cruser_id`        integer     NOT NULL DEFAULT '0',
    `sys_language_uid` integer     NOT NULL DEFAULT '0',
    `l10n_parent`      integer     NOT NULL DEFAULT '0',
    `l10n_diffsource`  mediumtext COLLATE BINARY,
    `l10n_source`      integer     NOT NULL DEFAULT '0',
    `t3ver_oid`        integer     NOT NULL DEFAULT '0',
    `t3ver_id`         integer     NOT NULL DEFAULT '0',
    `t3_origuid`       integer     NOT NULL DEFAULT '0',
    `t3ver_wsid`       integer     NOT NULL DEFAULT '0',
    `t3ver_label`      varchar(30) NOT NULL DEFAULT '',
    `t3ver_state`      integer     NOT NULL DEFAULT '0',
    `t3ver_stage`      integer     NOT NULL DEFAULT '0',
    `t3ver_count`      integer     NOT NULL DEFAULT '0',
    `t3ver_tstamp`     integer     NOT NULL DEFAULT '0',
    `t3ver_move_id`    integer     NOT NULL DEFAULT '0',
    `sorting`          integer     NOT NULL DEFAULT '0',
    `deleted`          integer     NOT NULL DEFAULT '0',
    `hidden`           integer     NOT NULL DEFAULT '0',
    `description`      text COLLATE BINARY,
    `l10n_state`       text COLLATE BINARY,
    `parent`           integer     NOT NULL DEFAULT '0',
    `title`            tinytext COLLATE BINARY,
    `uri`              text COLLATE BINARY
);
CREATE TABLE `tx_news_domain_model_news` (
    `uid`               integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`               integer      NOT NULL DEFAULT '0',
    `tstamp`            integer      NOT NULL DEFAULT '0',
    `crdate`            integer      NOT NULL DEFAULT '0',
    `cruser_id`         integer      NOT NULL DEFAULT '0',
    `t3ver_oid`         integer      NOT NULL DEFAULT '0',
    `t3ver_id`          integer      NOT NULL DEFAULT '0',
    `t3ver_wsid`        integer      NOT NULL DEFAULT '0',
    `t3ver_label`       varchar(30)  NOT NULL DEFAULT '',
    `t3ver_state`       integer      NOT NULL DEFAULT '0',
    `t3ver_stage`       integer      NOT NULL DEFAULT '0',
    `t3ver_count`       integer      NOT NULL DEFAULT '0',
    `t3ver_tstamp`      integer      NOT NULL DEFAULT '0',
    `t3ver_move_id`     integer      NOT NULL DEFAULT '0',
    `t3_origuid`        integer      NOT NULL DEFAULT '0',
    `editlock`          integer      NOT NULL DEFAULT '0',
    `sys_language_uid`  integer      NOT NULL DEFAULT '0',
    `l10n_parent`       integer      NOT NULL DEFAULT '0',
    `l10n_diffsource`   mediumtext COLLATE BINARY,
    `l10n_source`       integer      NOT NULL DEFAULT '0',
    `deleted`           integer      NOT NULL DEFAULT '0',
    `hidden`            integer      NOT NULL DEFAULT '0',
    `starttime`         integer      NOT NULL DEFAULT '0',
    `endtime`           integer      NOT NULL DEFAULT '0',
    `fe_group`          varchar(100) NOT NULL DEFAULT '0',
    `notes`             text COLLATE BINARY,
    `l10n_state`        text COLLATE BINARY,
    `sorting`           integer      NOT NULL DEFAULT '0',
    `title`             tinytext COLLATE BINARY,
    `teaser`            text COLLATE BINARY,
    `bodytext`          mediumtext COLLATE BINARY,
    `datetime`          integer      NOT NULL DEFAULT '0',
    `archive`           integer      NOT NULL DEFAULT '0',
    `author`            tinytext COLLATE BINARY,
    `author_email`      tinytext COLLATE BINARY,
    `categories`        integer      NOT NULL DEFAULT '0',
    `related`           integer      NOT NULL DEFAULT '0',
    `related_from`      integer      NOT NULL DEFAULT '0',
    `related_files`     tinytext COLLATE BINARY,
    `fal_related_files` integer               DEFAULT '0',
    `related_links`     tinytext COLLATE BINARY,
    `type`              varchar(100) NOT NULL DEFAULT '0',
    `keywords`          text COLLATE BINARY,
    `description`       text COLLATE BINARY,
    `tags`              integer      NOT NULL DEFAULT '0',
    `media`             text COLLATE BINARY,
    `fal_media`         integer               DEFAULT '0',
    `internalurl`       text COLLATE BINARY,
    `externalurl`       text COLLATE BINARY,
    `istopnews`         integer      NOT NULL DEFAULT '0',
    `content_elements`  integer      NOT NULL DEFAULT '0',
    `path_segment`      varchar(2048)         DEFAULT NULL,
    `alternative_title` tinytext COLLATE BINARY,
    `import_id`         varchar(100) NOT NULL DEFAULT '',
    `import_source`     varchar(100) NOT NULL DEFAULT ''
);
CREATE TABLE `tx_news_domain_model_news_related_mm` (
    `uid_local`       integer NOT NULL DEFAULT '0',
    `uid_foreign`     integer NOT NULL DEFAULT '0',
    `sorting`         integer NOT NULL DEFAULT '0',
    `sorting_foreign` integer NOT NULL DEFAULT '0'
);
CREATE TABLE `tx_news_domain_model_news_tag_mm` (
    `uid_local`   integer NOT NULL DEFAULT '0',
    `uid_foreign` integer NOT NULL DEFAULT '0',
    `sorting`     integer NOT NULL DEFAULT '0'
);
CREATE TABLE `tx_news_domain_model_news_ttcontent_mm` (
    `uid_local`   integer NOT NULL DEFAULT '0',
    `uid_foreign` integer NOT NULL DEFAULT '0',
    `sorting`     integer NOT NULL DEFAULT '0'
);
CREATE TABLE `tx_news_domain_model_tag` (
    `uid`              integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`              integer      NOT NULL DEFAULT '0',
    `tstamp`           integer      NOT NULL DEFAULT '0',
    `crdate`           integer      NOT NULL DEFAULT '0',
    `cruser_id`        integer      NOT NULL DEFAULT '0',
    `deleted`          integer      NOT NULL DEFAULT '0',
    `hidden`           integer      NOT NULL DEFAULT '0',
    `sys_language_uid` integer      NOT NULL DEFAULT '0',
    `l10n_parent`      integer      NOT NULL DEFAULT '0',
    `l10n_diffsource`  mediumtext COLLATE BINARY,
    `l10n_source`      integer      NOT NULL DEFAULT '0',
    `notes`            text COLLATE BINARY,
    `l10n_state`       text COLLATE BINARY,
    `sorting`          integer      NOT NULL DEFAULT '0',
    `t3ver_oid`        integer      NOT NULL DEFAULT '0',
    `t3ver_id`         integer      NOT NULL DEFAULT '0',
    `t3_origuid`       integer      NOT NULL DEFAULT '0',
    `t3ver_wsid`       integer      NOT NULL DEFAULT '0',
    `t3ver_label`      varchar(30)  NOT NULL DEFAULT '',
    `t3ver_state`      integer      NOT NULL DEFAULT '0',
    `t3ver_stage`      integer      NOT NULL DEFAULT '0',
    `t3ver_count`      integer      NOT NULL DEFAULT '0',
    `t3ver_tstamp`     integer      NOT NULL DEFAULT '0',
    `t3ver_move_id`    integer      NOT NULL DEFAULT '0',
    `title`            tinytext COLLATE BINARY,
    `slug`             varchar(2048)         DEFAULT NULL,
    `seo_title`        varchar(255) NOT NULL DEFAULT '',
    `seo_description`  text COLLATE BINARY,
    `seo_headline`     varchar(255) NOT NULL DEFAULT '',
    `seo_text`         text COLLATE BINARY
);
CREATE TABLE `tx_powermail_domain_model_answer` (
    `uid`              integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`              integer      NOT NULL DEFAULT '0',
    `tstamp`           integer      NOT NULL DEFAULT '0',
    `crdate`           integer      NOT NULL DEFAULT '0',
    `cruser_id`        integer      NOT NULL DEFAULT '0',
    `deleted`          integer      NOT NULL DEFAULT '0',
    `hidden`           integer      NOT NULL DEFAULT '0',
    `starttime`        integer      NOT NULL DEFAULT '0',
    `endtime`          integer      NOT NULL DEFAULT '0',
    `t3ver_oid`        integer      NOT NULL DEFAULT '0',
    `t3ver_id`         integer      NOT NULL DEFAULT '0',
    `t3ver_wsid`       integer      NOT NULL DEFAULT '0',
    `t3ver_label`      varchar(255) NOT NULL DEFAULT '',
    `t3ver_state`      integer      NOT NULL DEFAULT '0',
    `t3ver_stage`      integer      NOT NULL DEFAULT '0',
    `t3ver_count`      integer      NOT NULL DEFAULT '0',
    `t3ver_tstamp`     integer      NOT NULL DEFAULT '0',
    `t3ver_move_id`    integer      NOT NULL DEFAULT '0',
    `t3_origuid`       integer      NOT NULL DEFAULT '0',
    `sys_language_uid` integer      NOT NULL DEFAULT '0',
    `l10n_parent`      integer      NOT NULL DEFAULT '0',
    `l10n_diffsource`  blob,
    `l10n_state`       text COLLATE BINARY,
    `mail`             integer      NOT NULL DEFAULT '0',
    `value`            text         NOT NULL,
    `value_type`       integer      NOT NULL DEFAULT '0',
    `field`            integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `tx_powermail_domain_model_field` (
    `uid`                      integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`                      integer      NOT NULL DEFAULT '0',
    `tstamp`                   integer      NOT NULL DEFAULT '0',
    `crdate`                   integer      NOT NULL DEFAULT '0',
    `cruser_id`                integer      NOT NULL DEFAULT '0',
    `deleted`                  integer      NOT NULL DEFAULT '0',
    `hidden`                   integer      NOT NULL DEFAULT '0',
    `starttime`                integer      NOT NULL DEFAULT '0',
    `endtime`                  integer      NOT NULL DEFAULT '0',
    `sorting`                  integer      NOT NULL DEFAULT '0',
    `t3ver_oid`                integer      NOT NULL DEFAULT '0',
    `t3ver_id`                 integer      NOT NULL DEFAULT '0',
    `t3ver_wsid`               integer      NOT NULL DEFAULT '0',
    `t3ver_label`              varchar(255) NOT NULL DEFAULT '',
    `t3ver_state`              integer      NOT NULL DEFAULT '0',
    `t3ver_stage`              integer      NOT NULL DEFAULT '0',
    `t3ver_count`              integer      NOT NULL DEFAULT '0',
    `t3ver_tstamp`             integer      NOT NULL DEFAULT '0',
    `t3ver_move_id`            integer      NOT NULL DEFAULT '0',
    `t3_origuid`               integer      NOT NULL DEFAULT '0',
    `sys_language_uid`         integer      NOT NULL DEFAULT '0',
    `l10n_parent`              integer      NOT NULL DEFAULT '0',
    `l10n_diffsource`          blob,
    `l10n_state`               text COLLATE BINARY,
    `pages`                    integer      NOT NULL DEFAULT '0',
    `title`                    varchar(255) NOT NULL DEFAULT '',
    `type`                     varchar(255) NOT NULL DEFAULT '',
    `settings`                 text         NOT NULL,
    `path`                     varchar(255) NOT NULL DEFAULT '',
    `content_element`          integer      NOT NULL DEFAULT '0',
    `text`                     text         NOT NULL,
    `prefill_value`            text         NOT NULL,
    `placeholder`              text         NOT NULL,
    `create_from_typoscript`   text         NOT NULL,
    `validation`               integer      NOT NULL DEFAULT '0',
    `validation_configuration` varchar(255) NOT NULL DEFAULT '',
    `css`                      varchar(255) NOT NULL DEFAULT '',
    `description`              varchar(255) NOT NULL DEFAULT '',
    `multiselect`              integer      NOT NULL DEFAULT '0',
    `datepicker_settings`      varchar(255) NOT NULL DEFAULT '',
    `feuser_value`             varchar(255) NOT NULL DEFAULT '',
    `sender_email`             integer      NOT NULL DEFAULT '0',
    `sender_name`              integer      NOT NULL DEFAULT '0',
    `mandatory`                integer      NOT NULL DEFAULT '0',
    `own_marker_select`        integer      NOT NULL DEFAULT '0',
    `marker`                   varchar(255) NOT NULL DEFAULT '',
    `auto_marker`              integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `tx_powermail_domain_model_form` (
    `uid`              integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`              integer      NOT NULL DEFAULT '0',
    `tstamp`           integer      NOT NULL DEFAULT '0',
    `crdate`           integer      NOT NULL DEFAULT '0',
    `cruser_id`        integer      NOT NULL DEFAULT '0',
    `deleted`          integer      NOT NULL DEFAULT '0',
    `hidden`           integer      NOT NULL DEFAULT '0',
    `starttime`        integer      NOT NULL DEFAULT '0',
    `endtime`          integer      NOT NULL DEFAULT '0',
    `t3ver_oid`        integer      NOT NULL DEFAULT '0',
    `t3ver_id`         integer      NOT NULL DEFAULT '0',
    `t3ver_wsid`       integer      NOT NULL DEFAULT '0',
    `t3ver_label`      varchar(255) NOT NULL DEFAULT '',
    `t3ver_state`      integer      NOT NULL DEFAULT '0',
    `t3ver_stage`      integer      NOT NULL DEFAULT '0',
    `t3ver_count`      integer      NOT NULL DEFAULT '0',
    `t3ver_tstamp`     integer      NOT NULL DEFAULT '0',
    `t3ver_move_id`    integer      NOT NULL DEFAULT '0',
    `t3_origuid`       integer      NOT NULL DEFAULT '0',
    `sys_language_uid` integer      NOT NULL DEFAULT '0',
    `l10n_parent`      integer      NOT NULL DEFAULT '0',
    `l10n_diffsource`  blob,
    `l10n_state`       text COLLATE BINARY,
    `title`            varchar(255) NOT NULL DEFAULT '',
    `note`             integer      NOT NULL DEFAULT '0',
    `css`              varchar(255) NOT NULL DEFAULT '',
    `pages`            varchar(255) NOT NULL DEFAULT '',
    `is_dummy_record`  integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `tx_powermail_domain_model_mail` (
    `uid`                         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`                         integer      NOT NULL DEFAULT '0',
    `tstamp`                      integer      NOT NULL DEFAULT '0',
    `crdate`                      integer      NOT NULL DEFAULT '0',
    `cruser_id`                   integer      NOT NULL DEFAULT '0',
    `deleted`                     integer      NOT NULL DEFAULT '0',
    `hidden`                      integer      NOT NULL DEFAULT '0',
    `starttime`                   integer      NOT NULL DEFAULT '0',
    `endtime`                     integer      NOT NULL DEFAULT '0',
    `t3ver_oid`                   integer      NOT NULL DEFAULT '0',
    `t3ver_id`                    integer      NOT NULL DEFAULT '0',
    `t3ver_wsid`                  integer      NOT NULL DEFAULT '0',
    `t3ver_label`                 varchar(255) NOT NULL DEFAULT '',
    `t3ver_state`                 integer      NOT NULL DEFAULT '0',
    `t3ver_stage`                 integer      NOT NULL DEFAULT '0',
    `t3ver_count`                 integer      NOT NULL DEFAULT '0',
    `t3ver_tstamp`                integer      NOT NULL DEFAULT '0',
    `t3ver_move_id`               integer      NOT NULL DEFAULT '0',
    `t3_origuid`                  integer      NOT NULL DEFAULT '0',
    `sys_language_uid`            integer      NOT NULL DEFAULT '0',
    `l10n_parent`                 integer      NOT NULL DEFAULT '0',
    `l10n_diffsource`             blob,
    `l10n_state`                  text COLLATE BINARY,
    `sender_name`                 varchar(255) NOT NULL DEFAULT '',
    `sender_mail`                 varchar(255) NOT NULL DEFAULT '',
    `subject`                     varchar(255) NOT NULL DEFAULT '',
    `receiver_mail`               varchar(255) NOT NULL DEFAULT '',
    `body`                        text         NOT NULL,
    `feuser`                      integer      NOT NULL DEFAULT '0',
    `sender_ip`                   tinytext     NOT NULL,
    `user_agent`                  text         NOT NULL,
    `time`                        integer      NOT NULL DEFAULT '0',
    `form`                        integer      NOT NULL DEFAULT '0',
    `answers`                     integer      NOT NULL DEFAULT '0',
    `marketing_referer_domain`    text COLLATE BINARY,
    `marketing_referer`           text COLLATE BINARY,
    `marketing_country`           text COLLATE BINARY,
    `marketing_mobile_device`     integer      NOT NULL DEFAULT '0',
    `marketing_frontend_language` integer      NOT NULL DEFAULT '0',
    `marketing_browser_language`  text COLLATE BINARY,
    `marketing_page_funnel`       text COLLATE BINARY,
    `spam_factor`                 varchar(255) NOT NULL DEFAULT ''
);
CREATE TABLE `tx_powermail_domain_model_page` (
    `uid`              integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`              integer      NOT NULL DEFAULT '0',
    `tstamp`           integer      NOT NULL DEFAULT '0',
    `crdate`           integer      NOT NULL DEFAULT '0',
    `cruser_id`        integer      NOT NULL DEFAULT '0',
    `deleted`          integer      NOT NULL DEFAULT '0',
    `hidden`           integer      NOT NULL DEFAULT '0',
    `starttime`        integer      NOT NULL DEFAULT '0',
    `endtime`          integer      NOT NULL DEFAULT '0',
    `sorting`          integer      NOT NULL DEFAULT '0',
    `t3ver_oid`        integer      NOT NULL DEFAULT '0',
    `t3ver_id`         integer      NOT NULL DEFAULT '0',
    `t3ver_wsid`       integer      NOT NULL DEFAULT '0',
    `t3ver_label`      varchar(255) NOT NULL DEFAULT '',
    `t3ver_state`      integer      NOT NULL DEFAULT '0',
    `t3ver_stage`      integer      NOT NULL DEFAULT '0',
    `t3ver_count`      integer      NOT NULL DEFAULT '0',
    `t3ver_tstamp`     integer      NOT NULL DEFAULT '0',
    `t3ver_move_id`    integer      NOT NULL DEFAULT '0',
    `t3_origuid`       integer      NOT NULL DEFAULT '0',
    `sys_language_uid` integer      NOT NULL DEFAULT '0',
    `l10n_parent`      integer      NOT NULL DEFAULT '0',
    `l10n_diffsource`  blob,
    `l10n_state`       text COLLATE BINARY,
    `forms`            integer      NOT NULL DEFAULT '0',
    `title`            varchar(255) NOT NULL DEFAULT '',
    `css`              varchar(255) NOT NULL DEFAULT '',
    `fields`           integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `tx_rsaauth_keys` (
    `uid`       integer NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`       integer NOT NULL DEFAULT '0',
    `crdate`    integer NOT NULL DEFAULT '0',
    `key_value` text COLLATE BINARY
);
CREATE TABLE `tx_scheduler_task` (
    `uid`                    integer    NOT NULL PRIMARY KEY AUTOINCREMENT,
    `crdate`                 integer    NOT NULL DEFAULT '0',
    `disable`                integer    NOT NULL DEFAULT '0',
    `deleted`                integer    NOT NULL DEFAULT '0',
    `description`            text COLLATE BINARY,
    `nextexecution`          integer    NOT NULL DEFAULT '0',
    `lastexecution_time`     integer    NOT NULL DEFAULT '0',
    `lastexecution_failure`  text COLLATE BINARY,
    `lastexecution_context`  varchar(3) NOT NULL DEFAULT '',
    `serialized_task_object` blob,
    `serialized_executions`  blob,
    `task_group`             integer    NOT NULL DEFAULT '0'
);
CREATE TABLE `tx_scheduler_task_group` (
    `uid`         integer     NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`         integer     NOT NULL DEFAULT '0',
    `tstamp`      integer     NOT NULL DEFAULT '0',
    `crdate`      integer     NOT NULL DEFAULT '0',
    `cruser_id`   integer     NOT NULL DEFAULT '0',
    `deleted`     integer     NOT NULL DEFAULT '0',
    `hidden`      integer     NOT NULL DEFAULT '0',
    `sorting`     integer     NOT NULL DEFAULT '0',
    `groupName`   varchar(80) NOT NULL DEFAULT '',
    `description` text COLLATE BINARY
);
CREATE TABLE `tx_solr_cache` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `crdate`     integer      NOT NULL DEFAULT '0',
    `content`    blob,
    `lifetime`   integer      NOT NULL DEFAULT '0'
);
CREATE TABLE `tx_solr_cache_tags` (
    `id`         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `identifier` varchar(250) NOT NULL DEFAULT '',
    `tag`        varchar(250) NOT NULL DEFAULT ''
);
CREATE TABLE `tx_solr_indexqueue_indexing_property` (
    `uid`            integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `root`           integer      NOT NULL DEFAULT '0',
    `item_id`        integer      NOT NULL DEFAULT '0',
    `property_key`   varchar(255) NOT NULL DEFAULT '',
    `property_value` mediumtext   NOT NULL
);
CREATE TABLE `tx_solr_indexqueue_item` (
    `uid`                     integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `root`                    integer      NOT NULL DEFAULT '0',
    `item_type`               varchar(255) NOT NULL DEFAULT '',
    `item_uid`                integer      NOT NULL DEFAULT '0',
    `indexing_configuration`  varchar(255) NOT NULL DEFAULT '',
    `has_indexing_properties` integer      NOT NULL DEFAULT '0',
    `indexing_priority`       integer      NOT NULL DEFAULT '0',
    `changed`                 integer      NOT NULL DEFAULT '0',
    `indexed`                 integer      NOT NULL DEFAULT '0',
    `errors`                  text         NOT NULL,
    `pages_mountidentifier`   varchar(255) NOT NULL DEFAULT ''
);
CREATE TABLE `tx_solr_last_searches` (
    `sequence_id` integer      NOT NULL DEFAULT '0',
    `tstamp`      integer      NOT NULL DEFAULT '0',
    `keywords`    varchar(128) NOT NULL DEFAULT '',
    PRIMARY KEY (`sequence_id`)
);
CREATE TABLE `tx_solr_statistics` (
    `uid`               integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`               integer      NOT NULL DEFAULT '0',
    `root_pid`          integer      NOT NULL DEFAULT '0',
    `tstamp`            integer      NOT NULL DEFAULT '0',
    `language`          integer      NOT NULL DEFAULT '0',
    `num_found`         integer      NOT NULL DEFAULT '0',
    `suggestions_shown` integer      NOT NULL DEFAULT '0',
    `time_total`        integer      NOT NULL DEFAULT '0',
    `time_preparation`  integer      NOT NULL DEFAULT '0',
    `time_processing`   integer      NOT NULL DEFAULT '0',
    `feuser_id`         integer      NOT NULL DEFAULT '0',
    `cookie`            varchar(32)  NOT NULL DEFAULT '',
    `ip`                varchar(255) NOT NULL DEFAULT '',
    `keywords`          varchar(128) NOT NULL DEFAULT '',
    `page`              integer      NOT NULL DEFAULT '0',
    `filters`           blob,
    `sorting`           varchar(128) NOT NULL DEFAULT '',
    `parameters`        blob
);
CREATE TABLE `tx_t3amserver_client` (
    `uid`             integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
    `pid`             integer      NOT NULL DEFAULT '0',
    `tstamp`          integer      NOT NULL DEFAULT '0',
    `crdate`          integer      NOT NULL DEFAULT '0',
    `cruser_id`       integer      NOT NULL DEFAULT '0',
    `deleted`         integer      NOT NULL DEFAULT '0',
    `disabled`        integer      NOT NULL DEFAULT '0',
    `instance_notice` text COLLATE BINARY,
    `identifier`      varchar(255) NOT NULL DEFAULT '',
    `token`           varchar(255) NOT NULL DEFAULT ''
);
CREATE TABLE `tx_t3amserver_keys` (
    `uid`       integer NOT NULL PRIMARY KEY AUTOINCREMENT,
    `key_value` text COLLATE BINARY
);
CREATE INDEX "idx_fe_users_username" ON "fe_users"(`username`);
CREATE INDEX "idx_fe_users_is_online" ON "fe_users"(`is_online`);
CREATE INDEX "idx_sys_history_recordident_2" ON "sys_history"(`tablename`, `tstamp`);
CREATE INDEX "idx_sys_history_parent" ON "sys_history"(`pid`);
CREATE INDEX "idx_be_users_parent" ON "be_users"(`pid`, `deleted`, `disable`);
CREATE INDEX "idx_tx_extensionmanager_domain_model_extension_index_versionrepo" ON "tx_extensionmanager_domain_model_extension"(`integer_version`, `repository`, `extension_key`);
CREATE INDEX "idx_tx_extensionmanager_domain_model_extension_index_currentversions" ON "tx_extensionmanager_domain_model_extension"(`current_version`, `review_state`);
CREATE INDEX "idx_tx_extensionmanager_domain_model_extension_parent" ON "tx_extensionmanager_domain_model_extension"(`pid`);
CREATE INDEX "idx_sys_category_category_list" ON "sys_category"(`pid`, `deleted`, `sys_language_uid`);
CREATE INDEX "idx_sys_category_parent" ON "sys_category"(`pid`, `deleted`, `hidden`);
CREATE INDEX "idx_sys_category_t3ver_oid" ON "sys_category"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_sys_category_import" ON "sys_category"(`import_id`, `import_source`);
CREATE INDEX "idx_sys_template_parent" ON "sys_template"(`pid`, `deleted`, `hidden`);
CREATE INDEX "idx_sys_template_t3ver_oid" ON "sys_template"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_tx_in2publishcore_log_request" ON "tx_in2publishcore_log"(`request_id`);
CREATE INDEX "idx_tx_news_domain_model_news_tag_mm_uid_foreign" ON "tx_news_domain_model_news_tag_mm"(`uid_foreign`);
CREATE INDEX "idx_tx_in2publish_workflow_state_find_scheduled" ON "tx_in2publish_workflow_state"(`state_identifier`, `scheduled_publish`);
CREATE INDEX "idx_tx_in2publish_workflow_state_custom" ON "tx_in2publish_workflow_state"(`record_pid`);
CREATE INDEX "idx_sys_file_reference_deleted" ON "sys_file_reference"(`deleted`);
CREATE INDEX "idx_sys_file_reference_uid_local" ON "sys_file_reference"(`uid_local`);
CREATE INDEX "idx_sys_file_reference_uid_foreign" ON "sys_file_reference"(`uid_foreign`);
CREATE INDEX "idx_sys_file_reference_combined_1" ON "sys_file_reference"(`l10n_parent`, `t3ver_oid`, `t3ver_wsid`, `t3ver_state`, `deleted`);
CREATE INDEX "idx_sys_file_reference_parent" ON "sys_file_reference"(`pid`, `deleted`, `hidden`);
CREATE INDEX "idx_sys_file_reference_t3ver_oid" ON "sys_file_reference"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_tx_powermail_domain_model_page_parent_form" ON "tx_powermail_domain_model_page"(`forms`);
CREATE INDEX "idx_tx_powermail_domain_model_page_t3ver_oid" ON "tx_powermail_domain_model_page"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_tx_powermail_domain_model_page_language" ON "tx_powermail_domain_model_page"(`l10n_parent`, `sys_language_uid`);
CREATE INDEX "idx_tx_powermail_domain_model_answer_mail" ON "tx_powermail_domain_model_answer"(`mail`);
CREATE INDEX "idx_tx_powermail_domain_model_answer_deleted" ON "tx_powermail_domain_model_answer"(`deleted`);
CREATE INDEX "idx_tx_powermail_domain_model_answer_hidden" ON "tx_powermail_domain_model_answer"(`hidden`);
CREATE INDEX "idx_tx_powermail_domain_model_answer_starttime" ON "tx_powermail_domain_model_answer"(`starttime`);
CREATE INDEX "idx_tx_powermail_domain_model_answer_endtime" ON "tx_powermail_domain_model_answer"(`endtime`);
CREATE INDEX "idx_tx_powermail_domain_model_answer_t3ver_oid" ON "tx_powermail_domain_model_answer"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_tx_powermail_domain_model_answer_language" ON "tx_powermail_domain_model_answer"(`l10n_parent`, `sys_language_uid`);
CREATE INDEX "idx_sys_file_metadata_fal_filelist" ON "sys_file_metadata"(`l10n_parent`, `sys_language_uid`);
CREATE INDEX "idx_sys_file_metadata_parent" ON "sys_file_metadata"(`pid`);
CREATE INDEX "idx_sys_file_metadata_t3ver_oid" ON "sys_file_metadata"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_sys_category_record_mm_uid_foreign_tablefield" ON "sys_category_record_mm"(`uid_foreign`, `tablenames`, `fieldname`, `sorting_foreign`);
CREATE INDEX "idx_sys_file_folder" ON "sys_file"(`storage`, `folder_hash`);
CREATE INDEX "idx_sys_file_tstamp" ON "sys_file"(`tstamp`);
CREATE INDEX "idx_sys_file_lastindex" ON "sys_file"(`last_indexed`);
CREATE INDEX "idx_sys_file_sha1" ON "sys_file"(`sha1`);
CREATE INDEX "idx_sys_file_parent" ON "sys_file"(`pid`);
CREATE INDEX "idx_tx_solr_cache_tags_cache_tag" ON "tx_solr_cache_tags"(`tag`);
CREATE INDEX "idx_cf_cache_news_category_tags_cache_tag" ON "cf_cache_news_category_tags"(`tag`);
CREATE INDEX "idx_cf_tx_solr_configuration_tags_cache_tag" ON "cf_tx_solr_configuration_tags"(`tag`);
CREATE INDEX "idx_cf_cache_pagesection_tags_cache_tag" ON "cf_cache_pagesection_tags"(`tag`);
CREATE INDEX "idx_tx_solr_indexqueue_item_indexing_priority_changed" ON "tx_solr_indexqueue_item"(`indexing_priority`, `changed`);
CREATE INDEX "idx_tx_solr_indexqueue_item_item_id" ON "tx_solr_indexqueue_item"(`item_type`, `item_uid`);
CREATE INDEX "idx_tx_solr_indexqueue_item_pages_mountpoint" ON "tx_solr_indexqueue_item"(`item_type`, `item_uid`,
                                                                                         `has_indexing_properties`,
                                                                                         `pages_mountidentifier`);
CREATE INDEX "idx_cf_cache_imagesizes_tags_cache_tag" ON "cf_cache_imagesizes_tags"(`tag`);
CREATE INDEX "idx_sys_file_processedfile_identifier" ON "sys_file_processedfile"(`storage`, `identifier`);
CREATE INDEX "idx_cf_in2publish_core_tags_cache_tag" ON "cf_in2publish_core_tags"(`tag`);
CREATE INDEX "idx_cf_cache_rootline_tags_cache_tag" ON "cf_cache_rootline_tags"(`tag`);
CREATE INDEX "idx_tx_powermail_domain_model_field_parent_page" ON "tx_powermail_domain_model_field"(`pages`);
CREATE INDEX "idx_tx_powermail_domain_model_field_t3ver_oid" ON "tx_powermail_domain_model_field"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_tx_powermail_domain_model_field_language" ON "tx_powermail_domain_model_field"(`l10n_parent`, `sys_language_uid`);
CREATE INDEX "idx_cf_cache_pages_tags_cache_tag" ON "cf_cache_pages_tags"(`tag`);
CREATE INDEX "idx_sys_refindex_lookup_uid" ON "sys_refindex"(`ref_table`, `ref_uid`);
CREATE INDEX "idx_sys_refindex_lookup_string" ON "sys_refindex"(`ref_string`);
CREATE INDEX "idx_cf_cache_hash_tags_cache_tag" ON "cf_cache_hash_tags"(`tag`);
CREATE INDEX "idx_cf_in2publish_tags_cache_tag" ON "cf_in2publish_tags"(`tag`);
CREATE INDEX "idx_tx_t3amserver_client_parent" ON "tx_t3amserver_client"(`pid`);
CREATE INDEX "idx_tx_solr_statistics_rootpid_tstamp" ON "tx_solr_statistics"(`root_pid`, `tstamp`);
CREATE INDEX "idx_pages_determineSiteRoot" ON "pages"(`is_siteroot`);
CREATE INDEX "idx_pages_language_identifier" ON "pages"(`l10n_parent`, `sys_language_uid`);
CREATE INDEX "idx_pages_parent" ON "pages"(`pid`, `deleted`, `hidden`);
CREATE INDEX "idx_pages_t3ver_oid" ON "pages"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_pages_content_from_pid_deleted" ON "pages"(`content_from_pid`, `deleted`);
CREATE INDEX "idx_pages_doktype_no_search_deleted" ON "pages"(`doktype`, `no_search`, `deleted`);
CREATE INDEX "idx_cf_tx_solr_tags_cache_tag" ON "cf_tx_solr_tags"(`tag`);
CREATE INDEX "idx_tx_news_domain_model_link_t3ver_oid" ON "tx_news_domain_model_link"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_tx_powermail_domain_model_form_t3ver_oid" ON "tx_powermail_domain_model_form"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_tx_powermail_domain_model_form_language" ON "tx_powermail_domain_model_form"(`l10n_parent`, `sys_language_uid`);
CREATE INDEX "idx_tx_news_domain_model_news_sys_language_uid_l10n_parent" ON "tx_news_domain_model_news"(`sys_language_uid`, `l10n_parent`);
CREATE INDEX "idx_tx_news_domain_model_news_import" ON "tx_news_domain_model_news"(`import_id`, `import_source`);
CREATE INDEX "idx_tx_news_domain_model_news_t3ver_oid" ON "tx_news_domain_model_news"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_tx_news_domain_model_news_ttcontent_mm_uid_foreign" ON "tx_news_domain_model_news_ttcontent_mm"(`uid_foreign`);
CREATE INDEX "idx_sys_log_recuidIdx" ON "sys_log"(`recuid`);
CREATE INDEX "idx_sys_log_user_auth" ON "sys_log"(`type`, `action`, `tstamp`);
CREATE INDEX "idx_sys_log_request" ON "sys_log"(`request_id`);
CREATE INDEX "idx_sys_log_combined_1" ON "sys_log"(`tstamp`, `type`, `userid`);
CREATE INDEX "idx_sys_log_parent" ON "sys_log"(`pid`);
CREATE INDEX "idx_cf_extbase_datamapfactory_datamap_tags_cache_tag" ON "cf_extbase_datamapfactory_datamap_tags"(`tag`);
CREATE INDEX "idx_sys_collection_entries_uid_foreign" ON "sys_collection_entries"(`uid_foreign`);
CREATE INDEX "idx_sys_collection_t3ver_oid" ON "sys_collection"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_tx_powermail_domain_model_mail_t3ver_oid" ON "tx_powermail_domain_model_mail"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_tx_powermail_domain_model_mail_language" ON "tx_powermail_domain_model_mail"(`l10n_parent`, `sys_language_uid`);
CREATE INDEX "idx_backend_layout_t3ver_oid" ON "backend_layout"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_tx_news_domain_model_news_related_mm_uid_foreign" ON "tx_news_domain_model_news_related_mm"(`uid_foreign`);
CREATE INDEX "idx_sys_file_collection_t3ver_oid" ON "sys_file_collection"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_tt_content_t3ver_oid" ON "tt_content"(`t3ver_oid`, `t3ver_wsid`);
CREATE INDEX "idx_tt_content_language" ON "tt_content"(`l18n_parent`, `sys_language_uid`);
CREATE INDEX "idx_tt_content_index_newscontent" ON "tt_content"(`tx_news_related_news`);
END TRANSACTION;
