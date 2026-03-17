-- MySQL dump 10.13  Distrib 8.0.45, for Linux (aarch64)
--
-- Host: localhost    Database: foreign
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `be_groups`
--

DROP TABLE IF EXISTS `be_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `be_groups` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `tables_select` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `tx_styleguide_isdemorecord` smallint unsigned NOT NULL DEFAULT '0',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `db_mountpoints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `file_mountpoints` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `file_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `workspace_perms` smallint unsigned NOT NULL DEFAULT '0',
  `pagetypes_select` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `tables_modify` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `non_exclude_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `explicit_allowdeny` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `allowed_languages` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `custom_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `groupMods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `mfa_providers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `TSconfig` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `subgroup` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `category_perms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `availableWidgets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `be_users`
--

DROP TABLE IF EXISTS `be_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `be_users` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `disable` smallint unsigned NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `avatar` int unsigned NOT NULL DEFAULT '0',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `admin` smallint unsigned NOT NULL DEFAULT '0',
  `usergroup` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `db_mountpoints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `options` smallint unsigned NOT NULL DEFAULT '3',
  `realName` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `userMods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `allowed_languages` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uc` mediumblob,
  `file_mountpoints` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `file_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `workspace_perms` smallint unsigned NOT NULL DEFAULT '0',
  `TSconfig` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lastlogin` bigint NOT NULL DEFAULT '0',
  `workspace_id` int NOT NULL DEFAULT '0',
  `mfa` mediumblob,
  `category_perms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `password_reset_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tx_styleguide_isdemorecord` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`disable`),
  KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fe_groups`
--

DROP TABLE IF EXISTS `fe_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fe_groups` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `tx_styleguide_containsdemo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `subgroup` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `felogin_redirectPid` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fe_users`
--

DROP TABLE IF EXISTS `fe_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fe_users` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `disable` smallint unsigned NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `tx_extbase_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `uc` blob DEFAULT (NULL),
  `is_online` int unsigned NOT NULL DEFAULT '0',
  `mfa` mediumblob DEFAULT (NULL),
  `felogin_forgotHash` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `tx_styleguide_containsdemo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `usergroup` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `name` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `middle_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `telephone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fax` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `zip` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `city` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `country` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `www` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `company` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image` int unsigned NOT NULL DEFAULT '0',
  `lastlogin` bigint NOT NULL DEFAULT '0',
  `felogin_redirectPid` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`username`(100)),
  KEY `username` (`username`(100)),
  KEY `is_online` (`is_online`),
  KEY `felogin_forgotHash` (`felogin_forgotHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `fe_group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `rowDescription` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `editlock` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `l10n_diffsource` mediumblob,
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `perms_userid` int unsigned NOT NULL DEFAULT '0',
  `perms_groupid` int unsigned NOT NULL DEFAULT '0',
  `perms_user` smallint unsigned NOT NULL DEFAULT '0',
  `perms_group` smallint unsigned NOT NULL DEFAULT '0',
  `perms_everybody` smallint unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `slug` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `doktype` int unsigned NOT NULL DEFAULT '0',
  `TSconfig` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_siteroot` smallint unsigned NOT NULL DEFAULT '0',
  `php_tree_stop` smallint unsigned NOT NULL DEFAULT '0',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `shortcut` int unsigned NOT NULL DEFAULT '0',
  `shortcut_mode` int unsigned NOT NULL DEFAULT '0',
  `subtitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `layout` int unsigned NOT NULL DEFAULT '0',
  `target` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `media` int unsigned NOT NULL DEFAULT '0',
  `lastUpdated` bigint NOT NULL DEFAULT '0',
  `keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cache_timeout` int unsigned NOT NULL DEFAULT '0',
  `cache_tags` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `newUntil` bigint NOT NULL DEFAULT '0',
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `no_search` smallint unsigned NOT NULL DEFAULT '0',
  `SYS_LASTCHANGED` int unsigned NOT NULL DEFAULT '0',
  `abstract` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `module` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `extendToSubpages` smallint unsigned NOT NULL DEFAULT '0',
  `author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `author_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `nav_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `nav_hide` smallint unsigned NOT NULL DEFAULT '0',
  `content_from_pid` int unsigned NOT NULL DEFAULT '0',
  `mount_pid` int unsigned NOT NULL DEFAULT '0',
  `mount_pid_ol` smallint NOT NULL DEFAULT '0',
  `l18n_cfg` smallint unsigned NOT NULL DEFAULT '0',
  `backend_layout` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `backend_layout_next_level` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tsconfig_includes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `categories` int unsigned NOT NULL DEFAULT '0',
  `tx_impexp_origuid` int NOT NULL DEFAULT '0',
  `seo_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `no_index` smallint unsigned NOT NULL DEFAULT '0',
  `no_follow` smallint unsigned NOT NULL DEFAULT '0',
  `og_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `og_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `og_image` int unsigned NOT NULL DEFAULT '0',
  `twitter_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `twitter_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `twitter_image` int unsigned NOT NULL DEFAULT '0',
  `twitter_card` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `canonical_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (_utf8mb4''),
  `sitemap_priority` decimal(2,1) NOT NULL DEFAULT '0.5',
  `sitemap_changefreq` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tx_styleguide_containsdemo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `determineSiteRoot` (`is_siteroot`),
  KEY `language_identifier` (`l10n_parent`,`sys_language_uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `slug` (`slug`(127)),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB AUTO_INCREMENT=1001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_be_shortcuts`
--

DROP TABLE IF EXISTS `sys_be_shortcuts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_be_shortcuts` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `userid` int unsigned NOT NULL DEFAULT '0',
  `route` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `arguments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sorting` int NOT NULL DEFAULT '0',
  `sc_group` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `event` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_category`
--

DROP TABLE IF EXISTS `sys_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_category` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `items` int NOT NULL DEFAULT '0',
  `images` int unsigned DEFAULT '0',
  `single_pid` int unsigned NOT NULL DEFAULT '0',
  `shortcut` int NOT NULL DEFAULT '0',
  `import_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `import_source` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `seo_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `seo_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `seo_headline` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `seo_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `slug` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `parent` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `category_parent` (`parent`),
  KEY `category_list` (`pid`,`deleted`,`sys_language_uid`),
  KEY `import` (`import_id`,`import_source`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_category_record_mm`
--

DROP TABLE IF EXISTS `sys_category_record_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_category_record_mm` (
  `uid_local` int unsigned NOT NULL DEFAULT '0',
  `uid_foreign` int unsigned NOT NULL DEFAULT '0',
  `sorting` int unsigned NOT NULL DEFAULT '0',
  `sorting_foreign` int unsigned NOT NULL DEFAULT '0',
  `tablenames` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fieldname` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid_local`,`uid_foreign`,`tablenames`,`fieldname`),
  KEY `uid_foreign` (`uid_foreign`),
  KEY `uid_local` (`uid_local`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_csp_resolution`
--

DROP TABLE IF EXISTS `sys_csp_resolution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_csp_resolution` (
  `summary` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` int unsigned NOT NULL,
  `scope` varchar(264) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mutation_identifier` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `mutation_collection` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `meta` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`summary`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_file`
--

DROP TABLE IF EXISTS `sys_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_file` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `last_indexed` int NOT NULL DEFAULT '0',
  `missing` smallint unsigned NOT NULL DEFAULT '0',
  `storage` int unsigned NOT NULL DEFAULT '0',
  `type` int unsigned NOT NULL DEFAULT '0',
  `metadata` int unsigned NOT NULL DEFAULT '0',
  `identifier` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `identifier_hash` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `folder_hash` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `extension` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sha1` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `size` bigint NOT NULL DEFAULT '0',
  `creation_date` int NOT NULL DEFAULT '0',
  `modification_date` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `folder` (`storage`,`folder_hash`),
  KEY `lastindex` (`last_indexed`),
  KEY `parent` (`pid`),
  KEY `sel01` (`storage`,`identifier_hash`),
  KEY `sha1` (`sha1`),
  KEY `tstamp` (`tstamp`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_file_collection`
--

DROP TABLE IF EXISTS `sys_file_collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_file_collection` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `title` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'static',
  `files` int unsigned NOT NULL DEFAULT '0',
  `folder_identifier` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `recursive` smallint unsigned NOT NULL DEFAULT '0',
  `category` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_file_metadata`
--

DROP TABLE IF EXISTS `sys_file_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_file_metadata` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `l10n_diffsource` mediumblob,
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `file` int unsigned NOT NULL DEFAULT '0',
  `title` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `width` int NOT NULL DEFAULT '0',
  `height` int NOT NULL DEFAULT '0',
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `alternative` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `categories` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `fal_filelist` (`l10n_parent`,`sys_language_uid`),
  KEY `file` (`file`),
  KEY `parent` (`pid`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_file_reference`
--

DROP TABLE IF EXISTS `sys_file_reference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_file_reference` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `l10n_diffsource` mediumblob,
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `uid_local` int NOT NULL DEFAULT '0',
  `uid_foreign` int NOT NULL DEFAULT '0',
  `tablenames` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fieldname` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sorting_foreign` int NOT NULL DEFAULT '0',
  `title` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `alternative` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (_utf8mb4''),
  `crop` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `autoplay` smallint unsigned NOT NULL DEFAULT '0',
  `showinpreview` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `combined_1` (`l10n_parent`,`t3ver_oid`,`t3ver_wsid`,`t3ver_state`,`deleted`),
  KEY `deleted` (`deleted`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`),
  KEY `tablenames_fieldname` (`tablenames`(32),`fieldname`(12)),
  KEY `uid_foreign` (`uid_foreign`),
  KEY `uid_local` (`uid_local`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_file_storage`
--

DROP TABLE IF EXISTS `sys_file_storage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_file_storage` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `driver` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_default` smallint unsigned NOT NULL DEFAULT '0',
  `is_browsable` smallint unsigned NOT NULL DEFAULT '1',
  `is_public` smallint NOT NULL DEFAULT '0',
  `is_writable` smallint unsigned NOT NULL DEFAULT '1',
  `is_online` smallint unsigned NOT NULL DEFAULT '1',
  `auto_extract_metadata` smallint unsigned NOT NULL DEFAULT '1',
  `processingfolder` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_filemounts`
--

DROP TABLE IF EXISTS `sys_filemounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_filemounts` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `identifier` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `read_only` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_news`
--

DROP TABLE IF EXISTS `sys_news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_news` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_note`
--

DROP TABLE IF EXISTS `sys_note`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_note` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `cruser` int unsigned NOT NULL DEFAULT '0',
  `category` int unsigned NOT NULL DEFAULT '0',
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `personal` smallint unsigned NOT NULL DEFAULT '0',
  `position` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_preview`
--

DROP TABLE IF EXISTS `sys_preview`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_preview` (
  `keyword` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tstamp` int NOT NULL DEFAULT '0',
  `endtime` int NOT NULL DEFAULT '0',
  `config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_redirect`
--

DROP TABLE IF EXISTS `sys_redirect`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_redirect` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `updatedon` int unsigned NOT NULL DEFAULT '0',
  `createdon` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `disabled` smallint unsigned NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `source_host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `source_path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (_utf8mb4''),
  `is_regexp` smallint unsigned NOT NULL DEFAULT '0',
  `protected` smallint unsigned NOT NULL DEFAULT '0',
  `force_https` smallint unsigned NOT NULL DEFAULT '0',
  `respect_query_parameters` smallint unsigned NOT NULL DEFAULT '0',
  `keep_query_parameters` smallint unsigned NOT NULL DEFAULT '0',
  `target` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (_utf8mb4''),
  `target_statuscode` int unsigned NOT NULL DEFAULT '0',
  `hitcount` int NOT NULL DEFAULT '0',
  `lasthiton` bigint NOT NULL DEFAULT '0',
  `disable_hitcount` smallint unsigned NOT NULL DEFAULT '0',
  `tx_in2publishcore_page_uid` int unsigned DEFAULT NULL,
  `tx_in2publishcore_foreign_site_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `creation_type` int unsigned NOT NULL DEFAULT '0',
  `integrity_status` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `index_source` (`source_host`(80),`source_path`(80)),
  KEY `parent` (`pid`,`deleted`,`disabled`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_registry`
--

DROP TABLE IF EXISTS `sys_registry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_registry` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `entry_namespace` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `entry_key` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `entry_value` mediumblob,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `entry_identifier` (`entry_namespace`,`entry_key`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_template`
--

DROP TABLE IF EXISTS `sys_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_template` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `root` smallint unsigned NOT NULL DEFAULT '0',
  `clear` smallint unsigned NOT NULL DEFAULT '0',
  `include_static_file` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `constants` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `basedOn` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `includeStaticAfterBasedOn` smallint unsigned NOT NULL DEFAULT '0',
  `static_file_mode` int unsigned NOT NULL DEFAULT '0',
  `tx_impexp_origuid` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `roottemplate` (`deleted`,`hidden`,`root`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_workspace`
--

DROP TABLE IF EXISTS `sys_workspace`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_workspace` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `title` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `adminusers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `members` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `db_mountpoints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `file_mountpoints` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `publish_time` bigint NOT NULL DEFAULT '0',
  `freeze` smallint unsigned NOT NULL DEFAULT '0',
  `live_edit` smallint unsigned NOT NULL DEFAULT '0',
  `publish_access` smallint unsigned NOT NULL DEFAULT '0',
  `previewlink_lifetime` int NOT NULL DEFAULT '0',
  `stagechg_notification` smallint unsigned NOT NULL DEFAULT '1',
  `custom_stages` int unsigned NOT NULL DEFAULT '0',
  `edit_notification_defaults` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `edit_allow_notificaton_settings` smallint unsigned NOT NULL DEFAULT '3',
  `edit_notification_preselection` smallint unsigned NOT NULL DEFAULT '2',
  `publish_notification_defaults` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `publish_allow_notificaton_settings` smallint unsigned NOT NULL DEFAULT '3',
  `publish_notification_preselection` smallint unsigned NOT NULL DEFAULT '1',
  `execute_notification_defaults` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `execute_allow_notificaton_settings` smallint unsigned NOT NULL DEFAULT '3',
  `execute_notification_preselection` smallint unsigned NOT NULL DEFAULT '3',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_workspace_stage`
--

DROP TABLE IF EXISTS `sys_workspace_stage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_workspace_stage` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `title` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `responsible_persons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `default_mailcomment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `notification_defaults` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `allow_notificaton_settings` smallint unsigned NOT NULL DEFAULT '3',
  `notification_preselection` smallint unsigned NOT NULL DEFAULT '8',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tt_content`
--

DROP TABLE IF EXISTS `tt_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tt_content` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `rowDescription` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `fe_group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `editlock` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l18n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `l18n_diffsource` mediumblob,
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `CType` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `header` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `header_position` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bodytext` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bullets_type` int unsigned NOT NULL DEFAULT '0',
  `uploads_description` smallint unsigned NOT NULL DEFAULT '0',
  `uploads_type` int unsigned NOT NULL DEFAULT '0',
  `assets` int unsigned NOT NULL DEFAULT '0',
  `image` int unsigned NOT NULL DEFAULT '0',
  `imagewidth` int unsigned NOT NULL DEFAULT '0',
  `imageorient` int unsigned NOT NULL DEFAULT '0',
  `imagecols` int unsigned NOT NULL DEFAULT '0',
  `imageborder` smallint unsigned NOT NULL DEFAULT '0',
  `media` int unsigned NOT NULL DEFAULT '0',
  `layout` int unsigned NOT NULL DEFAULT '0',
  `frame_class` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `cols` int unsigned NOT NULL DEFAULT '0',
  `space_before_class` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `space_after_class` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `records` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `colPos` int unsigned NOT NULL DEFAULT '0',
  `subheader` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `header_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (_utf8mb4''),
  `image_zoom` smallint unsigned NOT NULL DEFAULT '0',
  `header_layout` int unsigned NOT NULL DEFAULT '0',
  `list_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sectionIndex` smallint unsigned NOT NULL DEFAULT '1',
  `linkToTop` smallint unsigned NOT NULL DEFAULT '0',
  `file_collections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `filelink_size` smallint unsigned NOT NULL DEFAULT '0',
  `filelink_sorting` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filelink_sorting_direction` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `target` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `date` bigint NOT NULL DEFAULT '0',
  `recursive` int unsigned NOT NULL DEFAULT '0',
  `imageheight` int unsigned NOT NULL DEFAULT '0',
  `pi_flexform` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `category_field` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `table_class` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `table_caption` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `table_delimiter` int unsigned NOT NULL DEFAULT '0',
  `table_enclosure` int unsigned NOT NULL DEFAULT '0',
  `table_header_position` int unsigned NOT NULL DEFAULT '0',
  `table_tfoot` smallint unsigned NOT NULL DEFAULT '0',
  `categories` int unsigned NOT NULL DEFAULT '0',
  `selected_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tx_impexp_origuid` int NOT NULL DEFAULT '0',
  `tx_news_related_news` int NOT NULL DEFAULT '0',
  `tx_styleguide_containsdemo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `index_newscontent` (`tx_news_related_news`),
  KEY `language` (`l18n_parent`,`sys_language_uid`),
  KEY `parent` (`pid`,`sorting`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_extensionmanager_domain_model_extension`
--

DROP TABLE IF EXISTS `tx_extensionmanager_domain_model_extension`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_extensionmanager_domain_model_extension` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `extension_key` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ter',
  `version` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alldownloadcounter` int unsigned NOT NULL DEFAULT '0',
  `downloadcounter` int unsigned NOT NULL DEFAULT '0',
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `serialized_dependencies` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `author_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `author_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ownerusername` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `md5hash` varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `authorcompany` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `integer_version` int NOT NULL DEFAULT '0',
  `lastreviewedversion` int NOT NULL DEFAULT '0',
  `documentation_link` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `distribution_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `distribution_welcome_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `state` int unsigned NOT NULL DEFAULT '0',
  `category` int unsigned NOT NULL DEFAULT '0',
  `last_updated` bigint NOT NULL DEFAULT '0',
  `update_comment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `current_version` smallint unsigned NOT NULL DEFAULT '0',
  `review_state` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `versionextrepo` (`extension_key`,`version`,`remote`),
  KEY `index_extrepo` (`extension_key`,`remote`),
  KEY `index_versionrepo` (`integer_version`,`remote`,`extension_key`),
  KEY `index_currentversions` (`current_version`,`review_state`),
  KEY `parent` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_impexp_presets`
--

DROP TABLE IF EXISTS `tx_impexp_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_impexp_presets` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `public` smallint NOT NULL DEFAULT '0',
  `item_uid` int NOT NULL DEFAULT '0',
  `user_uid` int unsigned NOT NULL DEFAULT '0',
  `preset_data` blob DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `lookup` (`item_uid`),
  KEY `parent` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_in2publish_workflow_history`
--

DROP TABLE IF EXISTS `tx_in2publish_workflow_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_in2publish_workflow_history` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `record_table` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `record_identifier` int unsigned NOT NULL DEFAULT '0',
  `record_pid` int unsigned NOT NULL DEFAULT '0',
  `record_language` int DEFAULT NULL,
  `record_language_parent` int unsigned DEFAULT NULL,
  `record_page_uid` int unsigned DEFAULT NULL,
  `backend_user` int unsigned NOT NULL DEFAULT '0',
  `creation_date` int unsigned NOT NULL DEFAULT '0',
  `state_identifier` int unsigned NOT NULL DEFAULT '0',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `scheduled_publish` int DEFAULT NULL,
  `obsolete` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `record` (`record_table`,`record_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_in2publish_workflow_state`
--

DROP TABLE IF EXISTS `tx_in2publish_workflow_state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_in2publish_workflow_state` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `record_table` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `record_identifier` int unsigned NOT NULL DEFAULT '0',
  `record_pid` int unsigned NOT NULL DEFAULT '0',
  `record_language` int DEFAULT NULL,
  `record_language_parent` int unsigned DEFAULT NULL,
  `record_page_uid` int unsigned DEFAULT NULL,
  `backend_user` int unsigned NOT NULL DEFAULT '0',
  `creation_date` int unsigned NOT NULL DEFAULT '0',
  `state_identifier` int unsigned NOT NULL DEFAULT '0',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `scheduled_publish` int DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `find_by_record_or_table` (`record_table`,`record_identifier`),
  KEY `find_scheduled` (`state_identifier`,`scheduled_publish`),
  KEY `custom` (`record_pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_linkvalidator_link`
--

DROP TABLE IF EXISTS `tx_linkvalidator_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_linkvalidator_link` (
  `uid` int NOT NULL AUTO_INCREMENT,
  `record_uid` int NOT NULL DEFAULT '0',
  `record_pid` int NOT NULL DEFAULT '0',
  `language` int NOT NULL DEFAULT '-1',
  `headline` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `field` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `table_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `element_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `link_title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `url_response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `last_check` int NOT NULL DEFAULT '0',
  `link_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `needs_recheck` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_news_domain_model_link`
--

DROP TABLE IF EXISTS `tx_news_domain_model_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_news_domain_model_link` (
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `t3_origuid` int unsigned NOT NULL DEFAULT '0',
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `parent` int NOT NULL DEFAULT '0',
  `title` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `uri` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_news_domain_model_news`
--

DROP TABLE IF EXISTS `tx_news_domain_model_news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_news_domain_model_news` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `t3_origuid` int unsigned NOT NULL DEFAULT '0',
  `editlock` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_diffsource` mediumblob,
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `fe_group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sorting` int NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `teaser` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bodytext` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `datetime` bigint NOT NULL DEFAULT '0',
  `archive` bigint NOT NULL DEFAULT '0',
  `author` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `author_email` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `categories` int NOT NULL DEFAULT '0',
  `related` int NOT NULL DEFAULT '0',
  `related_from` int NOT NULL DEFAULT '0',
  `related_files` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fal_related_files` int unsigned DEFAULT '0',
  `related_links` int NOT NULL DEFAULT '0',
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tags` int NOT NULL DEFAULT '0',
  `media` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fal_media` int unsigned DEFAULT '0',
  `internalurl` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `externalurl` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `istopnews` int NOT NULL DEFAULT '0',
  `content_elements` int NOT NULL DEFAULT '0',
  `path_segment` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alternative_title` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sitemap_changefreq` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sitemap_priority` decimal(2,1) NOT NULL DEFAULT '0.5',
  `import_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `import_source` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `import` (`import_id`,`import_source`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `path_segment` (`path_segment`(185),`uid`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_news_domain_model_news_related_mm`
--

DROP TABLE IF EXISTS `tx_news_domain_model_news_related_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_news_domain_model_news_related_mm` (
  `uid_local` int NOT NULL DEFAULT '0',
  `uid_foreign` int NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sorting_foreign` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid_local`,`uid_foreign`),
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_news_domain_model_news_tag_mm`
--

DROP TABLE IF EXISTS `tx_news_domain_model_news_tag_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_news_domain_model_news_tag_mm` (
  `uid_local` int NOT NULL DEFAULT '0',
  `uid_foreign` int NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sorting_foreign` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid_local`,`uid_foreign`),
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_news_domain_model_news_ttcontent_mm`
--

DROP TABLE IF EXISTS `tx_news_domain_model_news_ttcontent_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_news_domain_model_news_ttcontent_mm` (
  `uid_local` int NOT NULL DEFAULT '0',
  `uid_foreign` int NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_news_domain_model_tag`
--

DROP TABLE IF EXISTS `tx_news_domain_model_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_news_domain_model_tag` (
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `title` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `slug` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seo_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `seo_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `seo_headline` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `seo_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_scheduler_task`
--

DROP TABLE IF EXISTS `tx_scheduler_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_scheduler_task` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `disable` smallint unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `nextexecution` int unsigned NOT NULL DEFAULT '0',
  `lastexecution_time` int unsigned NOT NULL DEFAULT '0',
  `lastexecution_failure` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `lastexecution_context` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `serialized_task_object` mediumblob DEFAULT (NULL),
  `serialized_executions` mediumblob DEFAULT (NULL),
  `task_group` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `index_nextexecution` (`nextexecution`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_scheduler_task_group`
--

DROP TABLE IF EXISTS `tx_scheduler_task_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_scheduler_task_group` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `groupName` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_ctrl_common`
--

DROP TABLE IF EXISTS `tx_styleguide_ctrl_common`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_ctrl_common` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_ctrl_minimal`
--

DROP TABLE IF EXISTS `tx_styleguide_ctrl_minimal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_ctrl_minimal` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_displaycond`
--

DROP TABLE IF EXISTS `tx_styleguide_displaycond`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_displaycond` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `select_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `number_1` int NOT NULL DEFAULT '0',
  `input_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_6` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_7` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_8` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_9` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_2` int unsigned NOT NULL DEFAULT '0',
  `checkbox_1` smallint unsigned NOT NULL DEFAULT '0',
  `input_19` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_20` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_10` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_11` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_13` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `number_2` int NOT NULL DEFAULT '0',
  `number_3` int NOT NULL DEFAULT '0',
  `input_16` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_17` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_18` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_3` int unsigned NOT NULL DEFAULT '0',
  `flex_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `flex_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_4` int unsigned NOT NULL DEFAULT '0',
  `flex_3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_element_group_group_13_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_element_group_group_13_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_element_group_group_13_mm` (
  `uid_local` int unsigned NOT NULL DEFAULT '0',
  `uid_foreign` int unsigned NOT NULL DEFAULT '0',
  `sorting` int unsigned NOT NULL DEFAULT '0',
  `sorting_foreign` int unsigned NOT NULL DEFAULT '0',
  `tablenames` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fieldname` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid_local`,`uid_foreign`,`tablenames`,`fieldname`),
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_basic`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_basic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_basic` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `none_1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `none_2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `none_3` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `none_4` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `passthrough_1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `passthrough_2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `user_1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `user_2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_3` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_10` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_11` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_12` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_13` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_14` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `input_15` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_19` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_21` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_22` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_23` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_24` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_26` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_27` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_28` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `input_33` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_35` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_36` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_40` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_41` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_42` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_43` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `inputdatetime_1` bigint NOT NULL DEFAULT '0',
  `inputdatetime_2` date DEFAULT NULL,
  `inputdatetime_3` bigint NOT NULL DEFAULT '0',
  `inputdatetime_4` datetime DEFAULT NULL,
  `inputdatetime_5` bigint NOT NULL DEFAULT '0',
  `inputdatetime_6` bigint NOT NULL DEFAULT '0',
  `inputdatetime_7` bigint NOT NULL DEFAULT '0',
  `inputdatetime_8` bigint NOT NULL DEFAULT '0',
  `inputdatetime_9` bigint NOT NULL DEFAULT '0',
  `inputdatetime_10` bigint NOT NULL DEFAULT '0',
  `inputdatetime_11` bigint NOT NULL DEFAULT '0',
  `inputdatetime_34` bigint NOT NULL DEFAULT '0',
  `inputdatetime_12` time DEFAULT NULL,
  `inputdatetime_13` time DEFAULT NULL,
  `inputdatetime_21` bigint DEFAULT NULL,
  `inputdatetime_22` date DEFAULT NULL,
  `inputdatetime_23` bigint DEFAULT NULL,
  `inputdatetime_24` datetime DEFAULT NULL,
  `inputdatetime_25` bigint DEFAULT NULL,
  `inputdatetime_26` bigint DEFAULT NULL,
  `inputdatetime_27` bigint DEFAULT NULL,
  `inputdatetime_28` bigint DEFAULT NULL,
  `inputdatetime_29` bigint DEFAULT NULL,
  `inputdatetime_30` bigint DEFAULT NULL,
  `inputdatetime_31` bigint DEFAULT NULL,
  `inputdatetime_32` time DEFAULT NULL,
  `inputdatetime_33` time DEFAULT NULL,
  `link_1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (_utf8mb4''),
  `link_2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (_utf8mb4''),
  `link_3` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (_utf8mb4''),
  `link_4` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (_utf8mb4''),
  `link_5` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (_utf8mb4''),
  `password_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password_5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password_6` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password_7` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password_8` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_1` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `color_2` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `color_3` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `color_4` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_5` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `color_palpreset` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `number_1` decimal(10,2) NOT NULL DEFAULT '0.00',
  `number_2` int NOT NULL DEFAULT '0',
  `number_3` int NOT NULL DEFAULT '0',
  `number_4` int NOT NULL DEFAULT '0',
  `number_5` decimal(10,2) NOT NULL DEFAULT '0.00',
  `number_7` int NOT NULL DEFAULT '0',
  `email_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_4` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_5` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_6` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_7` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_9` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_10` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_11` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_12` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text_13` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_14` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_15` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_16` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_17` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_18` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_19` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_20` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `uuid_1` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uuid_2` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uuid_3` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `checkbox_1` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_2` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_3` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_4` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_6` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_7` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_8` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_9` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_10` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_11` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_12` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_13` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_14` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_15` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_16` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_17` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_18` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_19` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_20` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_21` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_24` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_25` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_26` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_27` smallint unsigned NOT NULL DEFAULT '0',
  `radio_1` smallint NOT NULL DEFAULT '0',
  `radio_2` smallint NOT NULL DEFAULT '0',
  `radio_3` smallint NOT NULL DEFAULT '0',
  `radio_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `radio_5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `radio_6` smallint NOT NULL DEFAULT '0',
  `unknown_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `flex_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `language_1` int NOT NULL DEFAULT '0',
  `none_5` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `inputdatetime_35` bigint DEFAULT NULL,
  `json_1` json DEFAULT (NULL),
  `json_2` json DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_folder`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_folder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_folder` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `folder_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `folder_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `folder_3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `flex_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_group`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_group` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `group_db_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_db_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_db_9` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_db_3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_db_8` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_db_11` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_db_4` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_db_5` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_db_7` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_db_10` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_db_12` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_db_13` int unsigned NOT NULL DEFAULT '0',
  `group_requestUpdate_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `flex_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_imagemanipulation`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_imagemanipulation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_imagemanipulation` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `group_db_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_db_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_db_3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `crop_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `crop_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `crop_4` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `crop_3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `crop_5` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `crop_6` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `crop_7` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `crop_8` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `crop_9` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `crop_10` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `file_1` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_rte`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_rte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_rte` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `rte_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_4` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_5` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_inline_1` int unsigned NOT NULL DEFAULT '0',
  `rte_flex_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `input_palette_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rte_palette_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_6` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_7` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_8` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_9` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_10` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_11` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_12` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_13` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_14` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_15` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_rte_flex_1_inline_1_child`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_rte_flex_1_inline_1_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_rte_flex_1_inline_1_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int NOT NULL DEFAULT '0',
  `parenttable` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_rte_inline_1_child`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_rte_inline_1_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_rte_inline_1_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rte_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_select`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_select`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_select` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `select_single_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_single_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_single_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_single_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_single_5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_single_7` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_single_8` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_single_10` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_single_11` int unsigned NOT NULL DEFAULT '0',
  `select_single_12` int unsigned NOT NULL DEFAULT '0',
  `select_single_13` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_single_14` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_single_15` int unsigned NOT NULL DEFAULT '0',
  `select_single_16` int unsigned NOT NULL DEFAULT '0',
  `select_single_17` int unsigned NOT NULL DEFAULT '0',
  `select_single_18` int unsigned NOT NULL DEFAULT '0',
  `select_single_19` int unsigned NOT NULL DEFAULT '0',
  `select_single_20` int unsigned NOT NULL DEFAULT '0',
  `select_single_21` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_singlebox_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_singlebox_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_singlebox_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_checkbox_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_checkbox_2` int unsigned NOT NULL DEFAULT '0',
  `select_checkbox_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_checkbox_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_checkbox_5` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_checkbox_6` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_checkbox_7` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_multiplesidebyside_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_multiplesidebyside_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_multiplesidebyside_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_multiplesidebyside_5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_multiplesidebyside_6` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_multiplesidebyside_7` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_multiplesidebyside_8` int unsigned NOT NULL DEFAULT '0',
  `select_multiplesidebyside_9` int unsigned NOT NULL DEFAULT '0',
  `select_multiplesidebyside_10` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_tree_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_tree_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_tree_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_tree_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_tree_5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_tree_6` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `category_11` int unsigned NOT NULL DEFAULT '0',
  `category_1n` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `category_mm` int unsigned NOT NULL DEFAULT '0',
  `select_requestUpdate_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `flex_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_single_22` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_single_23` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_select_flex_1_multiplesidebyside_2_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_select_flex_1_multiplesidebyside_2_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_select_flex_1_multiplesidebyside_2_mm` (
  `uid_local` int unsigned NOT NULL DEFAULT '0',
  `uid_foreign` int unsigned NOT NULL DEFAULT '0',
  `sorting` int unsigned NOT NULL DEFAULT '0',
  `sorting_foreign` int unsigned NOT NULL DEFAULT '0',
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_select_multiplesidebyside_8_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_select_multiplesidebyside_8_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_select_multiplesidebyside_8_mm` (
  `uid_local` int unsigned NOT NULL DEFAULT '0',
  `uid_foreign` int unsigned NOT NULL DEFAULT '0',
  `sorting` int unsigned NOT NULL DEFAULT '0',
  `sorting_foreign` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid_local`,`uid_foreign`),
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_select_single_12_foreign`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_select_single_12_foreign`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_select_single_12_foreign` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `fal_1` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_select_single_15_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_select_single_15_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_select_single_15_mm` (
  `uid_local` int unsigned NOT NULL DEFAULT '0',
  `uid_foreign` int unsigned NOT NULL DEFAULT '0',
  `sorting` int unsigned NOT NULL DEFAULT '0',
  `sorting_foreign` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid_local`,`uid_foreign`),
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_select_single_21_foreign`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_select_single_21_foreign`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_select_single_21_foreign` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `item_group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_slugs`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_slugs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_slugs` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `slug_1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `slug_2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `slug_4` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `slug_5` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `input_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `slug_3` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_t3editor`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_t3editor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_t3editor` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `t3editor_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `t3editor_reload_1` int unsigned NOT NULL DEFAULT '0',
  `t3editor_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `t3editor_inline_1` int unsigned NOT NULL DEFAULT '0',
  `t3editor_flex_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_t3editor_flex_1_inline_1_child`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_t3editor_flex_1_inline_1_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_t3editor_flex_1_inline_1_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int NOT NULL DEFAULT '0',
  `parenttable` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `t3editor_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_elements_t3editor_inline_1_child`
--

DROP TABLE IF EXISTS `tx_styleguide_elements_t3editor_inline_1_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_elements_t3editor_inline_1_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `t3editor_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_file`
--

DROP TABLE IF EXISTS `tx_styleguide_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_file` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `file_1` int unsigned NOT NULL DEFAULT '0',
  `file_2` int unsigned NOT NULL DEFAULT '0',
  `file_3` int unsigned NOT NULL DEFAULT '0',
  `file_4` int unsigned NOT NULL DEFAULT '0',
  `file_5` int unsigned NOT NULL DEFAULT '0',
  `file_6` int unsigned NOT NULL DEFAULT '0',
  `file_flex_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_flex`
--

DROP TABLE IF EXISTS `tx_styleguide_flex`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_flex` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `flex_file_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `flex_file_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `flex_5` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `flex_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `flex_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `flex_3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `flex_6` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_flex_flex_3_inline_1_child`
--

DROP TABLE IF EXISTS `tx_styleguide_flex_flex_3_inline_1_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_flex_flex_3_inline_1_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int NOT NULL DEFAULT '0',
  `parenttable` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `file_1` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_11`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_11`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_11` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `inline_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_11_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_11_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_11_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_1n`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_1n`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_1n` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  `inline_2` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_1n1n`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_1n1n`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_1n1n` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_1n1n_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_1n1n_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_1n1n_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  `inline_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `inline_3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_1n1n_childchild`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_1n1n_childchild`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_1n1n_childchild` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_1n_inline_1_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_1n_inline_1_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_1n_inline_1_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `disable` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `color_1` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `group_db_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_tree_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`disable`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_1n_inline_2_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_1n_inline_2_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_1n_inline_2_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `file_1` int unsigned NOT NULL DEFAULT '0',
  `rte_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_tree_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `t3editor_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_1nnol10n`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_1nnol10n`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_1nnol10n` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_1nnol10n_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_1nnol10n_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_1nnol10n_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `disable` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`disable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_1nreusabletable`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_1nreusabletable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_1nreusabletable` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  `inline_2` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_1nreusabletable_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_1nreusabletable_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_1nreusabletable_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `disable` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `role` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`disable`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_expand`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_expand`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_expand` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_expand_inline_1_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_expand_inline_1_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_expand_inline_1_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dummy_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `file_1` int unsigned NOT NULL DEFAULT '0',
  `rte_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_tree_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `t3editor_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_expandsingle`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_expandsingle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_expandsingle` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  `inline_2` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_expandsingle_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_expandsingle_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_expandsingle_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_expandsingle_inline_2_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_expandsingle_inline_2_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_expandsingle_inline_2_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `inputdatetime_1` bigint NOT NULL DEFAULT '0',
  `inputdatetime_2` date DEFAULT NULL,
  `inputdatetime_3` datetime DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_foreignrecorddefaults`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_foreignrecorddefaults`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_foreignrecorddefaults` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_foreignrecorddefaults_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_foreignrecorddefaults_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_foreignrecorddefaults_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mm` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mm_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mm_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mm_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `parents` int unsigned NOT NULL DEFAULT '0',
  `inline_2` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mm_child_childchild_rel`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mm_child_childchild_rel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mm_child_childchild_rel` (
  `uid_local` int unsigned NOT NULL DEFAULT '0',
  `uid_foreign` int unsigned NOT NULL DEFAULT '0',
  `sorting` int unsigned NOT NULL DEFAULT '0',
  `sorting_foreign` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid_local`,`uid_foreign`),
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mm_child_rel`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mm_child_rel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mm_child_rel` (
  `uid_local` int unsigned NOT NULL DEFAULT '0',
  `uid_foreign` int unsigned NOT NULL DEFAULT '0',
  `sorting` int unsigned NOT NULL DEFAULT '0',
  `sorting_foreign` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid_local`,`uid_foreign`),
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mm_childchild`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mm_childchild`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mm_childchild` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `parents` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mn`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mn` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mn_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mn_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mn_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `parents` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mn_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mn_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mn_mm` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `parentsort` int NOT NULL DEFAULT '0',
  `childsort` int NOT NULL DEFAULT '0',
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `childid` int unsigned NOT NULL DEFAULT '0',
  `check_1` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mngroup`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mngroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mngroup` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mngroup_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mngroup_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mngroup_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `parents` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mngroup_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mngroup_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mngroup_mm` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `parentsort` int NOT NULL DEFAULT '0',
  `childsort` int NOT NULL DEFAULT '0',
  `parentid` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `childid` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `check_1` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mnsymmetric`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mnsymmetric`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mnsymmetric` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `branches` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mnsymmetric_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mnsymmetric_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mnsymmetric_mm` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `hotelsort` int NOT NULL DEFAULT '0',
  `branchsort` int NOT NULL DEFAULT '0',
  `hotelid` int unsigned NOT NULL DEFAULT '0',
  `branchid` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mnsymmetricgroup`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mnsymmetricgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mnsymmetricgroup` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `branches` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_mnsymmetricgroup_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_mnsymmetricgroup_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_mnsymmetricgroup_mm` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `hotelid` int NOT NULL DEFAULT '0',
  `branchid` int NOT NULL DEFAULT '0',
  `hotelsort` int NOT NULL DEFAULT '0',
  `branchsort` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_overridechildtca`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_overridechildtca`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_overridechildtca` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  `inline_2` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_overridechildtca_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_overridechildtca_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_overridechildtca_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `input_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_parentnosoftdelete`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_parentnosoftdelete`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_parentnosoftdelete` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `text_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `file_1` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_usecombination`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_usecombination`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_usecombination` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  `inline_2` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_usecombination_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_usecombination_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_usecombination_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_usecombination_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_usecombination_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_usecombination_mm` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `select_parent` int unsigned NOT NULL DEFAULT '0',
  `select_child` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_usecombinationbox`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_usecombinationbox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_usecombinationbox` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_usecombinationbox_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_usecombinationbox_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_usecombinationbox_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_usecombinationbox_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_usecombinationbox_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_usecombinationbox_mm` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `select_parent` int unsigned NOT NULL DEFAULT '0',
  `select_child` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_usecombinationgroup`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_usecombinationgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_usecombinationgroup` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `t3_origuid` int unsigned NOT NULL DEFAULT '0',
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_usecombinationgroup_child`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_usecombinationgroup_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_usecombinationgroup_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_inline_usecombinationgroup_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_inline_usecombinationgroup_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_inline_usecombinationgroup_mm` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `group_parent` int unsigned NOT NULL DEFAULT '0',
  `group_child` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_l10nreadonly`
--

DROP TABLE IF EXISTS `tx_styleguide_l10nreadonly`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_l10nreadonly` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `none` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `input` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `color` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `datetime` bigint NOT NULL DEFAULT '0',
  `link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (_utf8mb4''),
  `slug` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `checkbox` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_toggle` smallint unsigned NOT NULL DEFAULT '0',
  `checkbox_labeled_toggle` smallint unsigned NOT NULL DEFAULT '0',
  `radio` smallint NOT NULL DEFAULT '0',
  `group` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_mm` int unsigned NOT NULL DEFAULT '0',
  `group_file` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `folder` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `image_manipulation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `language` int NOT NULL DEFAULT '0',
  `category_11` int unsigned NOT NULL DEFAULT '0',
  `category_1n` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `category_mm` int unsigned NOT NULL DEFAULT '0',
  `text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_rte` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_belayoutwizard` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_t3editor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `text_table` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_single` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_single_box` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_checkbox` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_tree` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_tree_mm` int unsigned NOT NULL DEFAULT '0',
  `select_multiplesidebyside` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_multiplesidebyside_mm` int unsigned NOT NULL DEFAULT '0',
  `inline` int unsigned NOT NULL DEFAULT '0',
  `flex` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_l10nreadonly_group_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_l10nreadonly_group_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_l10nreadonly_group_mm` (
  `uid_local` int unsigned NOT NULL DEFAULT '0',
  `uid_foreign` int unsigned NOT NULL DEFAULT '0',
  `sorting` int unsigned NOT NULL DEFAULT '0',
  `sorting_foreign` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid_local`,`uid_foreign`),
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_l10nreadonly_inline_child`
--

DROP TABLE IF EXISTS `tx_styleguide_l10nreadonly_inline_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_l10nreadonly_inline_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_l10nreadonly_select_multiplesidebyside_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_l10nreadonly_select_multiplesidebyside_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_l10nreadonly_select_multiplesidebyside_mm` (
  `uid_local` int unsigned NOT NULL DEFAULT '0',
  `uid_foreign` int unsigned NOT NULL DEFAULT '0',
  `sorting` int unsigned NOT NULL DEFAULT '0',
  `sorting_foreign` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid_local`,`uid_foreign`),
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_l10nreadonly_select_tree_mm`
--

DROP TABLE IF EXISTS `tx_styleguide_l10nreadonly_select_tree_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_l10nreadonly_select_tree_mm` (
  `uid_local` int unsigned NOT NULL DEFAULT '0',
  `uid_foreign` int unsigned NOT NULL DEFAULT '0',
  `sorting` int unsigned NOT NULL DEFAULT '0',
  `sorting_foreign` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid_local`,`uid_foreign`),
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_palette`
--

DROP TABLE IF EXISTS `tx_styleguide_palette`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_palette` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `palette_1_1` smallint unsigned NOT NULL DEFAULT '1',
  `palette_1_3` smallint unsigned NOT NULL DEFAULT '1',
  `palette_2_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `palette_3_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `palette_3_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `palette_4_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `palette_4_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `palette_4_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `palette_4_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `palette_5_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `palette_5_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `palette_6_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `palette_7_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_required`
--

DROP TABLE IF EXISTS `tx_styleguide_required`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_required` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `notrequired_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_1` varchar(23) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_2` bigint NOT NULL DEFAULT '0',
  `input_3` bigint NOT NULL DEFAULT '0',
  `color_1` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `link_1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (_utf8mb4''),
  `text_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_2` int unsigned NOT NULL DEFAULT '0',
  `select_3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `select_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `select_5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `group_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `group_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `rte_2` int unsigned NOT NULL DEFAULT '0',
  `inline_1` int unsigned NOT NULL DEFAULT '0',
  `inline_2` int unsigned NOT NULL DEFAULT '0',
  `inline_3` int unsigned NOT NULL DEFAULT '0',
  `file_1` int unsigned NOT NULL DEFAULT '0',
  `file_2` int unsigned NOT NULL DEFAULT '0',
  `file_3` int unsigned NOT NULL DEFAULT '0',
  `inline_file_1` int unsigned NOT NULL DEFAULT '0',
  `flex_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `flex_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `palette_input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `palette_input_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_required_file_1_child`
--

DROP TABLE IF EXISTS `tx_styleguide_required_file_1_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_required_file_1_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `file_1` int unsigned NOT NULL DEFAULT '0',
  `file_2` int unsigned NOT NULL DEFAULT '0',
  `file_3` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_required_flex_2_inline_1_child`
--

DROP TABLE IF EXISTS `tx_styleguide_required_flex_2_inline_1_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_required_flex_2_inline_1_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int NOT NULL DEFAULT '0',
  `parenttable` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_required_inline_1_child`
--

DROP TABLE IF EXISTS `tx_styleguide_required_inline_1_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_required_inline_1_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_required_inline_2_child`
--

DROP TABLE IF EXISTS `tx_styleguide_required_inline_2_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_required_inline_2_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_required_inline_3_child`
--

DROP TABLE IF EXISTS `tx_styleguide_required_inline_3_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_required_inline_3_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_required_rte_2_child`
--

DROP TABLE IF EXISTS `tx_styleguide_required_rte_2_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_required_rte_2_child` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `parenttable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rte_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_staticdata`
--

DROP TABLE IF EXISTS `tx_styleguide_staticdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_staticdata` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `value_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_type`
--

DROP TABLE IF EXISTS `tx_styleguide_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_type` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `record_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `color_1` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_typeforeign`
--

DROP TABLE IF EXISTS `tx_styleguide_typeforeign`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_typeforeign` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `foreign_table` int unsigned NOT NULL DEFAULT '0',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `color_1` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tx_styleguide_valuesdefault`
--

DROP TABLE IF EXISTS `tx_styleguide_valuesdefault`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_styleguide_valuesdefault` (
  `uid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tstamp` int unsigned NOT NULL DEFAULT '0',
  `crdate` int unsigned NOT NULL DEFAULT '0',
  `deleted` smallint unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  `sorting` int NOT NULL DEFAULT '0',
  `sys_language_uid` int NOT NULL DEFAULT '0',
  `l10n_parent` int unsigned NOT NULL DEFAULT '0',
  `l10n_source` int unsigned NOT NULL DEFAULT '0',
  `l10n_state` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `l10n_diffsource` mediumblob DEFAULT (NULL),
  `t3ver_oid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_wsid` int unsigned NOT NULL DEFAULT '0',
  `t3ver_state` smallint NOT NULL DEFAULT '0',
  `t3ver_stage` int NOT NULL DEFAULT '0',
  `input_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_2` bigint NOT NULL DEFAULT '0',
  `number_1` int unsigned NOT NULL DEFAULT '0',
  `text_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT (NULL),
  `checkbox_1` smallint unsigned NOT NULL DEFAULT '1',
  `checkbox_2` smallint unsigned NOT NULL DEFAULT '1',
  `checkbox_3` smallint unsigned NOT NULL DEFAULT '5',
  `checkbox_4` smallint unsigned NOT NULL DEFAULT '5',
  `radio_1` smallint NOT NULL DEFAULT '0',
  `radio_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `radio_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `select_1` int unsigned NOT NULL DEFAULT '0',
  `select_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-16  8:06:52
-- MySQL dump 10.13  Distrib 8.0.45, for Linux (aarch64)
--
-- Host: localhost    Database: foreign
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `be_groups`
--

LOCK TABLES `be_groups` WRITE;
/*!40000 ALTER TABLE `be_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `be_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `be_users`
--

LOCK TABLES `be_users` WRITE;
/*!40000 ALTER TABLE `be_users` DISABLE KEYS */;
INSERT INTO `be_users` VALUES (1,0,1690960885,1690960885,0,0,0,0,NULL,'admin',0,'$argon2i$v=19$m=65536,t=16,p=1$UjloUkRMckJkN2IwOVJwLw$eLAic8tCkF+WHQNn5dSOs6RHCbNetLVAnH9XBHr0S1k',1,NULL,'default','',NULL,0,'',NULL,'',_binary 'a:13:{s:14:\"interfaceSetup\";s:7:\"backend\";s:10:\"moduleData\";a:13:{s:28:\"dashboard/current_dashboard/\";s:40:\"ed50813904ea68407e7b887e7e2911534d3127bb\";s:10:\"web_layout\";a:4:{s:8:\"function\";s:1:\"2\";s:8:\"language\";s:2:\"-1\";s:10:\"showHidden\";b:1;s:19:\"constant_editor_cat\";N;}s:57:\"TYPO3\\CMS\\Backend\\Utility\\BackendUtility::getUpdateSignal\";a:0:{}s:10:\"FormEngine\";a:2:{i:0;a:1:{s:32:\"11c05ac60fae87cffc73e8b2727dae15\";a:5:{i:0;s:1:\"1\";i:1;a:5:{s:4:\"edit\";a:1:{s:29:\"tx_styleguide_elements_folder\";a:1:{i:1;s:4:\"edit\";}}s:7:\"defVals\";N;s:12:\"overrideVals\";N;s:11:\"columnsOnly\";N;s:6:\"noView\";N;}i:2;s:52:\"&edit%5Btx_styleguide_elements_folder%5D%5B1%5D=edit\";i:3;a:5:{s:5:\"table\";s:29:\"tx_styleguide_elements_folder\";s:3:\"uid\";i:1;s:3:\"pid\";i:112;s:3:\"cmd\";s:4:\"edit\";s:12:\"deleteAccess\";b:1;}i:4;s:93:\"/typo3/module/web/list?token=3874400e954c7de33f2c66040eb1214074d9c9b1&id=112&table=&pointer=1\";}}i:1;s:32:\"11c05ac60fae87cffc73e8b2727dae15\";}s:8:\"web_list\";a:3:{s:8:\"function\";N;s:8:\"language\";N;s:19:\"constant_editor_cat\";N;}s:16:\"opendocs::recent\";a:8:{s:32:\"867f073f256d6651a5b76cb1bc36ded4\";a:4:{i:0;s:60:\"JP 11e Page props changed - Translation not ready to publish\";i:1;a:5:{s:4:\"edit\";a:1:{s:5:\"pages\";a:1:{i:53;s:4:\"edit\";}}s:7:\"defVals\";N;s:12:\"overrideVals\";a:1:{s:5:\"pages\";a:1:{s:16:\"sys_language_uid\";s:1:\"2\";}}s:11:\"columnsOnly\";N;s:6:\"noView\";N;}i:2;s:77:\"&edit%5Bpages%5D%5B53%5D=edit&overrideVals%5Bpages%5D%5Bsys_language_uid%5D=2\";i:3;a:5:{s:5:\"table\";s:5:\"pages\";s:3:\"uid\";i:53;s:3:\"pid\";i:16;s:3:\"cmd\";s:4:\"edit\";s:12:\"deleteAccess\";b:1;}}s:32:\"f7e608d6e9127a38fb480425c61d2c10\";a:4:{i:0;s:9:\"Header JP\";i:1;a:5:{s:4:\"edit\";a:1:{s:10:\"tt_content\";a:1:{i:36;s:4:\"edit\";}}s:7:\"defVals\";N;s:12:\"overrideVals\";N;s:11:\"columnsOnly\";N;s:6:\"noView\";N;}i:2;s:34:\"&edit%5Btt_content%5D%5B36%5D=edit\";i:3;a:5:{s:5:\"table\";s:10:\"tt_content\";s:3:\"uid\";i:36;s:3:\"pid\";i:51;s:3:\"cmd\";s:4:\"edit\";s:12:\"deleteAccess\";b:1;}}s:32:\"614a7ec31add9a1a1be54fb7bc749a33\";a:4:{i:0;s:60:\"DE 11e Page props changed - Translation not ready to publish\";i:1;a:5:{s:4:\"edit\";a:1:{s:5:\"pages\";a:1:{i:52;s:4:\"edit\";}}s:7:\"defVals\";N;s:12:\"overrideVals\";a:1:{s:5:\"pages\";a:1:{s:16:\"sys_language_uid\";s:1:\"1\";}}s:11:\"columnsOnly\";N;s:6:\"noView\";N;}i:2;s:77:\"&edit%5Bpages%5D%5B52%5D=edit&overrideVals%5Bpages%5D%5Bsys_language_uid%5D=1\";i:3;a:5:{s:5:\"table\";s:5:\"pages\";s:3:\"uid\";i:52;s:3:\"pid\";i:16;s:3:\"cmd\";s:4:\"edit\";s:12:\"deleteAccess\";b:1;}}s:32:\"61765c6a3de2e0ba09d6230397278147\";a:4:{i:0;s:16:\"Header DE edited\";i:1;a:5:{s:4:\"edit\";a:1:{s:10:\"tt_content\";a:1:{i:12;s:4:\"edit\";}}s:7:\"defVals\";N;s:12:\"overrideVals\";N;s:11:\"columnsOnly\";N;s:6:\"noView\";N;}i:2;s:34:\"&edit%5Btt_content%5D%5B12%5D=edit\";i:3;a:5:{s:5:\"table\";s:10:\"tt_content\";s:3:\"uid\";i:12;s:3:\"pid\";i:22;s:3:\"cmd\";s:4:\"edit\";s:12:\"deleteAccess\";b:1;}}s:32:\"ca1d9f585ca31e6d709268bfa0021f7e\";a:4:{i:0;s:16:\"Header JP edited\";i:1;a:5:{s:4:\"edit\";a:1:{s:10:\"tt_content\";a:1:{i:13;s:4:\"edit\";}}s:7:\"defVals\";N;s:12:\"overrideVals\";N;s:11:\"columnsOnly\";N;s:6:\"noView\";N;}i:2;s:34:\"&edit%5Btt_content%5D%5B13%5D=edit\";i:3;a:5:{s:5:\"table\";s:10:\"tt_content\";s:3:\"uid\";i:13;s:3:\"pid\";i:22;s:3:\"cmd\";s:4:\"edit\";s:12:\"deleteAccess\";b:1;}}s:32:\"9c43855926e54a70d4d0a190cb54db8c\";a:4:{i:0;s:16:\"Header EN edited\";i:1;a:5:{s:4:\"edit\";a:1:{s:10:\"tt_content\";a:1:{i:11;s:4:\"edit\";}}s:7:\"defVals\";N;s:12:\"overrideVals\";N;s:11:\"columnsOnly\";N;s:6:\"noView\";N;}i:2;s:34:\"&edit%5Btt_content%5D%5B11%5D=edit\";i:3;a:5:{s:5:\"table\";s:10:\"tt_content\";s:3:\"uid\";i:11;s:3:\"pid\";i:22;s:3:\"cmd\";s:4:\"edit\";s:12:\"deleteAccess\";b:1;}}s:32:\"629911c017052e9e049ce359020150c0\";a:4:{i:0;s:16:\"Header EN edited\";i:1;a:5:{s:4:\"edit\";a:1:{s:10:\"tt_content\";a:1:{i:7;s:4:\"edit\";}}s:7:\"defVals\";N;s:12:\"overrideVals\";N;s:11:\"columnsOnly\";N;s:6:\"noView\";N;}i:2;s:33:\"&edit%5Btt_content%5D%5B7%5D=edit\";i:3;a:5:{s:5:\"table\";s:10:\"tt_content\";s:3:\"uid\";i:7;s:3:\"pid\";i:16;s:3:\"cmd\";s:4:\"edit\";s:12:\"deleteAccess\";b:1;}}s:32:\"f109e0aa70f0b4a44c314d01554c6c76\";a:4:{i:0;s:14:\"Content Page 2\";i:1;a:5:{s:4:\"edit\";a:1:{s:10:\"tt_content\";a:1:{i:42;s:4:\"edit\";}}s:7:\"defVals\";N;s:12:\"overrideVals\";N;s:11:\"columnsOnly\";N;s:6:\"noView\";N;}i:2;s:34:\"&edit%5Btt_content%5D%5B42%5D=edit\";i:3;a:5:{s:5:\"table\";s:10:\"tt_content\";s:3:\"uid\";i:42;s:3:\"pid\";i:197;s:3:\"cmd\";s:4:\"edit\";s:12:\"deleteAccess\";b:1;}}}s:12:\"system_dbint\";a:5:{s:8:\"function\";s:1:\"0\";s:8:\"language\";N;s:19:\"constant_editor_cat\";N;s:6:\"search\";s:3:\"raw\";s:22:\"search_query_makeQuery\";s:3:\"all\";}s:9:\"file_list\";a:3:{s:8:\"function\";N;s:8:\"language\";N;s:19:\"constant_editor_cat\";N;}s:6:\"web_ts\";a:4:{s:8:\"function\";s:85:\"TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateInformationModuleFunctionController\";s:8:\"language\";N;s:19:\"constant_editor_cat\";s:7:\"content\";s:6:\"action\";s:25:\"web_typoscript_infomodify\";}s:7:\"tx_solr\";s:94:\"O:53:\"ApacheSolrForTypo3\\Solr\\System\\Mvc\\Backend\\ModuleData\":1:{s:7:\"\0*\0core\";s:8:\"/core_en\";}\";s:16:\"browse_links.php\";a:1:{s:12:\"expandFolder\";s:13:\"1:/Testcases/\";}s:17:\"typoscript_active\";a:6:{s:18:\"sortAlphabetically\";b:1;s:28:\"displayConstantSubstitutions\";b:1;s:15:\"displayComments\";b:1;s:23:\"selectedTemplatePerPage\";a:1:{i:1;i:1;}s:18:\"constantConditions\";a:0:{}s:15:\"setupConditions\";a:0:{}}s:25:\"web_typoscript_infomodify\";a:1:{s:23:\"selectedTemplatePerPage\";a:1:{i:1;i:1;}}}s:14:\"emailMeAtLogin\";i:0;s:8:\"titleLen\";i:50;s:8:\"edit_RTE\";s:1:\"1\";s:20:\"edit_docModuleUpload\";s:1:\"1\";s:25:\"resizeTextareas_MaxHeight\";i:500;s:4:\"lang\";s:7:\"default\";s:19:\"firstLoginTimeStamp\";i:1690961257;s:15:\"moduleSessionID\";a:13:{s:28:\"dashboard/current_dashboard/\";s:40:\"37d38980ca7c58af48ac1646947d276468fc768a\";s:10:\"web_layout\";s:40:\"3aac37e0a639b7781df3709000bf37ca41046a72\";s:57:\"TYPO3\\CMS\\Backend\\Utility\\BackendUtility::getUpdateSignal\";s:40:\"37d38980ca7c58af48ac1646947d276468fc768a\";s:10:\"FormEngine\";s:40:\"4562c0807fe96f99e97bd8ef0a0333689eb535e6\";s:8:\"web_list\";s:40:\"c6dd06d5dc94b4c48bae35e3653edaf4819296b2\";s:16:\"opendocs::recent\";s:40:\"64ca95c12429a06a43756fdb8fe86d41f4b51abd\";s:12:\"system_dbint\";s:40:\"86c5e4f911279b454ea8f84b3ec939a19a1e4fd4\";s:9:\"file_list\";s:40:\"18805841000a9cdb6becc6bdb35ef13f25965c59\";s:6:\"web_ts\";s:40:\"1325c4454f206550a85c95d9bee1a8d4f3e3fcbb\";s:7:\"tx_solr\";s:40:\"bab0880bb65c2cfc64fa6615438dbce7af081242\";s:16:\"browse_links.php\";s:40:\"4562c0807fe96f99e97bd8ef0a0333689eb535e6\";s:17:\"typoscript_active\";s:40:\"76352b098fc93c87e073f10b119bf85d0aeab101\";s:25:\"web_typoscript_infomodify\";s:40:\"1325c4454f206550a85c95d9bee1a8d4f3e3fcbb\";}s:17:\"BackendComponents\";a:1:{s:6:\"States\";a:2:{s:8:\"Pagetree\";a:1:{s:9:\"stateHash\";a:11:{s:3:\"0_1\";s:1:\"1\";s:3:\"0_2\";s:1:\"1\";s:5:\"0_195\";s:1:\"1\";s:5:\"0_196\";s:1:\"1\";s:5:\"0_208\";s:1:\"1\";s:3:\"0_4\";s:1:\"1\";s:3:\"0_3\";s:1:\"1\";s:4:\"0_34\";s:1:\"1\";s:4:\"0_39\";s:1:\"1\";s:4:\"0_16\";s:1:\"1\";s:4:\"0_37\";s:1:\"1\";}}s:15:\"FileStorageTree\";a:1:{s:9:\"stateHash\";a:1:{s:11:\"1_227846615\";s:1:\"1\";}}}}s:10:\"inlineView\";s:167:\"{\"tt_content\":{\"46\":{\"sys_file_reference\":[\"1\"]},\"16\":{\"sys_file_reference\":[10]}},\"tx_news_domain_model_news\":{\"5\":{\"tt_content\":[\"18\"]},\"3\":{\"tt_content\":{\"1\":\"\"}}}}\";s:10:\"navigation\";a:1:{s:5:\"width\";s:3:\"391\";}}',NULL,NULL,0,NULL,1768231591,0,NULL,NULL,'',0),(2,0,1690960893,1690960893,0,0,0,0,NULL,'_cli_',0,'$argon2i$v=19$m=65536,t=16,p=1$TnIuRlBFSlc5b1VRdjRPcA$TwEl7KJfFX125vkopPIs/KB+aeb1QcfDXsc3eonaNU0',1,NULL,'default','',NULL,0,'',NULL,'',_binary 'a:9:{s:14:\"interfaceSetup\";s:0:\"\";s:10:\"moduleData\";a:0:{}s:14:\"emailMeAtLogin\";i:0;s:8:\"titleLen\";i:50;s:8:\"edit_RTE\";s:1:\"1\";s:20:\"edit_docModuleUpload\";s:1:\"1\";s:25:\"resizeTextareas_MaxHeight\";i:500;s:4:\"lang\";s:7:\"default\";s:19:\"firstLoginTimeStamp\";i:1690960893;}',NULL,NULL,0,NULL,0,0,NULL,NULL,'',0);
/*!40000 ALTER TABLE `be_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `fe_groups`
--

LOCK TABLES `fe_groups` WRITE;
/*!40000 ALTER TABLE `fe_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `fe_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `fe_users`
--

LOCK TABLES `fe_users` WRITE;
/*!40000 ALTER TABLE `fe_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `fe_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,0,1696487800,1660115842,0,0,0,0,'',512,NULL,0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,1,31,31,1,'Home','/',1,NULL,1,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,1696487800,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(2,3,1695902743,1695897937,0,0,0,0,'',64,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'5a Workflows - Page properties','/5a-page-workflows-page-properties',1,'',0,0,'',0,0,'Page properties (title) has changed',0,'',0,0,'',0,'',0,'',0,1695902743,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(3,1,1695902066,1695901523,0,0,0,0,'',32,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'EXT:in2publish','/extin2publish-core',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(4,1,1695902072,1695901537,0,0,0,0,'',16,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'EXT:in2publish_core','/extin2publish',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(5,4,1695901977,1695901581,0,0,0,0,'',112,'',0,0,0,0,NULL,_binary '{\"title\":\"\"}',0,0,0,0,1,0,31,27,0,'1a Page properties','/extin2publish/1-publish-page',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,1695901581,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(6,4,1695901980,1695901957,0,0,0,0,'',128,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'1b Page content','/extin2publish/1b-page-content',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(7,3,1695905926,1695905917,0,0,0,0,'',128,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'5b Workflows - Page content','/extin2publish-core/5b-workflows-page-content',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,1695905947,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(8,3,1695906757,1695906746,0,0,0,0,'',384,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'6a Workflows / TreeViewColors - Page properties','/extin2publish-core/6a-workflows-/-treeviewcolors-page-properties',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(9,3,1695906793,1695906780,0,0,0,0,'',640,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'6b Workflows / TreeViewColors - ContentElements','/extin2publish-core/6b-workflows-/-treeviewcolors-contentelements',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(10,16,1698225997,1695908424,1,0,0,0,'',896,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'11a Workflows/CLC LIW - Page props','/extin2publish-core/11a-workflows/clc-liw-page-props',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,1697010533,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(11,16,1698225997,1695908449,1,0,0,0,'',896,NULL,0,1,10,10,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"hidden\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11a Workflows \\/ CLC \\/ languageIndependentWorkflows - Page properties\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'DE 11a Workflows / CLC / languageIndependentWorkflows - Page properties','/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(12,16,1698225997,1695908463,1,0,0,0,'',896,NULL,0,2,10,10,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"hidden\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11a Workflows \\/ CLC \\/ languageIndependentWorkflows - Page properties\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'JP 11a Workflows / CLC / languageIndependentWorkflows - Page properties','/extin2publish-core/jp-11a-workflows-/-clc-/-languageindependentworkflows-page-properties',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(13,16,1698225992,1696253408,1,0,0,0,'',1152,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'11b Workflows / CLC / languageIndependentWorkflows - Header changed','/extin2publish-core/11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(14,16,1698225992,1696253408,1,0,0,0,'',1152,NULL,0,1,13,13,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"10\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11b Workflows \\/ CLC \\/ languageIndependentWorkflows - Header changed\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,1,0,31,27,0,'DE 11b Workflows / CLC / languageIndependentWorkflows - Header changed','/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(15,16,1698225992,1696253408,1,0,0,0,'',1152,NULL,0,2,13,13,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"10\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11b Workflows \\/ CLC \\/ languageIndependentWorkflows - Header changed\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,1,0,31,27,0,'JP 11b Workflows / CLC / languageIndependentWorkflows - Header changed','/extin2publish-core/jp-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(16,3,1697543491,1696253425,0,0,0,0,'',2432,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'11 Workflows / CLC / languageIndependentWorkflows','/extin2publish-core/11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,1696405393,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(17,3,1697548085,1696253425,0,0,0,0,'',2432,NULL,0,1,16,16,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1-1\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"13\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11 Workflows \\/ CLC \\/ languageIndependentWorkflows\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,1,0,31,27,0,'DE Workflows / CLC / languageIndependentWorkflows','/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,1696405404,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(18,3,1696253788,1696253425,0,0,0,0,'',2432,NULL,0,2,16,16,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1-1\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"13\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11c Workflows \\/ CLC \\/ languageIndependentWorkflows - Header changed\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,1,0,31,27,0,'JP 11c Workflows / CLC / languageIndependentWorkflows - Header changed','/extin2publish-core/jp-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(19,3,1696408921,1696408735,0,0,0,0,'',6272,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'12a Workflows / TreeviewColors - Page properties','/extin2publish-core/12a-workflows-/-treeviewcolors-page-properties',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(20,3,1696409276,1696408735,0,0,0,0,'',6272,NULL,0,1,19,19,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/12a-workflows-\\/-treeviewcolors-page-properties\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"10\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"12a Workflows \\/ TreeviewColors - Page properties\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\",\"tx_impexp_origuid\":\"0\"}',0,0,0,0,1,0,31,27,0,'DE 11a Workflows / TreeviewColors - Page properties','/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-2',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(21,3,1696409319,1696408735,0,0,0,0,'',6272,NULL,0,2,19,19,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/12a-workflows-\\/-treeviewcolors-page-properties\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"10\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"12a Workflows \\/ TreeviewColors - Page properties\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\",\"tx_impexp_origuid\":\"0\"}',0,0,0,0,1,0,31,27,0,'JP 12a Workflows /TreeviewColorss- Page properties','/extin2publish-core/jp-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-2',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(22,3,1696408905,1696408834,0,0,0,0,'',8064,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'12b Workflows / TreeviewColors - Header changed','/extin2publish-core/12b-workflows-/-treeviewcolors-header-changed',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(23,3,1696409341,1696408834,0,0,0,0,'',8064,NULL,0,1,22,22,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/12b-workflows-\\/-treeviewcolors-header-changed\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"16\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"12b Workflows \\/ TreeviewColors - Header changed\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,1,0,31,27,0,'DE 12b Workflows / TreeviewColors - Header changed','/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1-1',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(24,3,1696409356,1696408834,0,0,0,0,'',8064,NULL,0,2,22,22,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/12b-workflows-\\/-treeviewcolors-header-changed\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"16\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"12b Workflows \\/ TreeviewColors - Header changed\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,1,0,31,27,0,'JP 12b Workflows / TreeviewColors - Header changed','/extin2publish-core/jp-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1-1',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(25,3,1696489032,1696432486,0,0,0,0,'',8320,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'13a SolrIntegration','/extin2publish-core/12b-workflows-/-treeviewcolors-header-changed/13-solrintegration',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(26,3,1696487844,1696487830,0,0,0,0,'',9088,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'News Folder','/news-folder',254,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(33,1,1697024852,1697023506,0,0,0,0,'',24,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'News Folder','/extin2publish/default-d644ce19a7fcaff4a29e6944d0a1e4ef',254,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(34,3,1697114500,1697114203,0,0,0,0,'0',256,NULL,0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'5c Workflows - Unfulfilled Dependencies','/extin2publish-core/5c-workflows-unfulfilled-dependencies',1,NULL,0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(37,34,1697114704,1697114704,0,0,0,0,'',512,'',0,0,0,0,NULL,'',0,0,0,0,1,0,31,27,0,'5c.2 Parent published','/extin2publish-core/5c-workflows-unfulfilled-dependencies/5c2-parent-published',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(38,37,1698235929,1697114763,0,0,0,0,'',256,NULL,0,0,0,0,NULL,_binary '{\"title\":\"\"}',0,0,0,0,1,6,31,31,1,'5c.2.1 Child Ready To Publish','/extin2publish-core/5c-workflows-unfulfilled-dependencies/5c2-parent-published/5c21-child-read-to-publish',1,NULL,0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,1697114800,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(39,1,1697536456,1697119823,0,0,0,0,'',20,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'8 HideRecordsDeletedDifferently','/8-hiderecordsdeleteddifferently',1,'',0,0,'',0,0,'There are two deleted subpages, which should only be visible in the PublishOverview if the setting treatRemovedAndDeletedAsDifference=true',0,'',0,0,'',0,'',0,'',0,1697119835,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(42,16,1697544785,1697543530,0,0,0,1690899120,'',256,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'11d Page with expired endtime','/extin2publish-core/11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/11a',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,1697544785,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(43,16,1697544251,1697543541,0,0,0,0,'',128,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'11c Content disabled','/extin2publish-core/11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/11b',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(44,16,1698046782,1697543555,0,0,0,0,'',64,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,6,31,31,1,'11b Content translated in ConnectedMode','/extin2publish-core/11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/11a-1',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(48,16,1698046782,1697544018,0,0,0,0,'',64,NULL,0,1,44,44,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1-1\\/11a-1\",\"hidden\":\"1\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11b Content translated in ConnectedMode\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,6,31,31,1,'DE 11b Content translated in ConnectedMode','/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/translate-to-german-11b-content-translated-in-connectedmode',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(49,16,1697544330,1697544258,0,0,0,0,'',128,NULL,0,1,43,43,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1-1\\/11b\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11c Content disabled\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'11c Content disabled DE','/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/translate-to-german-11c-content-disabled',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(50,16,1698229319,1697544431,0,0,1696162860,0,'',256,NULL,0,1,42,42,'{\"starttime\":\"custom\",\"endtime\":\"custom\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1-1\\/11a\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"1690899120\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11d Page with expired endtime\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,6,31,31,1,'11d Page with starttime DE','/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/translate-to-german-11d-page-with-starttime',1,'',0,0,'',0,0,'Translated in Connected Mode',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(51,16,1697545739,1697544822,0,0,0,0,'',512,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'11e Content changed - Translation not RTP - Overview','/extin2publish-core/11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/11e-languageindependentworkflows',1,'',0,0,'',0,0,'OverviewModul',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(52,16,1697550940,1697544923,0,0,0,0,'',512,NULL,0,1,51,51,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1-1\\/11e-languageindependentworkflows\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11e Content changed - Translation not RTP - Overview\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"OverviewModul\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'DE 11e Content changed - Translation not RTP - Overview','/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/translate-to-german-11e-page-props-changed-translation-not-ready-to-publish',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(53,16,1697550954,1697544937,0,0,0,0,'',512,NULL,0,2,51,51,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1-1\\/11e-languageindependentworkflows\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11e Content changed - Translation not RTP - Overview\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"OverviewModul\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'JP 11e Content changed - Translation not RTP - Overview','/extin2publish-core/11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/translate-to-japanese-11e-page-props-changed-translation-not-ready-to-publish',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(54,16,1697545758,1697545388,0,0,0,0,'',896,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'11f Content changed - Translation not RTP - Workflow','/extin2publish-core/11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/11e-languageindependentworkflows-1',1,'',0,0,'',0,0,'Workflow Modul',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(55,16,1697545545,1697545388,0,0,0,0,'',896,NULL,0,1,54,54,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1-1\\/11e-languageindependentworkflows-1\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"51\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11f Content changed - Translation not ready to publish\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"Workflow Modul\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\",\"tx_impexp_origuid\":\"0\"}',0,0,0,0,1,0,31,27,0,'DE 11e Content changed - Translation not ready to publish','/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/translate-to-german-11e-page-props-changed-translation-not-ready-to-publish-1',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(56,16,1697545561,1697545388,0,0,0,0,'',896,NULL,0,2,54,54,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1-1\\/11e-languageindependentworkflows-1\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"fe_login_mode\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"51\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11f Content changed - Translation not ready to publish\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"fe_login_mode\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"Workflow Modul\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\",\"tx_impexp_origuid\":\"0\"}',0,0,0,0,1,0,31,27,0,'JP 11e Content changed - Translation not ready to publish','/extin2publish-core/11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/translate-to-japanese-11e-page-props-changed-translation-not-ready-to-publish-1',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(57,3,1697716969,1697716889,0,0,0,0,'',1536,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'6c Workflows / TreeViewColors - Cut element','/extin2publish-core/6b-workflows-/-treeviewcolors-contentelements/6c-workflows-/-treeviewcolors-cut-element',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,1697716993,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(58,3,1697716965,1697716948,0,0,0,0,'',1984,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'6c Workflows / TreeViewColors - Paste element','/extin2publish-core/6b-workflows-/-treeviewcolors-contentelements/6c-workflows-/-treeviewcolors-cut-element-1',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(59,3,1697723120,1697723082,0,0,0,0,'',8448,'Old row description',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'14 - Ignored Field rowDescription','/extin2publish-core/14-ignored-field-rowdescription',1,'',0,0,'',0,0,'The field rowDescription is ignored by the publisher (Tab: Notes Field: Description)',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(60,34,1698235892,1698048853,0,0,0,0,'',768,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"fe_login_mode\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,6,31,31,1,'5c.3 User has no permission for inserted content element','/extin2publish-core/5c-workflows-unfulfilled-dependencies/5c1-parent-not-published/5c12-no-permission-for-content-element',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,1698142784,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(65,6,1698311577,1698311555,0,0,0,0,'0',256,NULL,0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'1b.1 Page content - changed','/extin2publish/1b-page-content/1b1-page-content-changed',1,NULL,0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(66,34,1698311676,1698311665,0,0,0,0,'0',1024,NULL,0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'5c.4 User has no permission for changed inserted content element','/extin2publish-core/5c-workflows-unfulfilled-dependencies/5c4-user-has-no-permission-for-changed-inserted-content-element',1,NULL,0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(67,3,1700665220,1698752956,0,0,0,0,'',8512,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'22 SingleRecordPublishing','/20-singlerecordpublishing',1,'',0,0,'',0,0,'Content elements can be published individually in the OverviewModule',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','Changed nav_title',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(68,4,1700037584,1700037572,0,0,0,0,'',256,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'3 Remote Cache Teset','/extin2publish/3-remote-cache-teset',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(69,68,1700037678,1700037618,0,0,0,0,'',256,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'3a Page with current date','/extin2publish/3-remote-cache-teset/3a-page-with-current-date',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,1700037678,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(70,68,1700038371,1700037824,0,0,0,0,'',512,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'3b Page with TCEMAIN.clearCacheCmd','/extin2publish/3-remote-cache-teset/3b-page-with-tcemainclearcachecmd',1,'TCEMAIN.clearCacheCmd = 69',0,0,'',0,0,'Make a change on this page and publish. The displayed date on page 3a should be updated on foreign.',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(71,73,1700554907,1700506507,0,0,0,0,'',64,NULL,0,0,0,0,NULL,_binary '{\"title\":\"\"}',0,0,0,0,1,0,31,27,0,'1d.1 Free Mode','/extin2publish/1c-content-translated-freemode',1,NULL,0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(72,73,1700554963,1700506783,0,0,0,0,'',64,NULL,0,1,71,71,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish\\/1c-content-translated-freemode\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"1d.1 Free Mode\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'1d.1 Free Mode - DE','/extin2publish/translate-to-german-1c-content-translated-freemode',1,NULL,0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(73,4,1700554904,1700506882,0,0,0,0,'',160,NULL,0,0,0,0,NULL,_binary '{\"title\":\"\"}',0,0,0,0,1,0,31,27,0,'1d Translated Content','/extin2publish/1c-codeception-tests',1,NULL,0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(74,73,1700554910,1700506952,0,0,0,0,'0',128,NULL,0,0,0,0,NULL,_binary '{\"title\":\"\"}',0,0,0,0,1,0,31,27,0,'1d.2 Connected Mode','/extin2publish/1c-codeception-tests/1c2-connected-mode',1,NULL,0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(75,73,1700554978,1700507009,0,0,0,0,'',128,NULL,0,1,74,74,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish\\/1c-codeception-tests\\/1c2-connected-mode\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"hidden\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"1d.2 Connected Mode\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"0\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'1d.2 Connected Mode - DE','/extin2publish/1c-codeception-tests/translate-to-german-1c2-connected-mode',1,NULL,0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(76,1,1730361316,1730131569,0,0,0,0,'',22,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'24 Page with Category 1 and Category 2','/24-page-with-category-1-and-category-2',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',2,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(77,16,1730279180,1730279064,0,0,0,0,'',1152,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'11g Workflow triggered by translation','/extin2publish-core/11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/11g-workflow-triggered-by-translation',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,1730279180,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(78,16,1730279466,1730279446,0,1,0,0,'',1152,NULL,0,1,77,77,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1-1\\/11g-workflow-triggered-by-translation\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"hidden\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11g Workflow triggered by translation\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'DE: 11g Workflow triggered by translation','/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/translate-to-german-11g-workflow-triggered-by-translation',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(79,16,1730279477,1730279470,0,1,0,0,'',1152,NULL,0,2,77,77,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1-1\\/11g-workflow-triggered-by-translation\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"hidden\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11g Workflow triggered by translation\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'JP: 11g Workflow triggered by translation','/extin2publish-core/11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/translate-to-japanese-11g-workflow-triggered-by-translation',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(82,16,1734364435,1734364286,0,0,0,0,'',1664,'',0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'11h Visibility settings','/extin2publish-core/11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/11h-visibility-settings',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'summary','',0.5,'',''),(83,16,1734364536,1734364506,0,0,0,0,'',1664,NULL,0,1,82,82,'{\"starttime\":\"parent\",\"endtime\":\"parent\",\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1-1\\/11h-visibility-settings\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"categories\":\"0\",\"l10n_diffsource\":\"{\\\"doktype\\\":\\\"\\\",\\\"title\\\":\\\"\\\",\\\"slug\\\":\\\"\\\",\\\"nav_title\\\":\\\"\\\",\\\"subtitle\\\":\\\"\\\",\\\"seo_title\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"no_index\\\":\\\"\\\",\\\"no_follow\\\":\\\"\\\",\\\"canonical_link\\\":\\\"\\\",\\\"sitemap_changefreq\\\":\\\"\\\",\\\"sitemap_priority\\\":\\\"\\\",\\\"og_title\\\":\\\"\\\",\\\"og_description\\\":\\\"\\\",\\\"og_image\\\":\\\"\\\",\\\"twitter_title\\\":\\\"\\\",\\\"twitter_description\\\":\\\"\\\",\\\"twitter_image\\\":\\\"\\\",\\\"twitter_card\\\":\\\"\\\",\\\"abstract\\\":\\\"\\\",\\\"keywords\\\":\\\"\\\",\\\"author\\\":\\\"\\\",\\\"author_email\\\":\\\"\\\",\\\"lastUpdated\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"newUntil\\\":\\\"\\\",\\\"backend_layout\\\":\\\"\\\",\\\"backend_layout_next_level\\\":\\\"\\\",\\\"content_from_pid\\\":\\\"\\\",\\\"target\\\":\\\"\\\",\\\"cache_timeout\\\":\\\"\\\",\\\"cache_tags\\\":\\\"\\\",\\\"is_siteroot\\\":\\\"\\\",\\\"no_search\\\":\\\"\\\",\\\"no_search_sub_entries\\\":\\\"\\\",\\\"php_tree_stop\\\":\\\"\\\",\\\"module\\\":\\\"\\\",\\\"media\\\":\\\"\\\",\\\"tsconfig_includes\\\":\\\"\\\",\\\"TSconfig\\\":\\\"\\\",\\\"l18n_cfg\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"nav_hide\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"extendToSubpages\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"summary\",\"t3_origuid\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11h Visibility settings\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"editlock\":\"0\",\"fe_group\":\"\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'DE 11h Visibility settings','/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/de-11h-visibility-settings',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',''),(86,0,1741949069,1741945243,1,0,0,0,'0',768,NULL,0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'styleguide TCA demo','/',1,NULL,1,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,'',0,0,'',NULL,0,'',NULL,0,'','',0.5,'','tx_styleguide'),(112,86,1741949039,1741945243,1,0,0,0,'',1536,NULL,0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'elements folder 1','/elements-folder',1,NULL,0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,'',0,0,'',NULL,0,'',NULL,0,'','',0.5,'','tx_styleguide_elements_folder'),(277,0,1768232506,1741947254,1,0,0,0,'',768,NULL,0,0,0,0,NULL,_binary '{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"no_search_sub_entries\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'styleguide TCA demo','/',1,NULL,1,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,'',0,0,'',NULL,0,'',NULL,0,'','',0.5,'','tx_styleguide'),(468,0,1768232510,1741948624,1,0,0,0,'0',1024,NULL,0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'styleguide frontend demo','/',1,NULL,1,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,'',0,0,'',NULL,0,'',NULL,0,'','',0.5,'','tx_styleguide_frontend_root'),(495,16,1743169202,1743169178,0,0,0,0,'',1920,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'11i deleted translation DE','/extin2publish-core/11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/11i-deleted-translation-de',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'','',0.5,'',''),(496,16,1743169265,1743169211,1,1,0,0,'',1920,NULL,0,1,495,495,'{\"nav_hide\":\"parent\",\"url\":\"parent\",\"lastUpdated\":\"parent\",\"newUntil\":\"parent\",\"no_search\":\"parent\",\"shortcut\":\"parent\",\"shortcut_mode\":\"parent\",\"content_from_pid\":\"parent\",\"author\":\"parent\",\"author_email\":\"parent\",\"media\":\"parent\",\"starttime\":\"parent\",\"endtime\":\"parent\",\"og_image\":\"parent\",\"twitter_image\":\"parent\",\"no_search_sub_entries\":\"parent\"}',_binary '{\"doktype\":\"1\",\"slug\":\"\\/extin2publish-core\\/11a-workflows-\\/-clc-\\/-languageindependentworkflows-page-properties-changed-1-1\\/11i-deleted-translation-de\",\"categories\":\"0\",\"layout\":\"0\",\"lastUpdated\":\"0\",\"newUntil\":\"0\",\"cache_timeout\":\"0\",\"shortcut\":\"0\",\"shortcut_mode\":\"0\",\"content_from_pid\":\"0\",\"mount_pid\":\"0\",\"module\":\"\",\"hidden\":\"0\",\"starttime\":\"0\",\"endtime\":\"0\",\"l10n_parent\":\"0\",\"l10n_diffsource\":\"{\\\"hidden\\\":\\\"\\\"}\",\"sitemap_priority\":\"0.5\",\"twitter_card\":\"\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"title\":\"11i deleted translation DE\",\"nav_hide\":\"0\",\"url\":\"\",\"no_search\":\"0\",\"author\":\"\",\"author_email\":\"\",\"media\":\"0\",\"og_image\":\"0\",\"twitter_image\":\"0\",\"no_search_sub_entries\":\"0\",\"TSconfig\":\"\",\"php_tree_stop\":\"0\",\"extendToSubpages\":\"0\",\"target\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"0\",\"mount_pid_ol\":\"0\",\"l18n_cfg\":\"0\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"tsconfig_includes\":\"\",\"editlock\":\"0\",\"no_index\":\"0\",\"no_follow\":\"0\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"fe_group\":\"\",\"rowDescription\":\"\"}',0,0,0,0,1,0,31,27,0,'[Translate to German:] 11i deleted translation DE','/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/translate-to-german-11i-deleted-translation-de',1,'',0,0,'',0,0,'',0,'',0,0,NULL,0,'',0,NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'',NULL,0,'',NULL,0,'','',0.5,'',''),(497,3,1760434756,1760434740,0,0,0,0,'',8560,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'27 deleted and recreated translation','/extin2publish-core/27-deleted-and-recreated-translation',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'','',0.5,'',''),(1000,3,1765876660,1765876646,0,0,0,0,'',8816,'',0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'28 different files in translations','/extin2publish-core/28-different-files-in-translations',1,'',0,0,'',0,0,'',0,'',0,0,'',0,'',0,'',0,0,'','',0,'','','',0,0,0,0,0,'','','',0,0,'',0,0,'','',0,'','',0,'','',0.5,'','');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_be_shortcuts`
--

LOCK TABLES `sys_be_shortcuts` WRITE;
/*!40000 ALTER TABLE `sys_be_shortcuts` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_be_shortcuts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_category`
--

LOCK TABLES `sys_category` WRITE;
/*!40000 ALTER TABLE `sys_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_category_record_mm`
--

LOCK TABLES `sys_category_record_mm` WRITE;
/*!40000 ALTER TABLE `sys_category_record_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_category_record_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_csp_resolution`
--

LOCK TABLES `sys_csp_resolution` WRITE;
/*!40000 ALTER TABLE `sys_csp_resolution` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_csp_resolution` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_file`
--

LOCK TABLES `sys_file` WRITE;
/*!40000 ALTER TABLE `sys_file` DISABLE KEYS */;
INSERT INTO `sys_file` VALUES (2,0,1696487911,0,0,1,2,0,'/user_upload/glenn-carstens-peters-npxXWgQ33ZQ-unsplash.jpg','a8ffb82a26f6a8379a8a366ee3bda4fcda33b9eb','19669f1e02c2f16705ec7587044c66443be70725','jpg','image/jpeg','glenn-carstens-peters-npxXWgQ33ZQ-unsplash.jpg','29275461b82629c2f15297c5bbab07063851c1ee',601238,1750661151,1746170231),(3,0,1696487911,0,0,1,2,0,'/user_upload/nate-johnston-JiBCTXVdR64-unsplash.jpg','0c5b8687aa3b9fca36894b5b34b683a535fbba63','19669f1e02c2f16705ec7587044c66443be70725','jpg','image/jpeg','nate-johnston-JiBCTXVdR64-unsplash.jpg','bed158226e85c0af93cd4808f924bde17a1536ee',462395,1750661151,1746170231),(4,0,1696487911,0,0,1,2,0,'/user_upload/roman-wimmers-STrq0wSBGIs-unsplash.jpg','c7307b1ad0d0f692df6c17aca0a9ed6c4a95d1c0','19669f1e02c2f16705ec7587044c66443be70725','jpg','image/jpeg','roman-wimmers-STrq0wSBGIs-unsplash.jpg','5e6af1681cdbdd7fc9dcbf68b4265d4dff54d7a4',1435414,1750661151,1746170231),(5,0,1697025237,0,0,1,2,0,'/user_upload/maxim-berg-9XunOfueKKI-unsplash.jpg','46abc498006f661a8ab80a1eb75cd49ced210156','19669f1e02c2f16705ec7587044c66443be70725','jpg','image/jpeg','maxim-berg-9XunOfueKKI-unsplash.jpg','4ef2566b387a01c11228983cf1ac0a94387cd6cf',350748,1750661151,1746170231),(7,0,1701439957,1701439957,0,1,1,0,'/Testcases/DeletedFile_2d.txt','830c25db115611e7cf3c50639e49844fc4e7134e','2295e52c555d83687f1960f8f94e221981b10ec2','txt','text/plain','DeletedFile_2d.txt','6357bf3ec8fa292eeea3237c14918a209985e304',12,1750661151,1746170231),(8,0,1701439957,1701439957,0,1,1,0,'/Testcases/File_2b.txt','9cd8b53dcced9229b600c6cee59dd101f013cc87','2295e52c555d83687f1960f8f94e221981b10ec2','txt','text/plain','File_2b.txt','e6a2301e063c892c7b4b424dfb952845600e32e6',12,1750661151,1746170231),(11,0,1701439956,1701439957,0,1,1,0,'/Testcases/ChangedFile_2h.txt','a38c2834111f575d8fcc526235799b3fc7dfef2f','2295e52c555d83687f1960f8f94e221981b10ec2','txt','text/plain','ChangedFile_2h.txt','e097d61e0e0cff4c8f1a17a9aa957eb09fbc436f',22,1750661151,1746170231),(14,0,1701693847,1701693847,0,1,2,0,'/Testcases/2b_published_file/bds-photo-1523151-unsplash.jpg','c37de962d260f929f212169798e072a4c2363c48','fe8e2252551ce6a2996d4414b8ce3f870a23a4e4','jpg','image/jpeg','bds-photo-1523151-unsplash.jpg','01dd855ad55591512bdab0921775b8fb3be80e29',549411,1750661151,1746170231),(15,0,1701696609,1701696609,0,1,1,0,'/Testcases/2c_source_folder/MovedFile_2c.txt','eefb1c1bf264db0e14bc3ee9b544c643fcd01608','f49899bec27e911b933b3371442fa505e657d2a6','txt','text/plain','MovedFile_2c.txt','39dbf8c1cacdce81bea7e8acfdc54e0f99efb688',10,1750661151,1746170231),(16,0,1701698345,1701698346,0,1,1,0,'/Testcases/2d_deleted_file/2d_deleted_file.txt','a3a16239b08c1c0d1486fecb1103f57758ec2d62','d0064688efea3cad0e505672757a7e0c3b10606d','txt','text/plain','2d_deleted_file.txt','78f639acc5f90f1d73a8b096b583876582c77660',12,1750661151,1746170231),(18,0,1701699520,1701699520,0,1,1,0,'/Testcases/2g_moved_folder_with_file/MovedFileInFolder_2g.txt','e1f8a626395f566b3cc8526ab5272e5288ff4488','6adc12fef140bc7a5c3dc3c015396cb9ef06cfe2','txt','text/plain','MovedFileInFolder_2g.txt','db00fa1fe7f173569674dc841868d34bd161ab7e',20,1750661151,1746170231);
/*!40000 ALTER TABLE `sys_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_file_collection`
--

LOCK TABLES `sys_file_collection` WRITE;
/*!40000 ALTER TABLE `sys_file_collection` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_file_collection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_file_metadata`
--

LOCK TABLES `sys_file_metadata` WRITE;
/*!40000 ALTER TABLE `sys_file_metadata` DISABLE KEYS */;
INSERT INTO `sys_file_metadata` VALUES (2,0,1696487911,1696487911,0,0,NULL,'',0,0,0,0,2,NULL,4076,2712,NULL,NULL,0),(3,0,1696487911,1696487911,0,0,NULL,'',0,0,0,0,3,NULL,2582,3227,NULL,NULL,0),(4,0,1696487911,1696487911,0,0,NULL,'',0,0,0,0,4,NULL,3506,2504,NULL,NULL,0),(5,0,1697025237,1697025237,0,0,NULL,'',0,0,0,0,5,NULL,2160,3840,NULL,NULL,0),(6,0,1701439956,1701439956,0,0,NULL,'',0,0,0,0,6,NULL,0,0,NULL,NULL,0),(7,0,1701439956,1701439956,0,0,NULL,'',0,0,0,0,7,NULL,0,0,NULL,NULL,0),(8,0,1701439956,1701439956,0,0,NULL,'',0,0,0,0,8,NULL,0,0,NULL,NULL,0),(14,0,1701693846,1701693846,0,0,NULL,'',0,0,0,0,14,NULL,2663,3329,NULL,NULL,0),(15,0,1701696609,1701696609,0,0,NULL,'',0,0,0,0,15,NULL,0,0,NULL,NULL,0),(16,0,1701698339,1701698339,0,0,NULL,'',0,0,0,0,16,NULL,0,0,NULL,NULL,0),(18,0,1701699519,1701699519,0,0,NULL,'',0,0,0,0,18,NULL,0,0,NULL,NULL,0);
/*!40000 ALTER TABLE `sys_file_metadata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_file_reference`
--

LOCK TABLES `sys_file_reference` WRITE;
/*!40000 ALTER TABLE `sys_file_reference` DISABLE KEYS */;
INSERT INTO `sys_file_reference` VALUES (1,26,1697026206,1696487930,1,0,0,0,NULL,_binary '{\"alternative\":\"\",\"description\":\"\",\"link\":\"\",\"title\":\"\",\"crop\":\"\",\"uid_local\":\"\",\"hidden\":\"\",\"sys_language_uid\":\"\"}',0,0,0,0,2,16,'tt_content','image',1,NULL,NULL,NULL,'','{\"default\":{\"cropArea\":{\"x\":0,\"y\":0,\"width\":1,\"height\":1},\"selectedRatio\":\"NaN\",\"focusArea\":null}}',0,0),(2,33,1697023700,1697023608,1,0,0,0,NULL,'',0,0,0,0,2,18,'tt_content','image',1,NULL,NULL,NULL,'','{\"default\":{\"cropArea\":{\"x\":0,\"y\":0,\"width\":1,\"height\":1},\"selectedRatio\":\"NaN\",\"focusArea\":null}}',0,0),(3,33,1697025243,1697023700,1,0,0,0,NULL,_binary '{\"alternative\":\"\",\"description\":\"\",\"link\":\"\",\"title\":\"\",\"crop\":\"\",\"uid_local\":\"\",\"hidden\":\"\",\"sys_language_uid\":\"\"}',0,0,0,0,3,18,'tt_content','image',1,NULL,NULL,NULL,'','{\"default\":{\"cropArea\":{\"x\":0,\"y\":0,\"width\":1,\"height\":1},\"selectedRatio\":\"NaN\",\"focusArea\":null}}',0,0),(4,33,1697025259,1697025243,1,0,0,0,NULL,'',0,0,0,0,4,18,'tt_content','image',1,NULL,NULL,NULL,'','{\"default\":{\"cropArea\":{\"x\":0,\"y\":0,\"width\":1,\"height\":1},\"selectedRatio\":\"NaN\",\"focusArea\":null}}',0,0),(5,33,1697025578,1697025243,1,0,0,0,NULL,_binary '{\"alternative\":\"\",\"description\":\"\",\"link\":\"\",\"title\":\"\",\"crop\":\"\",\"uid_local\":\"\",\"hidden\":\"\",\"sys_language_uid\":\"\"}',0,0,0,0,5,18,'tt_content','image',1,NULL,NULL,NULL,'','{\"default\":{\"cropArea\":{\"x\":0,\"y\":0,\"width\":1,\"height\":1},\"selectedRatio\":\"NaN\",\"focusArea\":null}}',0,0),(6,33,1697025655,1697025578,1,0,0,0,NULL,'',0,0,0,0,3,18,'tt_content','image',1,NULL,NULL,NULL,'','{\"default\":{\"cropArea\":{\"x\":0,\"y\":0,\"width\":1,\"height\":1},\"selectedRatio\":\"NaN\",\"focusArea\":null}}',0,0),(7,33,1698750513,1697025655,0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,4,18,'tt_content','image',1,NULL,NULL,NULL,'','{\"default\":{\"cropArea\":{\"x\":0,\"y\":0,\"width\":1,\"height\":1},\"selectedRatio\":\"NaN\",\"focusArea\":null}}',0,0),(8,26,1697027588,1697026206,1,0,0,0,NULL,_binary '{\"alternative\":\"\",\"description\":\"\",\"link\":\"\",\"title\":\"\",\"crop\":\"\",\"uid_local\":\"\",\"hidden\":\"\",\"sys_language_uid\":\"\"}',0,0,0,0,5,16,'tt_content','image',1,NULL,NULL,NULL,'','{\"default\":{\"cropArea\":{\"x\":0,\"y\":0,\"width\":1,\"height\":1},\"selectedRatio\":\"NaN\",\"focusArea\":null}}',0,0),(9,26,1697027528,1697027409,1,0,0,0,NULL,'',0,0,0,0,4,19,'tt_content','image',1,NULL,NULL,NULL,'','{\"default\":{\"cropArea\":{\"x\":0,\"y\":0,\"width\":1,\"height\":1},\"selectedRatio\":\"NaN\",\"focusArea\":null}}',0,0),(10,26,1697028376,1697027588,0,0,0,0,NULL,_binary '{\"alternative\":\"\",\"description\":\"\",\"link\":\"\",\"title\":\"\",\"crop\":\"\",\"uid_local\":\"\",\"hidden\":\"\",\"sys_language_uid\":\"\"}',0,0,0,0,3,16,'tt_content','image',1,NULL,NULL,NULL,'','{\"default\":{\"cropArea\":{\"x\":0,\"y\":0,\"width\":1,\"height\":1},\"selectedRatio\":\"NaN\",\"focusArea\":null}}',0,0),(18,44,1758555102,1758555078,0,0,0,0,NULL,_binary '{\"hidden\":\"\"}',0,0,0,0,2,99,'tt_content','image',1,NULL,NULL,NULL,'','{\"default\":{\"cropArea\":{\"x\":0,\"y\":0,\"width\":1,\"height\":1},\"selectedRatio\":\"NaN\",\"focusArea\":null}}',0,0),(19,44,1758555095,1758555086,0,0,1,18,NULL,_binary '{\"alternative\":\"\",\"autoplay\":\"0\",\"crop\":\"{\\\"default\\\":{\\\"cropArea\\\":{\\\"x\\\":0,\\\"y\\\":0,\\\"width\\\":1,\\\"height\\\":1},\\\"selectedRatio\\\":\\\"NaN\\\",\\\"focusArea\\\":null}}\",\"description\":\"\",\"fieldname\":\"image\",\"hidden\":\"0\",\"link\":\"\",\"showinpreview\":\"0\",\"sorting_foreign\":\"1\",\"tablenames\":\"tt_content\",\"title\":\"\",\"uid_foreign\":\"99\",\"uid_local\":\"2\"}',0,0,0,0,2,100,'tt_content','image',1,NULL,NULL,NULL,'','{\"default\":{\"cropArea\":{\"x\":0,\"y\":0,\"width\":1,\"height\":1},\"selectedRatio\":\"NaN\",\"focusArea\":null}}',0,0);
/*!40000 ALTER TABLE `sys_file_reference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_file_storage`
--

LOCK TABLES `sys_file_storage` WRITE;
/*!40000 ALTER TABLE `sys_file_storage` DISABLE KEYS */;
INSERT INTO `sys_file_storage` VALUES (1,0,1695897191,1695897191,0,'This is the local fileadmin/ directory. This storage mount has been created automatically by TYPO3.','fileadmin','Local','<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"yes\" ?>\n<T3FlexForms>\n    <data>\n        <sheet index=\"sDEF\">\n            <language index=\"lDEF\">\n                <field index=\"basePath\">\n                    <value index=\"vDEF\">fileadmin/</value>\n                </field>\n                <field index=\"pathType\">\n                    <value index=\"vDEF\">relative</value>\n                </field>\n                <field index=\"caseSensitive\">\n                    <value index=\"vDEF\">1</value>\n                </field>\n            </language>\n        </sheet>\n    </data>\n</T3FlexForms>',1,1,1,1,1,1,NULL);
/*!40000 ALTER TABLE `sys_file_storage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_filemounts`
--

LOCK TABLES `sys_filemounts` WRITE;
/*!40000 ALTER TABLE `sys_filemounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_filemounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_news`
--

LOCK TABLES `sys_news` WRITE;
/*!40000 ALTER TABLE `sys_news` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_note`
--

LOCK TABLES `sys_note` WRITE;
/*!40000 ALTER TABLE `sys_note` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_note` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_preview`
--

LOCK TABLES `sys_preview` WRITE;
/*!40000 ALTER TABLE `sys_preview` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_preview` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_redirect`
--

LOCK TABLES `sys_redirect` WRITE;
/*!40000 ALTER TABLE `sys_redirect` DISABLE KEYS */;
INSERT INTO `sys_redirect` VALUES (19,1,1697536456,1697536456,0,0,0,0,'local.v12.in2publish.de','/extin2publish/8-treatremovedanddeletedasdifference',0,0,0,0,0,'t3://page?uid=39&_language=0',307,0,0,0,NULL,NULL,NULL,0,''),(20,1,1698753296,1698753296,0,0,0,0,'local.v12.in2publish.de','/19-singlerecordpublishing',0,0,0,0,0,'t3://page?uid=67&_language=0',307,0,0,0,NULL,NULL,NULL,0,''),(21,1,1730361316,1730361316,0,0,0,0,'local.v12.in2publish.de','/extin2publish/24-page-with-categories',0,0,0,0,0,'t3://page?uid=76&_language=0',307,0,0,0,NULL,NULL,NULL,0,''),(22,1,1734364515,1734364515,0,0,0,0,'local.v12.in2publish.de','/de/extin2publish-core/de-11a-workflows-/-clc-/-languageindependentworkflows-page-properties-changed-1-1/translate-to-german-11h-visibility-settings',0,0,0,0,0,'t3://page?uid=82&_language=1',307,0,0,0,NULL,NULL,NULL,0,'');
/*!40000 ALTER TABLE `sys_redirect` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_registry`
--

LOCK TABLES `sys_registry` WRITE;
/*!40000 ALTER TABLE `sys_registry` DISABLE KEYS */;
INSERT INTO `sys_registry` VALUES (12,'tx_scheduler','lastRun',_binary 'a:3:{s:5:\"start\";i:1719995529;s:3:\"end\";i:1719995529;s:4:\"type\";s:6:\"manual\";}'),(14,'installUpdateRows','rowUpdatersDone',_binary 'a:1:{i:0;s:69:\"TYPO3\\CMS\\Install\\Updates\\RowUpdater\\SysRedirectRootPageMoveMigration\";}'),(15,'core','formProtectionSessionToken:1',_binary 's:64:\"f30190074f830c33d69fedfb49eaedbe2920da6637e95ddb77c49a795713d47f\";');
/*!40000 ALTER TABLE `sys_registry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_template`
--

LOCK TABLES `sys_template` WRITE;
/*!40000 ALTER TABLE `sys_template` DISABLE KEYS */;
INSERT INTO `sys_template` VALUES (1,1,1719999744,1660115842,0,0,0,0,0,'This is an Empty Site Package TypoScript template.\r\n\r\nFor each website you need a TypoScript template on the main page of your website (on the top level). For better maintenance all TypoScript should be extracted into external files via @import \'EXT:site_myproject/Configuration/TypoScript/setup.typoscript\'','Main TypoScript Rendering',1,1,'EXT:fluid_styled_content/Configuration/TypoScript/,EXT:fluid_styled_content/Configuration/TypoScript/Styling/,EXT:solr/Configuration/TypoScript/Solr/,EXT:solr/Configuration/TypoScript/BootstrapCss/,EXT:solr/Configuration/TypoScript/StyleSheets/,EXT:solr/Configuration/TypoScript/Examples/IndexQueueNews/,EXT:solr/Configuration/TypoScript/Examples/IndexQueueNewsContentElements/,EXT:solr/Configuration/TypoScript/Examples/IndexQueueTtNews/,EXT:solrfal/Configuration/TypoScript/Basic,EXT:fal_securedownload/Configuration/TypoScript,EXT:news/Configuration/TypoScript','','## SOLR\r\nconfig {\r\n    index_enable = 1\r\n}\r\npage = PAGE\r\npage.10 {\r\n    stdWrap.dataWrap = <!--TYPO3SEARCH_begin-->|<!--TYPO3SEARCH_end-->\r\n}\r\npage.10 = TEXT\r\npage.10.value (\r\n   <div style=\"width: 800px; margin: 15% auto;\">\r\n      <div style=\"width: 300px;\">\r\n        <svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 150 42\"><path d=\"M60.2 14.4v27h-3.8v-27h-6.7v-3.3h17.1v3.3h-6.6zm20.2 12.9v14h-3.9v-14l-7.7-16.2h4.1l5.7 12.2 5.7-12.2h3.9l-7.8 16.2zm19.5 2.6h-3.6v11.4h-3.8V11.1s3.7-.3 7.3-.3c6.6 0 8.5 4.1 8.5 9.4 0 6.5-2.3 9.7-8.4 9.7m.4-16c-2.4 0-4.1.3-4.1.3v12.6h4.1c2.4 0 4.1-1.6 4.1-6.3 0-4.4-1-6.6-4.1-6.6m21.5 27.7c-7.1 0-9-5.2-9-15.8 0-10.2 1.9-15.1 9-15.1s9 4.9 9 15.1c.1 10.6-1.8 15.8-9 15.8m0-27.7c-3.9 0-5.2 2.6-5.2 12.1 0 9.3 1.3 12.4 5.2 12.4 3.9 0 5.2-3.1 5.2-12.4 0-9.4-1.3-12.1-5.2-12.1m19.9 27.7c-2.1 0-5.3-.6-5.7-.7v-3.1c1 .2 3.7.7 5.6.7 2.2 0 3.6-1.9 3.6-5.2 0-3.9-.6-6-3.7-6H138V24h3.1c3.5 0 3.7-3.6 3.7-5.3 0-3.4-1.1-4.8-3.2-4.8-1.9 0-4.1.5-5.3.7v-3.2c.5-.1 3-.7 5.2-.7 4.4 0 7 1.9 7 8.3 0 2.9-1 5.5-3.3 6.3 2.6.2 3.8 3.1 3.8 7.3 0 6.6-2.5 9-7.3 9\"/><path fill=\"#FF8700\" d=\"M31.7 28.8c-.6.2-1.1.2-1.7.2-5.2 0-12.9-18.2-12.9-24.3 0-2.2.5-3 1.3-3.6C12 1.9 4.3 4.2 1.9 7.2 1.3 8 1 9.1 1 10.6c0 9.5 10.1 31 17.3 31 3.3 0 8.8-5.4 13.4-12.8M28.4.5c6.6 0 13.2 1.1 13.2 4.8 0 7.6-4.8 16.7-7.2 16.7-4.4 0-9.9-12.1-9.9-18.2C24.5 1 25.6.5 28.4.5\"/></svg>\r\n      </div>\r\n      <h4 style=\"font-family: sans-serif;\">Welcome to a default website made with <a href=\"https://typo3.org\">TYPO3</a></h4>\r\n   </div>\r\n)\r\npage.100 {\r\n  stdWrap.dataWrap = <!--TYPO3SEARCH_begin-->|<!--TYPO3SEARCH_end-->\r\n}\r\npage.100 = CONTENT\r\npage.100 {\r\n    table = tt_content\r\n    select {\r\n        orderBy = sorting\r\n        where = {#colPos}=0\r\n    }\r\n}\r\n\r\nplugin.tx_solr.index.enableFileIndexing.storageContext = 1\r\nplugin.tx_solr.index.queue.news.attachments.fields = fal_related_files, fal_media',NULL,0,0,0),(3,69,1700037665,1700037653,0,0,0,0,256,NULL,'+ext',0,0,NULL,NULL,'page.20 = COA\r\npage.20.10 = TEXT\r\npage.20.10.data = date:U\r\npage.20.10.strftime = %d.%m.%Y %H:%M:%S','',0,0,0),(4,468,1768232510,1741948624,1,0,0,0,256,NULL,'root styleguide frontend demo',1,3,'EXT:styleguide/Configuration/TypoScript','','','',0,0,0);
/*!40000 ALTER TABLE `sys_template` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_workspace`
--

LOCK TABLES `sys_workspace` WRITE;
/*!40000 ALTER TABLE `sys_workspace` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_workspace` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_workspace_stage`
--

LOCK TABLES `sys_workspace_stage` WRITE;
/*!40000 ALTER TABLE `sys_workspace_stage` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_workspace_stage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tt_content`
--

LOCK TABLES `tt_content` WRITE;
/*!40000 ALTER TABLE `tt_content` DISABLE KEYS */;
INSERT INTO `tt_content` VALUES (1,'',6,1695902329,1695902329,0,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'header','1b Header','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(2,'',7,1695905947,1695905947,0,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'header','5b Header','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(3,'',9,1695906833,1695906833,0,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'header','6b Header','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(4,'',13,1698224962,1696253456,1,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'header','Header EN','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(5,'',13,1698224962,1696253468,1,0,0,0,'',512,0,1,4,4,NULL,_binary '{\"CType\":\"header\",\"starttime\":\"0\",\"endtime\":\"0\",\"categories\":\"0\",\"l18n_parent\":\"0\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"bullets_type\":\"0\",\"colPos\":\"0\",\"date\":\"0\",\"header_layout\":\"0\",\"header_position\":\"\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"sectionIndex\":\"1\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"0\",\"rowDescription\":\"\",\"tstamp\":\"1696253456\",\"crdate\":\"1696253456\",\"hidden\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"header\":\"Header EN\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"subheader\":\"\",\"header_link\":\"\",\"image_zoom\":\"0\",\"linkToTop\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,'header','Header DE','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(6,'',13,1698224962,1696253515,1,0,0,0,'',384,0,2,4,4,NULL,_binary '{\"CType\":\"header\",\"starttime\":\"0\",\"endtime\":\"0\",\"categories\":\"0\",\"l18n_parent\":\"0\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"bullets_type\":\"0\",\"colPos\":\"0\",\"date\":\"0\",\"header_layout\":\"0\",\"header_position\":\"\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"sectionIndex\":\"1\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"0\",\"rowDescription\":\"\",\"tstamp\":\"1696253456\",\"crdate\":\"1696253456\",\"hidden\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"header\":\"Header EN\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"subheader\":\"\",\"header_link\":\"\",\"image_zoom\":\"0\",\"linkToTop\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,'header','Header JP','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(7,'',16,1697543468,1696253917,1,0,0,0,'',256,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'header','Header EN edited','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(8,'',16,1696253939,1696253930,1,0,0,0,'',128,0,1,0,0,NULL,'',0,0,0,0,'header','Header DE','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(9,'',16,1697543468,1696253946,1,0,0,0,'',512,0,1,7,7,NULL,_binary '{\"CType\":\"header\",\"starttime\":\"0\",\"endtime\":\"0\",\"categories\":\"0\",\"l18n_parent\":\"0\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"bullets_type\":\"0\",\"colPos\":\"0\",\"date\":\"0\",\"header_layout\":\"0\",\"header_position\":\"\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"sectionIndex\":\"1\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"0\",\"rowDescription\":\"\",\"tstamp\":\"1696253917\",\"crdate\":\"1696253917\",\"hidden\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"header\":\"Header EN edited\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"subheader\":\"\",\"header_link\":\"\",\"image_zoom\":\"0\",\"linkToTop\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,'header','Header DE edited','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(10,'',16,1696253987,1696253975,0,0,0,0,'',384,0,2,7,7,NULL,_binary '{\"CType\":\"header\",\"starttime\":\"0\",\"endtime\":\"0\",\"categories\":\"0\",\"l18n_parent\":\"0\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"bullets_type\":\"0\",\"colPos\":\"0\",\"date\":\"0\",\"header_layout\":\"0\",\"header_position\":\"\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"sectionIndex\":\"1\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"0\",\"rowDescription\":\"\",\"tstamp\":\"1696253917\",\"crdate\":\"1696253917\",\"hidden\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"header\":\"Header EN\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"subheader\":\"\",\"header_link\":\"\",\"image_zoom\":\"0\",\"linkToTop\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,'header','Header JP','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(11,'',22,1697014937,1696408834,0,0,0,0,'',256,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'header','Header EN','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(12,'',22,1697014951,1696408834,0,0,0,0,'',128,0,1,11,11,NULL,_binary '{\"CType\":\"header\",\"starttime\":\"0\",\"endtime\":\"0\",\"categories\":\"0\",\"l18n_parent\":\"0\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"bullets_type\":\"0\",\"colPos\":\"0\",\"date\":\"0\",\"header_layout\":\"0\",\"header_position\":\"\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"sectionIndex\":\"1\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"{\\\"CType\\\":\\\"\\\",\\\"colPos\\\":\\\"\\\",\\\"header\\\":\\\"\\\",\\\"header_layout\\\":\\\"\\\",\\\"header_position\\\":\\\"\\\",\\\"date\\\":\\\"\\\",\\\"header_link\\\":\\\"\\\",\\\"subheader\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"frame_class\\\":\\\"\\\",\\\"space_before_class\\\":\\\"\\\",\\\"space_after_class\\\":\\\"\\\",\\\"sectionIndex\\\":\\\"\\\",\\\"linkToTop\\\":\\\"\\\",\\\"sys_language_uid\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"7\",\"rowDescription\":\"\",\"tstamp\":\"1696408834\",\"crdate\":\"1696408834\",\"hidden\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"header\":\"Header EN\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"subheader\":\"\",\"header_link\":\"\",\"image_zoom\":\"0\",\"linkToTop\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"0\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,'header','Header DE','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(13,'',22,1697014944,1696408834,0,0,0,0,'',64,0,2,11,11,NULL,_binary '{\"CType\":\"header\",\"starttime\":\"0\",\"endtime\":\"0\",\"categories\":\"0\",\"l18n_parent\":\"0\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"bullets_type\":\"0\",\"colPos\":\"0\",\"date\":\"0\",\"header_layout\":\"0\",\"header_position\":\"\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"sectionIndex\":\"1\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"{\\\"CType\\\":\\\"\\\",\\\"colPos\\\":\\\"\\\",\\\"header\\\":\\\"\\\",\\\"header_layout\\\":\\\"\\\",\\\"header_position\\\":\\\"\\\",\\\"date\\\":\\\"\\\",\\\"header_link\\\":\\\"\\\",\\\"subheader\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"frame_class\\\":\\\"\\\",\\\"space_before_class\\\":\\\"\\\",\\\"space_after_class\\\":\\\"\\\",\\\"sectionIndex\\\":\\\"\\\",\\\"linkToTop\\\":\\\"\\\",\\\"sys_language_uid\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"7\",\"rowDescription\":\"\",\"tstamp\":\"1696408834\",\"crdate\":\"1696408834\",\"hidden\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"header\":\"Header EN\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"subheader\":\"\",\"header_link\":\"\",\"image_zoom\":\"0\",\"linkToTop\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"0\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,'header','Header JP','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(14,'',25,1696432957,1696432574,1,0,0,0,'',256,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'header','Solr Integration','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(15,'',25,1696433049,1696433049,0,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'text','Solr integration','','<p>Upon publishing of this page a new&nbsp;In2code\\In2publish\\Features\\SolrIntegration\\Domain\\Model\\Task\\SolrQueueItemTask will be created and executed. You will find a new entry in the table&nbsp;foreign.tx_in2code_in2publish_task on foreign.</p>',0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(16,'',26,1697028376,1696487898,0,0,0,0,'',1,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"bodytext\":\"\",\"image\":\"\",\"imagewidth\":\"\",\"imageheight\":\"\",\"imageborder\":\"\",\"imageorient\":\"\",\"imagecols\":\"\",\"image_zoom\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'textpic','9b about nate johnston','','',0,0,0,0,1,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,3,''),(18,'',33,1697025655,1697023578,0,0,0,0,'',1,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"bodytext\":\"\",\"image\":\"\",\"imagewidth\":\"\",\"imageheight\":\"\",\"imageborder\":\"\",\"imageorient\":\"\",\"imagecols\":\"\",\"image_zoom\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'textpic','Content element with image - edited','','',0,0,0,0,1,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,5,''),(19,'',26,1697027528,1697027393,1,0,0,0,'',1,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"bodytext\":\"\",\"image\":\"\",\"imagewidth\":\"\",\"imageheight\":\"\",\"imageborder\":\"\",\"imageorient\":\"\",\"imagecols\":\"\",\"image_zoom\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'textpic','9a new news about roman wimmers','','',0,0,0,0,1,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,4,''),(23,'',37,1697114945,1697114724,1,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'header','Header on published Page 5c.2','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(24,'',38,1697114952,1697114800,1,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'shortcut','Insert Record on Ready To Publish Page 5c.2.1','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','tt_content_23',NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(25,'',37,1697114973,1697114973,0,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'header','Header auf Parent Published 5c.2','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(26,'',37,1697115034,1697115004,1,0,0,0,'',512,0,0,0,0,NULL,'',0,0,0,0,'header','Test 1','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(27,'',38,1698236027,1697115096,1,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'shortcut','Insert Record on Child Ready To Publish 5c.2.1','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','tt_content_25',NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(30,'',44,1697544009,1697544009,0,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'header','Header EN','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(31,'',44,1697544060,1697544038,0,0,0,0,'',512,0,1,30,30,NULL,_binary '{\"CType\":\"header\",\"starttime\":\"0\",\"endtime\":\"0\",\"categories\":\"0\",\"l18n_parent\":\"0\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"bullets_type\":\"0\",\"colPos\":\"0\",\"date\":\"0\",\"header_layout\":\"0\",\"header_position\":\"\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"sectionIndex\":\"1\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"0\",\"rowDescription\":\"\",\"tstamp\":\"1697544009\",\"crdate\":\"1697544009\",\"hidden\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"header\":\"Header EN\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"subheader\":\"\",\"header_link\":\"\",\"image_zoom\":\"0\",\"linkToTop\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\"}',0,0,0,0,'header','Header DE','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'Translated in Connected Mode','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(32,'',43,1698750866,1697544285,0,1,0,0,'',256,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'header','Header EN','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'disabled','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(33,'',43,1697544334,1697544303,0,0,0,0,'',512,0,1,32,32,NULL,_binary '{\"CType\":\"header\",\"starttime\":\"0\",\"endtime\":\"0\",\"categories\":\"0\",\"l18n_parent\":\"0\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"bullets_type\":\"0\",\"colPos\":\"0\",\"date\":\"0\",\"header_layout\":\"0\",\"header_position\":\"\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"sectionIndex\":\"1\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"0\",\"rowDescription\":\"\",\"tstamp\":\"1697544285\",\"crdate\":\"1697544285\",\"hidden\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"header\":\"Header EN\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"subheader\":\"\",\"header_link\":\"\",\"image_zoom\":\"0\",\"linkToTop\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\"}',0,0,0,0,'header','Header DE','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'Translated in Connected Mode','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(34,'',51,1697551056,1697545050,0,0,0,0,'',256,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'header','Header EN','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(35,'',51,1697545084,1697545065,0,0,0,0,'',512,0,1,34,34,NULL,_binary '{\"CType\":\"header\",\"starttime\":\"0\",\"endtime\":\"0\",\"categories\":\"0\",\"l18n_parent\":\"0\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"bullets_type\":\"0\",\"colPos\":\"0\",\"date\":\"0\",\"header_layout\":\"0\",\"header_position\":\"\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"sectionIndex\":\"1\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"0\",\"rowDescription\":\"\",\"tstamp\":\"1697545050\",\"crdate\":\"1697545050\",\"hidden\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"header\":\"Header EN\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"subheader\":\"\",\"header_link\":\"\",\"image_zoom\":\"0\",\"linkToTop\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\"}',0,0,0,0,'header','Header DE','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'Translated in Connected Mode','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(36,'',51,1697550665,1697545099,0,0,0,0,'',384,0,2,34,34,NULL,_binary '{\"CType\":\"header\",\"starttime\":\"0\",\"endtime\":\"0\",\"categories\":\"0\",\"l18n_parent\":\"0\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"bullets_type\":\"0\",\"colPos\":\"0\",\"date\":\"0\",\"header_layout\":\"0\",\"header_position\":\"\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"sectionIndex\":\"1\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"0\",\"rowDescription\":\"\",\"tstamp\":\"1697545050\",\"crdate\":\"1697545050\",\"hidden\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"header\":\"Header EN\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"subheader\":\"\",\"header_link\":\"\",\"image_zoom\":\"0\",\"linkToTop\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\"}',0,0,0,0,'header','Header JP','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'Translated in Connected Mode','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(37,'',54,1697545388,1697545388,0,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'header','Header EN','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(38,'',54,1697545388,1697545388,0,0,0,0,'',128,0,1,37,37,NULL,_binary '{\"CType\":\"header\",\"starttime\":\"0\",\"endtime\":\"0\",\"categories\":\"0\",\"l18n_parent\":\"0\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"bullets_type\":\"0\",\"colPos\":\"0\",\"date\":\"0\",\"header_layout\":\"0\",\"header_position\":\"\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"sectionIndex\":\"1\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"34\",\"rowDescription\":\"\",\"tstamp\":\"1697545388\",\"crdate\":\"1697545388\",\"hidden\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"header\":\"Header EN\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"subheader\":\"\",\"header_link\":\"\",\"image_zoom\":\"0\",\"linkToTop\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"0\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\"}',0,0,0,0,'header','Header DE','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'Translated in Connected Mode','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(39,'',54,1697550730,1697545388,0,0,0,0,'',64,0,2,37,37,NULL,_binary '{\"CType\":\"header\",\"starttime\":\"0\",\"endtime\":\"0\",\"categories\":\"0\",\"l18n_parent\":\"0\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"bullets_type\":\"0\",\"colPos\":\"0\",\"date\":\"0\",\"header_layout\":\"0\",\"header_position\":\"\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"sectionIndex\":\"1\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"34\",\"rowDescription\":\"\",\"tstamp\":\"1697545388\",\"crdate\":\"1697545388\",\"hidden\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"header\":\"Header EN\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"subheader\":\"\",\"header_link\":\"\",\"image_zoom\":\"0\",\"linkToTop\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"0\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\"}',0,0,0,0,'header','Header JP','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'Translated in Connected Mode','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(40,'',58,1698233753,1697716993,1,0,0,0,'',256,0,0,0,0,NULL,_binary '{\"colPos\":\"\",\"sys_language_uid\":\"\"}',0,0,0,0,'header','Cut out header','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(41,'Old row description',59,1697727531,1697727494,0,0,0,0,'',256,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'header','Header with ignored field rowDescription','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'The field rowDescription is ignored by the publisher (Tab: Notes Field: Description)','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(42,'',60,1698048920,1698048903,1,1,0,0,'',256,0,0,0,0,NULL,_binary '{\"colPos\":\"\",\"sys_language_uid\":\"\"}',0,0,0,0,'header','1b Header - changed','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(43,'',60,1698236510,1698048960,1,0,0,0,'',256,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"records\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'shortcut','','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','tt_content_1',NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(46,'',57,1698233778,1698233778,0,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'header','Cut out header','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(49,'',65,1698311570,1698311567,0,0,0,0,'',256,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'header','1b.1 Header','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(50,'',66,1698311716,1698311716,0,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'shortcut','','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','tt_content_49',NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(51,'',67,1698753000,1698752979,0,0,0,0,'',256,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'header','Header','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(52,'',67,1698752992,1698752992,0,0,0,0,'',512,0,0,0,0,NULL,'',0,0,0,0,'text','Text element','','',0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(53,'',71,1700506817,1700506817,0,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'header','Header in English','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(54,'',71,1700555099,1700506833,0,0,0,0,'',512,0,1,0,53,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"l18n_parent\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'header','Header in German - Version 3','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(55,'',74,1700507045,1700507045,0,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'header','Header in English','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(56,'',74,1700507971,1700507061,0,0,0,0,'',512,0,1,55,55,NULL,_binary '{\"CType\":\"header\",\"starttime\":\"0\",\"endtime\":\"0\",\"categories\":\"0\",\"l18n_parent\":\"0\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"bullets_type\":\"0\",\"colPos\":\"0\",\"date\":\"0\",\"header_layout\":\"0\",\"header_position\":\"\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"sectionIndex\":\"1\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"0\",\"rowDescription\":\"\",\"tstamp\":\"1700507045\",\"crdate\":\"1700507045\",\"hidden\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"sys_language_uid\":\"0\",\"l10n_source\":\"0\",\"header\":\"Header in English\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"subheader\":\"\",\"header_link\":\"\",\"image_zoom\":\"0\",\"linkToTop\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,'header','Header in German - Version 2','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(57,'',77,1730279247,1730279247,0,0,0,0,'',256,0,0,0,0,NULL,'',0,0,0,0,'header','Header Default','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(62,'',82,1734364494,1734364390,0,0,1733071980,0,'',256,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'header','EN Header with startdate','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'Start date of header changed in default language is published in translation','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(63,'',82,1734364569,1734364554,0,0,1733071980,0,'',512,0,1,62,62,NULL,_binary '{\"CType\":\"header\",\"colPos\":\"0\",\"header\":\"EN Header with startdate\",\"header_layout\":\"0\",\"header_position\":\"\",\"date\":\"0\",\"header_link\":\"\",\"subheader\":\"Start date of header changed in default language is published in translation\",\"layout\":\"0\",\"frame_class\":\"default\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"1\",\"linkToTop\":\"0\",\"sys_language_uid\":\"0\",\"hidden\":\"0\",\"starttime\":\"1733071980\",\"endtime\":\"0\",\"fe_group\":\"\",\"editlock\":\"0\",\"categories\":\"0\",\"rowDescription\":\"\",\"l18n_parent\":\"0\",\"bullets_type\":\"0\",\"imagewidth\":\"0\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagecols\":\"2\",\"cols\":\"0\",\"recursive\":\"0\",\"list_type\":\"\",\"target\":\"\",\"accessibility_title\":\"\",\"accessibility_bypass_text\":\"\",\"l18n_diffsource\":\"{\\\"CType\\\":\\\"\\\",\\\"colPos\\\":\\\"\\\",\\\"header\\\":\\\"\\\",\\\"header_layout\\\":\\\"\\\",\\\"header_position\\\":\\\"\\\",\\\"date\\\":\\\"\\\",\\\"header_link\\\":\\\"\\\",\\\"subheader\\\":\\\"\\\",\\\"layout\\\":\\\"\\\",\\\"frame_class\\\":\\\"\\\",\\\"space_before_class\\\":\\\"\\\",\\\"space_after_class\\\":\\\"\\\",\\\"sectionIndex\\\":\\\"\\\",\\\"linkToTop\\\":\\\"\\\",\\\"sys_language_uid\\\":\\\"\\\",\\\"hidden\\\":\\\"\\\",\\\"starttime\\\":\\\"\\\",\\\"endtime\\\":\\\"\\\",\\\"fe_group\\\":\\\"\\\",\\\"editlock\\\":\\\"\\\",\\\"categories\\\":\\\"\\\",\\\"rowDescription\\\":\\\"\\\"}\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"uploads_description\":\"0\",\"uploads_type\":\"0\",\"t3_origuid\":\"0\",\"tstamp\":\"1734364494\",\"crdate\":\"1734364390\",\"l10n_source\":\"0\",\"bodytext\":\"\",\"assets\":\"0\",\"image\":\"0\",\"imageborder\":\"0\",\"media\":\"0\",\"records\":\"\",\"pages\":\"\",\"image_zoom\":\"0\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"pi_flexform\":\"\",\"accessibility_bypass\":\"0\",\"category_field\":\"\",\"table_caption\":\"\",\"selected_categories\":\"\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\",\"tx_styleguide_containsdemo\":\"\"}',0,0,0,0,'header','DE Header with startdate','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','','','',0,'Start date of header changed in default language is published in translation','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,''),(64,NULL,468,1768232510,1741948624,1,0,0,0,'0',256,0,0,0,0,NULL,'',0,0,0,0,'text','TYPO3 Styleguide Frontend','','This is the generated frontend for the Styleguide Extension. This consists of all default content elements of the TYPO3 Core.',0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,'tx_styleguide_frontend'),(99,'',44,1758555102,1758555078,0,0,0,0,'',384,0,0,0,0,NULL,_binary '{\"CType\":\"\",\"bodytext\":\"\",\"categories\":\"\",\"colPos\":\"\",\"date\":\"\",\"editlock\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"frame_class\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_link\":\"\",\"header_position\":\"\",\"hidden\":\"\",\"image\":\"\",\"image_zoom\":\"\",\"imageborder\":\"\",\"imagecols\":\"\",\"imageheight\":\"\",\"imageorient\":\"\",\"imagewidth\":\"\",\"layout\":\"\",\"linkToTop\":\"\",\"rowDescription\":\"\",\"sectionIndex\":\"\",\"space_after_class\":\"\",\"space_before_class\":\"\",\"starttime\":\"\",\"subheader\":\"\"}',0,0,0,0,'textpic','EN','','',0,0,0,0,1,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,0,'',1,0,NULL,0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,NULL,0,0,''),(100,'',44,1758555095,1758555086,0,0,0,0,'',768,0,1,99,99,NULL,_binary '{\"CType\":\"textpic\",\"assets\":\"0\",\"bodytext\":\"\",\"bullets_type\":\"0\",\"categories\":\"0\",\"category_field\":\"\",\"colPos\":\"0\",\"cols\":\"0\",\"crdate\":\"1758555078\",\"date\":\"0\",\"editlock\":\"0\",\"endtime\":\"0\",\"fe_group\":\"\",\"file_collections\":\"\",\"filelink_size\":\"0\",\"filelink_sorting\":\"\",\"filelink_sorting_direction\":\"\",\"frame_class\":\"default\",\"header\":\"\",\"header_layout\":\"0\",\"header_link\":\"\",\"header_position\":\"\",\"hidden\":\"0\",\"image\":\"1\",\"image_zoom\":\"0\",\"imageborder\":\"0\",\"imagecols\":\"2\",\"imageheight\":\"0\",\"imageorient\":\"0\",\"imagewidth\":\"0\",\"l10n_source\":\"0\",\"layout\":\"0\",\"linkToTop\":\"0\",\"list_type\":\"\",\"media\":\"0\",\"pages\":\"\",\"pi_flexform\":\"\",\"records\":\"\",\"recursive\":\"0\",\"rowDescription\":\"\",\"sectionIndex\":\"1\",\"selected_categories\":\"\",\"space_after_class\":\"\",\"space_before_class\":\"\",\"starttime\":\"0\",\"subheader\":\"\",\"table_caption\":\"\",\"table_class\":\"\",\"table_delimiter\":\"124\",\"table_enclosure\":\"0\",\"table_header_position\":\"0\",\"table_tfoot\":\"0\",\"target\":\"\",\"tstamp\":\"1758555078\",\"tx_impexp_origuid\":\"0\",\"tx_news_related_news\":\"0\",\"tx_styleguide_containsdemo\":\"\",\"uploads_description\":\"0\",\"uploads_type\":\"0\"}',0,0,0,0,'textpic','DE','','',0,0,0,0,1,0,0,2,0,0,0,'default',0,'','','','',0,'','',0,0,'',1,0,'',0,'','','',0,0,0,NULL,'','',NULL,124,0,0,0,0,'0',0,0,'');
/*!40000 ALTER TABLE `tt_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_extensionmanager_domain_model_extension`
--

LOCK TABLES `tx_extensionmanager_domain_model_extension` WRITE;
/*!40000 ALTER TABLE `tx_extensionmanager_domain_model_extension` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_extensionmanager_domain_model_extension` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_impexp_presets`
--

LOCK TABLES `tx_impexp_presets` WRITE;
/*!40000 ALTER TABLE `tx_impexp_presets` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_impexp_presets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_in2publish_workflow_history`
--

LOCK TABLES `tx_in2publish_workflow_history` WRITE;
/*!40000 ALTER TABLE `tx_in2publish_workflow_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_in2publish_workflow_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_in2publish_workflow_state`
--

LOCK TABLES `tx_in2publish_workflow_state` WRITE;
/*!40000 ALTER TABLE `tx_in2publish_workflow_state` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_in2publish_workflow_state` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_linkvalidator_link`
--

LOCK TABLES `tx_linkvalidator_link` WRITE;
/*!40000 ALTER TABLE `tx_linkvalidator_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_linkvalidator_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_news_domain_model_link`
--

LOCK TABLES `tx_news_domain_model_link` WRITE;
/*!40000 ALTER TABLE `tx_news_domain_model_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_news_domain_model_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_news_domain_model_news`
--

LOCK TABLES `tx_news_domain_model_news` WRITE;
/*!40000 ALTER TABLE `tx_news_domain_model_news` DISABLE KEYS */;
INSERT INTO `tx_news_domain_model_news` VALUES (1,207,1690983868,1690983868,0,0,0,0,0,0,0,0,'',0,0,0,0,0,'','',NULL,0,'News 1 ready to publish','Teaser News 1 ready to publish','<p>Text&nbsp;News 1 ready to publish</p>',1690983771,0,'','',0,0,0,NULL,0,0,'0','','',0,NULL,1,NULL,NULL,0,0,'news-1-ready-to-publish','','',0.5,'',''),(3,26,1697026127,1696487898,0,0,0,0,0,0,0,0,_binary '{\"type\":\"\",\"istopnews\":\"\",\"title\":\"\",\"path_segment\":\"\",\"teaser\":\"\",\"datetime\":\"\",\"archive\":\"\",\"bodytext\":\"\",\"content_elements\":\"\",\"fal_media\":\"\",\"fal_related_files\":\"\",\"categories\":\"\",\"related\":\"\",\"related_links\":\"\",\"tags\":\"\",\"author\":\"\",\"author_email\":\"\",\"keywords\":\"\",\"description\":\"\",\"alternative_title\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"notes\":\"\"}',0,0,0,0,0,'','',NULL,0,'9b news with changed content','News with changed header and changed content (header and replaced image).','',1696487870,0,'','',0,0,0,NULL,0,0,'0','','',0,NULL,0,NULL,NULL,0,1,'news-1','','',0.5,'',''),(4,26,1697027528,1696488613,0,0,0,0,0,0,0,0,_binary '{\"type\":\"\",\"istopnews\":\"\",\"title\":\"\",\"path_segment\":\"\",\"teaser\":\"\",\"datetime\":\"\",\"archive\":\"\",\"bodytext\":\"\",\"content_elements\":\"\",\"fal_media\":\"\",\"fal_related_files\":\"\",\"categories\":\"\",\"related\":\"\",\"related_links\":\"\",\"tags\":\"\",\"author\":\"\",\"author_email\":\"\",\"keywords\":\"\",\"description\":\"\",\"alternative_title\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"notes\":\"\"}',0,1,0,0,0,'','',NULL,0,'13b new news','Fantastic new news teaser','<p>Fantastic new news text</p>',1696488589,0,'','',0,0,0,NULL,0,0,'0','','',0,NULL,0,NULL,NULL,0,0,'13b-new-news','','',0.5,'',''),(5,33,1698750513,1697023553,0,0,0,0,0,0,0,0,_binary '{\"type\":\"\",\"istopnews\":\"\",\"title\":\"\",\"path_segment\":\"\",\"teaser\":\"\",\"datetime\":\"\",\"archive\":\"\",\"bodytext\":\"\",\"content_elements\":\"\",\"fal_media\":\"\",\"fal_related_files\":\"\",\"categories\":\"\",\"related\":\"\",\"related_links\":\"\",\"tags\":\"\",\"author\":\"\",\"author_email\":\"\",\"keywords\":\"\",\"description\":\"\",\"alternative_title\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"notes\":\"\"}',0,0,0,0,0,'','',NULL,0,'1c news with changed content','','',1697023537,0,'','',0,0,0,NULL,0,0,'0','','',0,NULL,0,NULL,NULL,0,1,'11c-news-with-changed-content','','',0.5,'','');
/*!40000 ALTER TABLE `tx_news_domain_model_news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_news_domain_model_news_related_mm`
--

LOCK TABLES `tx_news_domain_model_news_related_mm` WRITE;
/*!40000 ALTER TABLE `tx_news_domain_model_news_related_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_news_domain_model_news_related_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_news_domain_model_news_tag_mm`
--

LOCK TABLES `tx_news_domain_model_news_tag_mm` WRITE;
/*!40000 ALTER TABLE `tx_news_domain_model_news_tag_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_news_domain_model_news_tag_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_news_domain_model_news_ttcontent_mm`
--

LOCK TABLES `tx_news_domain_model_news_ttcontent_mm` WRITE;
/*!40000 ALTER TABLE `tx_news_domain_model_news_ttcontent_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_news_domain_model_news_ttcontent_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_news_domain_model_tag`
--

LOCK TABLES `tx_news_domain_model_tag` WRITE;
/*!40000 ALTER TABLE `tx_news_domain_model_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_news_domain_model_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_scheduler_task`
--

LOCK TABLES `tx_scheduler_task` WRITE;
/*!40000 ALTER TABLE `tx_scheduler_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_scheduler_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_scheduler_task_group`
--

LOCK TABLES `tx_scheduler_task_group` WRITE;
/*!40000 ALTER TABLE `tx_scheduler_task_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_scheduler_task_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_ctrl_common`
--

LOCK TABLES `tx_styleguide_ctrl_common` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_ctrl_common` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_ctrl_common` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_ctrl_minimal`
--

LOCK TABLES `tx_styleguide_ctrl_minimal` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_ctrl_minimal` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_ctrl_minimal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_displaycond`
--

LOCK TABLES `tx_styleguide_displaycond` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_displaycond` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_displaycond` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_element_group_group_13_mm`
--

LOCK TABLES `tx_styleguide_element_group_group_13_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_element_group_group_13_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_element_group_group_13_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_basic`
--

LOCK TABLES `tx_styleguide_elements_basic` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_basic` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_basic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_folder`
--

LOCK TABLES `tx_styleguide_elements_folder` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_folder` DISABLE KEYS */;
INSERT INTO `tx_styleguide_elements_folder` VALUES (1,112,1741949039,1741945243,1,0,0,0,0,0,NULL,_binary '{\"folder_1\":\"\",\"folder_2\":\"\",\"folder_3\":\"\",\"flex_1\":\"\",\"sys_language_uid\":\"\"}',0,0,0,0,'1:/Testcases/2b_published_file/,1:/Testcases/2d_deleted_file/','','','<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"yes\" ?>\n<T3FlexForms>\n    <data>\n        <sheet index=\"sDb\">\n            <language index=\"lDEF\">\n                <field index=\"folder_1\">\n                    <value index=\"vDEF\"></value>\n                </field>\n            </language>\n        </sheet>\n    </data>\n</T3FlexForms>');
/*!40000 ALTER TABLE `tx_styleguide_elements_folder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_group`
--

LOCK TABLES `tx_styleguide_elements_group` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_imagemanipulation`
--

LOCK TABLES `tx_styleguide_elements_imagemanipulation` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_imagemanipulation` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_imagemanipulation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_rte`
--

LOCK TABLES `tx_styleguide_elements_rte` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_rte` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_rte` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_rte_flex_1_inline_1_child`
--

LOCK TABLES `tx_styleguide_elements_rte_flex_1_inline_1_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_rte_flex_1_inline_1_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_rte_flex_1_inline_1_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_rte_inline_1_child`
--

LOCK TABLES `tx_styleguide_elements_rte_inline_1_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_rte_inline_1_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_rte_inline_1_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_select`
--

LOCK TABLES `tx_styleguide_elements_select` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_select` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_select` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_select_flex_1_multiplesidebyside_2_mm`
--

LOCK TABLES `tx_styleguide_elements_select_flex_1_multiplesidebyside_2_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_select_flex_1_multiplesidebyside_2_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_select_flex_1_multiplesidebyside_2_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_select_multiplesidebyside_8_mm`
--

LOCK TABLES `tx_styleguide_elements_select_multiplesidebyside_8_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_select_multiplesidebyside_8_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_select_multiplesidebyside_8_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_select_single_12_foreign`
--

LOCK TABLES `tx_styleguide_elements_select_single_12_foreign` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_select_single_12_foreign` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_select_single_12_foreign` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_select_single_15_mm`
--

LOCK TABLES `tx_styleguide_elements_select_single_15_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_select_single_15_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_select_single_15_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_select_single_21_foreign`
--

LOCK TABLES `tx_styleguide_elements_select_single_21_foreign` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_select_single_21_foreign` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_select_single_21_foreign` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_slugs`
--

LOCK TABLES `tx_styleguide_elements_slugs` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_slugs` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_slugs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_t3editor`
--

LOCK TABLES `tx_styleguide_elements_t3editor` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_t3editor` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_t3editor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_t3editor_flex_1_inline_1_child`
--

LOCK TABLES `tx_styleguide_elements_t3editor_flex_1_inline_1_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_t3editor_flex_1_inline_1_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_t3editor_flex_1_inline_1_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_elements_t3editor_inline_1_child`
--

LOCK TABLES `tx_styleguide_elements_t3editor_inline_1_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_elements_t3editor_inline_1_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_elements_t3editor_inline_1_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_file`
--

LOCK TABLES `tx_styleguide_file` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_flex`
--

LOCK TABLES `tx_styleguide_flex` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_flex` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_flex` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_flex_flex_3_inline_1_child`
--

LOCK TABLES `tx_styleguide_flex_flex_3_inline_1_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_flex_flex_3_inline_1_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_flex_flex_3_inline_1_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_11`
--

LOCK TABLES `tx_styleguide_inline_11` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_11` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_11` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_11_child`
--

LOCK TABLES `tx_styleguide_inline_11_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_11_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_11_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_1n`
--

LOCK TABLES `tx_styleguide_inline_1n` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_1n` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_1n` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_1n1n`
--

LOCK TABLES `tx_styleguide_inline_1n1n` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_1n1n` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_1n1n` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_1n1n_child`
--

LOCK TABLES `tx_styleguide_inline_1n1n_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_1n1n_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_1n1n_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_1n1n_childchild`
--

LOCK TABLES `tx_styleguide_inline_1n1n_childchild` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_1n1n_childchild` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_1n1n_childchild` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_1n_inline_1_child`
--

LOCK TABLES `tx_styleguide_inline_1n_inline_1_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_1n_inline_1_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_1n_inline_1_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_1n_inline_2_child`
--

LOCK TABLES `tx_styleguide_inline_1n_inline_2_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_1n_inline_2_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_1n_inline_2_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_1nnol10n`
--

LOCK TABLES `tx_styleguide_inline_1nnol10n` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_1nnol10n` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_1nnol10n` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_1nnol10n_child`
--

LOCK TABLES `tx_styleguide_inline_1nnol10n_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_1nnol10n_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_1nnol10n_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_1nreusabletable`
--

LOCK TABLES `tx_styleguide_inline_1nreusabletable` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_1nreusabletable` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_1nreusabletable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_1nreusabletable_child`
--

LOCK TABLES `tx_styleguide_inline_1nreusabletable_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_1nreusabletable_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_1nreusabletable_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_expand`
--

LOCK TABLES `tx_styleguide_inline_expand` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_expand` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_expand` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_expand_inline_1_child`
--

LOCK TABLES `tx_styleguide_inline_expand_inline_1_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_expand_inline_1_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_expand_inline_1_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_expandsingle`
--

LOCK TABLES `tx_styleguide_inline_expandsingle` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_expandsingle` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_expandsingle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_expandsingle_child`
--

LOCK TABLES `tx_styleguide_inline_expandsingle_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_expandsingle_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_expandsingle_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_expandsingle_inline_2_child`
--

LOCK TABLES `tx_styleguide_inline_expandsingle_inline_2_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_expandsingle_inline_2_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_expandsingle_inline_2_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_foreignrecorddefaults`
--

LOCK TABLES `tx_styleguide_inline_foreignrecorddefaults` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_foreignrecorddefaults` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_foreignrecorddefaults` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_foreignrecorddefaults_child`
--

LOCK TABLES `tx_styleguide_inline_foreignrecorddefaults_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_foreignrecorddefaults_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_foreignrecorddefaults_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mm`
--

LOCK TABLES `tx_styleguide_inline_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mm_child`
--

LOCK TABLES `tx_styleguide_inline_mm_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mm_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mm_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mm_child_childchild_rel`
--

LOCK TABLES `tx_styleguide_inline_mm_child_childchild_rel` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mm_child_childchild_rel` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mm_child_childchild_rel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mm_child_rel`
--

LOCK TABLES `tx_styleguide_inline_mm_child_rel` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mm_child_rel` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mm_child_rel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mm_childchild`
--

LOCK TABLES `tx_styleguide_inline_mm_childchild` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mm_childchild` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mm_childchild` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mn`
--

LOCK TABLES `tx_styleguide_inline_mn` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mn` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mn_child`
--

LOCK TABLES `tx_styleguide_inline_mn_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mn_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mn_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mn_mm`
--

LOCK TABLES `tx_styleguide_inline_mn_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mn_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mn_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mngroup`
--

LOCK TABLES `tx_styleguide_inline_mngroup` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mngroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mngroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mngroup_child`
--

LOCK TABLES `tx_styleguide_inline_mngroup_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mngroup_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mngroup_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mngroup_mm`
--

LOCK TABLES `tx_styleguide_inline_mngroup_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mngroup_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mngroup_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mnsymmetric`
--

LOCK TABLES `tx_styleguide_inline_mnsymmetric` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mnsymmetric` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mnsymmetric` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mnsymmetric_mm`
--

LOCK TABLES `tx_styleguide_inline_mnsymmetric_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mnsymmetric_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mnsymmetric_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mnsymmetricgroup`
--

LOCK TABLES `tx_styleguide_inline_mnsymmetricgroup` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mnsymmetricgroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mnsymmetricgroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_mnsymmetricgroup_mm`
--

LOCK TABLES `tx_styleguide_inline_mnsymmetricgroup_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_mnsymmetricgroup_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_mnsymmetricgroup_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_overridechildtca`
--

LOCK TABLES `tx_styleguide_inline_overridechildtca` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_overridechildtca` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_overridechildtca` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_overridechildtca_child`
--

LOCK TABLES `tx_styleguide_inline_overridechildtca_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_overridechildtca_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_overridechildtca_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_parentnosoftdelete`
--

LOCK TABLES `tx_styleguide_inline_parentnosoftdelete` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_parentnosoftdelete` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_parentnosoftdelete` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_usecombination`
--

LOCK TABLES `tx_styleguide_inline_usecombination` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombination` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombination` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_usecombination_child`
--

LOCK TABLES `tx_styleguide_inline_usecombination_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombination_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombination_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_usecombination_mm`
--

LOCK TABLES `tx_styleguide_inline_usecombination_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombination_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombination_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_usecombinationbox`
--

LOCK TABLES `tx_styleguide_inline_usecombinationbox` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombinationbox` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombinationbox` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_usecombinationbox_child`
--

LOCK TABLES `tx_styleguide_inline_usecombinationbox_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombinationbox_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombinationbox_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_usecombinationbox_mm`
--

LOCK TABLES `tx_styleguide_inline_usecombinationbox_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombinationbox_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombinationbox_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_usecombinationgroup`
--

LOCK TABLES `tx_styleguide_inline_usecombinationgroup` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombinationgroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombinationgroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_usecombinationgroup_child`
--

LOCK TABLES `tx_styleguide_inline_usecombinationgroup_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombinationgroup_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombinationgroup_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_inline_usecombinationgroup_mm`
--

LOCK TABLES `tx_styleguide_inline_usecombinationgroup_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombinationgroup_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_inline_usecombinationgroup_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_l10nreadonly`
--

LOCK TABLES `tx_styleguide_l10nreadonly` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_l10nreadonly` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_l10nreadonly` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_l10nreadonly_group_mm`
--

LOCK TABLES `tx_styleguide_l10nreadonly_group_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_l10nreadonly_group_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_l10nreadonly_group_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_l10nreadonly_inline_child`
--

LOCK TABLES `tx_styleguide_l10nreadonly_inline_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_l10nreadonly_inline_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_l10nreadonly_inline_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_l10nreadonly_select_multiplesidebyside_mm`
--

LOCK TABLES `tx_styleguide_l10nreadonly_select_multiplesidebyside_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_l10nreadonly_select_multiplesidebyside_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_l10nreadonly_select_multiplesidebyside_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_l10nreadonly_select_tree_mm`
--

LOCK TABLES `tx_styleguide_l10nreadonly_select_tree_mm` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_l10nreadonly_select_tree_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_l10nreadonly_select_tree_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_palette`
--

LOCK TABLES `tx_styleguide_palette` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_palette` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_palette` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_required`
--

LOCK TABLES `tx_styleguide_required` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_required` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_required` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_required_file_1_child`
--

LOCK TABLES `tx_styleguide_required_file_1_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_required_file_1_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_required_file_1_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_required_flex_2_inline_1_child`
--

LOCK TABLES `tx_styleguide_required_flex_2_inline_1_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_required_flex_2_inline_1_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_required_flex_2_inline_1_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_required_inline_1_child`
--

LOCK TABLES `tx_styleguide_required_inline_1_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_required_inline_1_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_required_inline_1_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_required_inline_2_child`
--

LOCK TABLES `tx_styleguide_required_inline_2_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_required_inline_2_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_required_inline_2_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_required_inline_3_child`
--

LOCK TABLES `tx_styleguide_required_inline_3_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_required_inline_3_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_required_inline_3_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_required_rte_2_child`
--

LOCK TABLES `tx_styleguide_required_rte_2_child` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_required_rte_2_child` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_required_rte_2_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_staticdata`
--

LOCK TABLES `tx_styleguide_staticdata` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_staticdata` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_staticdata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_type`
--

LOCK TABLES `tx_styleguide_type` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_typeforeign`
--

LOCK TABLES `tx_styleguide_typeforeign` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_typeforeign` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_typeforeign` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tx_styleguide_valuesdefault`
--

LOCK TABLES `tx_styleguide_valuesdefault` WRITE;
/*!40000 ALTER TABLE `tx_styleguide_valuesdefault` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_styleguide_valuesdefault` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-16  8:06:52
