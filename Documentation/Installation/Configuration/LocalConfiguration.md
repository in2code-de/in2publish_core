# LocalConfiguration.yaml

## Explanation


|Settings|Type|Default|Description|
|---|---|---|---|
|database.foreign.name|string|'database_123'|Name of the foreign database|
|database.foreign.username|string|'username_123'|Username to the foreign database|
|database.foreign.password|string|'Password_123'|Password to the foreign database|
|database.foreign.hostname|string|'localhost'|Host of the foreign database|
|database.foreign.port|int|3306|Port to the foreign database|
|excludeRelatedTables|array|_cropped. See example file_|Tables that are excluded from publishing|
|ignoreFieldsForDifferenceView.[tableName]|array|_depends on table, tables defined by default: pages, physical_folder, sys_file_|Don't show a difference if there is a difference in this table.field|
|ignoreFieldsForDifferenceView.physical_folder|array|_cropped. See example file_|Don't show a difference if there is a difference by comparing folders between Local and Foreign (All settings of  php function stat() are possible.)|
|factory.maximumPageRecursion|int|3|Depth of pages to fetch in hierarchy|
|factory.maximumContentRecursion|int|6|Maximum number of relations in one relation chain. For example: a value of "3" would stop after `tt_content` > `sys_file_reference` > `sys_file`. Therefor `sys_file_metadata` will not be included. |
|factory.maximumOverallRecursion|int|12|Maximum number of instance creation recursion. Minimum: maximumPageRecursion + maximumContentRecursion. Will be ignored if lower.|
|factory.resolvePageRelations|bool|FALSE|Resolve properties of records which target records from "pages" table. Use with care: Related pages will be published through the relation chain, too. Content records are ALL records, even pages and MM Records.|
|factory.simpleOverviewAndAjax|bool|FALSE|_cropped. See example file_|
|factory.fal.reclaimSysFileEntries|bool|FALSE|_cropped. See example file_|
|factory.fal.autoRepairFolderHash|bool|FALSE|_cropped. See example file_|
|factory.fal.mergeSysFileByIdentifier|bool|FALSE|_cropped. See example file_|
|factory.fal.enableSysFileReferenceUpdate|bool|FALSE|_cropped. See example file_|
|filePreviewDomainName.local|string|stage.publishing.localhost.de|Domain prefix for local instance. This is needed for the preview links.|
|filePreviewDomainName.foreign|string|prod.publishing.localhost.de|Domain prefix for the foreign instance. This is needed for the preview links.|
|log.logLevel|int|5|0:Emergency, 1:Alert, 2:Critical, 3:Error, 4:Warning, 5:Notice, 6:Info, 7:Debug (int) - All levels smaller or equal to this value will be stored.|
|view.records.filterButtons|bool|TRUE|Activate filter buttons in publishing module|
|view.records.breadcrumb|bool|FALSE|Displays a list of all records that are related to the selected page and are changed in the overview module and show the relation path to the root. The list will not be shown when debug.allInformation is enabled. The connection to the root record will not be shown when debug.disableParentRecords is TRUE.|
|view.files.filterButtons|bool|TRUE|Activate filter buttons in file module|
|sshConnection.host|string|production.domain.tld|Hostname of the foreign server for SSH connection|
|sshConnection.port|int|22|Default port 22, change if necessary|
|sshConnection.username|string|simpleAccount|Username of user on the foreign server|
|sshConnection.privateKeyFileAndPathName|string|/full/path/to/private/ssh/key/id_rsa|Absolute path to private ssh key from local system (rsa/dsa/..)|
|sshConnection.publicKeyFileAndPathName|string|/full/path/to/public/ssh/key/id_rsa.pub|Absolute path to public ssh key from local system (rsa/dsa/..)|
|sshConnection.privateKeyPassphrase|null/string|NULL|SSH and transfer settings. Private key password for the given private key (see above), NULL if none set|
|sshConnection.foreignKeyFingerprint|string|00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00|Key fingerprint of the production server for additional authentication check. If this fingerprint does not match in2publish will refuse the connection. [How to get the Foreign Key Fingerprint](#how-to-get-the-foreign-key-fingerprint) |
|sshConnection.foreignKeyFingerprintHashingMethod|string|SSH2_FINGERPRINT_MD5|Choose SSH2_FINGERPRINT_MD5 or SSH2_FINGERPRINT_SHA1 for the fingerprint tests|
|sshConnection.foreignRootPath|string|/Users/simpleAccount/Projects/Websites/foreign.publishing.dev|Root path of the foreign TYPO3 CMS instance|
|sshConnection.pathToPhp|string|/usr/bin/env php|Path to php binary on foreign|
|sshConnection.ignoreChmodFail|bool|FALSE|SSH and transfer settings. Ignore if chmod fails. Prevents exception.|
|module.m1|bool|TRUE|Enable or disable publishing overview module in general|
|module.m3|bool|TRUE|Enable or disable file publishing module in general|
|module.m4|bool|TRUE|Enable or disable tools module in general|
|debug.disableParentRecords|bool|FALSE|Debug settings: If set to TRUE, parentRecord will not be set for records|
|debug.showForeignKeyFingerprint|bool|FALSE|Debug settings: Show foreign key fingerprint instead of throwing an exception if keyprint does not match with configuration|
|debug.showRecordDepth|bool|FALSE|Debug settings: Show depth of records in publishing view|
|debug.allInformation|bool|FALSE|Debug settings: Show all information in publishing overview module (which records are related to the current page)|
|debug.keepEnvelopes|bool|FALSE|Do not delete Envelope entries after they fulfilled their purpose. Enable to keep all Envelopes. Overrules method level burnEnvelopes setting.|
|tasks.realUrl.excludedDokTypes|array|[254]|Exclude pages with these dokTypes from realUrl generation|
|tasks.realUrl.requestFrontend|bool|FALSE|Create a web request for the published page|
|disableUserConfig|bool|FALSE|Set to TRUE if User TSconfig should not be merged into this configuration file|
|backup.publishTableCommand.keepBackups|int|2|Backup configuration for Backuptable Command Controller: The number of backups to keep. 0: no backups are made; 1: one backup, keep none; greater than 1: keep the specified number of backups|
|backup.publishTableCommand.backupLocation|string|/Users/simpleAccount/Projects/Websites/foreign.publishing.dev_Backups|Backup configuration for Backuptable Command Controller: Specify the location where table backups should be stored (absolute & writable).|
|backup.publishTableCommand.addDropTable|bool|TRUE|Backup configuration for Backuptable Command Controller: adds a "DROP TABLE {tablename} IF EXISTS" statement to the backup|
|backup.publishTableCommand.zipBackup|bool|TRUE|Backup configuration for Backuptable Command Controller: If TRUE, backups of tables will be stored in ZIP files instead of plain sql file. Saves a lot of disc space.|
|tca.processor.[type]|array|_cropped. See example file_|Processors to handle table relations|

## How to get the Foreign Key Fingerprint

Since this topic has produced some irritations this will be clarified here.

The Foreign Key Fingerprint is, as the name states, a hash of the public ssh key from the foreign systems ssh daemon.
You can generate the hash with following command on your foreign server (example command!): `ssh-keygen -E md5 -lf /etc/ssh/ssh_host_rsa_key.pub`

Hint:

> You can omit the colons of the hash.

## Example File

Filename: `LocalConfigurtion.yaml`

```YAML
#
#    Example Configuration for in2publish
#

---

# Database access
database:
  # Set your production systems database credentials here. If you use port forwarding to
  # the server where the database is
  # installed, the host is localhost and the port is your forwarded port.
  foreign:
    # name of the foreign database
    name: 'database_123'
    # name of the foreign mysql user
    username: 'username_123'
    # password of the foreign user above
    password: 'Password_123'
    # hostname of the database to connect to.
    hostname: 'localhost'
    # default: 3306
    port: 3306


# Exclude tables from publishing
excludeRelatedTables:
  - be_groups
  - be_users
  - sys_domain
  - sys_history
  - sys_log
  - sys_note
  - tx_extensionmanager_domain_model_extension
  - tx_extensionmanager_domain_model_repository
  - tx_rsaauth_keys
  - cache_treelist

# Ignore this fields for DIFF view
ignoreFieldsForDifferenceView:
  pages:
    - pid
    - uid
    - t3ver_oid
    - t3ver_id
    - t3ver_wsid
    - t3ver_label
    - t3ver_state
    - t3ver_stage
    - t3ver_count
    - t3ver_tstamp
    - t3ver_move_id
    - t3_origuid
    - tstamp
    - sorting
    - perms_userid
    - perms_groupid
    - perms_user
    - perms_group
    - perms_everybody
    - crdate
    - cruser_id
    - SYS_LASTCHANGED
    - l18n_cfg
  physical_folder:
    - absolutePath
    - ino
    - mode
    - nlink
    - uid
    - gid
    - rdev
    - size
    - atime
    - mtime
    - ctime
    - blksize
    - blocks
  sys_file:
    - modification_date
    - creation_date
    - tstamp

# factory settings (configuration about building relations in in2publish)
factory:

  # [Performance]
  # depth of pages to fetch in hierarchy
  maximumPageRecursion: 2

  # maximum number of relations in one chain
  maximumContentRecursion: 6

  # maximum number of instance creation recursion.
  # Minimum: maximumPageRecursion + maximumContentRecursion. Will be ignored if lower.
  maximumOverallRecursion: 8

  # Resolve properties of records (and pages) which target records from "pages" table
  # Use with care: Related pages will be published through the relation chain, too
  resolvePageRelations: FALSE

  # [Performance] [BETA]
  # Overview output will be build mainly from information of the local system. Status will be guessed by simple queries.
  # Details are available through an AJAX request and comparison with the foreign system.
  # Warning: Overview does not include everything (e.g. file records) and is not as robust as on default mode
  simpleOverviewAndAjax: FALSE

  # FAL related settings
  fal:

    # This setting will enable a sys_file lookup based on the file identifier of files which were found in the storage
    # but not in the database. (Database files are found by their folder hash)
    reclaimSysFileEntries: FALSE

    # Only in combination with factory.fal.reclaimSysFileEntries enabled.
    # While reclaimSysFileEntries is consuming some additional performance this setting autoRepairFolderHash
    # will automatically repair database entries and increase relation consistency
    #
    # WARNING: This feature changes sys_file entries on live without opt-in or asking for permission.
    #   There is no preview of the changes which are going to be made.
    #   Changes are persisted immediately before the publish file module is rendered.
    autoRepairFolderHash: FALSE

    # [BETA]
    # It is possible for sys_file records to have different UIDs on local and foreign while referencing the same file
    # due to the indexing behaviour of FAL. If this setting is set to FALSE you might see duplicate file entries in the
    # publish files module. Enable this feature to merge the sys_files by identifier before showing them.
    #
    # WARNING: This feature will overwrite the UID of the foreign sys_file record if there are no references in the
    #   table sys_file_reference. No other table or relation will be checked.
    #   This feature changes sys_file_reference entries on live without opt-in or asking for permission.
    #   There is no preview of the changes which are going to be made.
    #   Changes are persisted immediately before the publish file module is rendered.
    #
    # IMPORTANT: If the UID of the foreign sys_file entry is already used in sys_file_reference this feature will not
    #   overwrite the foreign UID to prevent severe damage or missing images on the foreign's frontend.
    #   Hence it's mostly worth the risk and relatively stable.
    mergeSysFileByIdentifier: TRUE

    # [BETA]
    #
    # CAUTION: This feature disables the integrity check of the mergeSysFileByIdentifier feature. USE WITH CARE!
    #
    # DISCLAIMER:
    #   This feature is distributed WITHOUT ANY WARRANTY;
    #   without even the implied warranty of merchantability or fitness for a particular purpose.
    #
    # Disables the mergeSysFileByIdentifier integrity protection to allow you to update a sys_file's UID on foreign
    # along with all associated sys_file_reference entries.
    #
    # WARNING: This feature changes sys_file_reference entries on live without opt-in or asking for permission.
    #   There is no preview of the changes which are going to be made.
    #   Changes are persisted immediately before the publish file module is rendered.
    enableSysFileReferenceUpdate: TRUE

# Set domain names for file preview without leading protocol (e.g. www.domain.org)
filePreviewDomainName:
  local: stage.publishing.localhost.de
  foreign: prod.publishing.localhost.de

# Logger configuration
log:
  # 0:Emergency, 1:Alert, 2:Critical, 3:Error, 4:Warning, 5:Notice, 6:Info, 7:Debug (int)
  # - All levels smaller or equal to this value will be stored.
  logLevel: 5

# Manipulate view
view:
  # Backend module Publish records
  records:
    # Activate Filter buttons
    filterButtons: TRUE

    # show record connection to root record as breadcrumb in overview module. Applies only when debug.allInformation = FALSE.
    breadcrumb: FALSE

  # Backend module Publish files
  files:
    # Activate Filter buttons
    filterButtons: TRUE


# SSH and transfer settings for foreign ssh connection (file and commands)
sshConnection:

  # Hostname of the foreign server for SSH connection
  host: production.domain.tld

  # Default: 22, change if necessary
  port: 22

  # username of user on the foreign server
  username: simpleAccount

  # absolute path to local ssh key (rsa/dsa/..)
  privateKeyFileAndPathName: /full/path/to/private/ssh/key/id_rsa

  # absolute path to local ssh key belonging to the private above
  publicKeyFileAndPathName: /full/path/to/public/ssh/key/id_rsa.pub

  # private key password, NULL if none set
  privateKeyPassphrase: NULL

  # Key fingerprint of the production server for additional authentication check. If this fingerprint does not match in2publish will refuse the connection.
  foreignKeyFingerprint: 00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00

  # Method modification: one of SSH2_FINGERPRINT_MD5 or SSH2_FINGERPRINT_SHA1
  foreignKeyFingerprintHashingMethod: SSH2_FINGERPRINT_MD5

  # root path of the foreign TYPO3 CMS instance
  foreignRootPath: /Users/simpleAccount/Projects/Websites/foreign.publishing.dev

  # path to php binary
  pathToPhp: /usr/bin/env php

  # ignore if chmod fails. prevents exception
  ignoreChmodFail: FALSE


# module settings
module:
  # Enable/Disable Publishing overview module
  m1: TRUE

  # Enable/Disable File Publishing module
  m3: TRUE

  # Enable/Disable Function Publishing module
  m4: TRUE

# Debug settings
debug:

  # if set to TRUE, parentRecord will not be set for records
  disableParentRecords: FALSE

  # Show foreign key fingerprint instead of throwing an exception.
  showForeignKeyFingerprint: FALSE

  # Show depth of records in publishing view.
  showRecordDepth: FALSE

  # show execution time in backend modules in footer
  showExecutionTime: TRUE

  # show all information in publishing overview module (which records are related to the current page)
  allInformation: FALSE

  # Do not delete Envelope entries after they fulfilled their purpose. Enable to keep all Envelopes. Overrules method level burnEnvelopes setting.
  keepEnvelopes: FALSE

# Configuration for tasks
tasks:

  # Task name
  realUrl:

    # Exclude pages with these dokTypes from realUrl generation.
    excludedDokTypes: [254]

    # Create a web request for the published page.
    requestFrontend: FALSE

  # Task name
  solr:

    # enable or disable solr integration
    enable: FALSE


# Set to TRUE if User TSconfig shall not be merged into this configuration file.
disableUserConfig: FALSE

# Backup configuration
backup:

  # Backup settings for table publishing
  publishTableCommand:

    # The number of backups to keep. 0 : no backups are made; 1 : one backup, keep none;
    # greater than 1: keep the specified number of backups
    keepBackups: 2

    # Specify the location where table backups should be stored (absolute & writable).
    backupLocation: /Users/simpleAccount/Projects/Websites/foreign.publishing.dev_Backups

    # Adds a "DROP TABLE {tablename} IF EXISTS" statement to the backup.
    addDropTable: TRUE

    # If TRUE, backups of tables will be stored in ZIP files instead of plain sql file.
    # saves a lot of disc space.
    zipBackup: TRUE

tca:
  processor:
    check: 'In2code\In2publishCore\Domain\Service\Processor\CheckProcessor'
    flex: 'In2code\In2publishCore\Domain\Service\Processor\FlexProcessor'
    group: 'In2code\In2publishCore\Domain\Service\Processor\GroupProcessor'
    inline: 'In2code\In2publishCore\Domain\Service\Processor\InlineProcessor'
    input: 'In2code\In2publishCore\Domain\Service\Processor\InputProcessor'
    none: 'In2code\In2publishCore\Domain\Service\Processor\NoneProcessor'
    passthrough: 'In2code\In2publishCore\Domain\Service\Processor\PassthroughProcessor'
    radio: 'In2code\In2publishCore\Domain\Service\Processor\RadioProcessor'
    select: 'In2code\In2publishCore\Domain\Service\Processor\SelectProcessor'
    text: 'In2code\In2publishCore\Domain\Service\Processor\TextProcessor'
    user: 'In2code\In2publishCore\Domain\Service\Processor\UserProcessor'
    imageManipulation: 'In2code\In2publishCore\Domain\Service\Processor\ImageManipulationProcessor'
```
