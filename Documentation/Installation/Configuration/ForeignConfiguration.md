# ForeignConfiguration.yaml

## Explanation

|Settings|Type|Default|Description|
|---|---|---|---|
|log.logLevel|int|5|0:Emergency, 1:Alert, 2:Critical, 3:Error, 4:Warning, 5:Notice, 6:Info, 7:Debug (int) - All levels smaller or equal to this value will be stored.|
|backup.publishTableCommand.keepBackups|int|2|Backup configuration for Backuptable Command Controller: The number of backups to keep. 0: no backups are made; 1: one backup, keep none; greater than 1: keep the specified number of backups|
|backup.publishTableCommand.backupLocation|string|<see example file>|Backup configuration for Backuptable Command Controller: Specify the location where table backups should be stored (absolute & writable).|
|backup.publishTableCommand.addDropTable|bool|TRUE|Backup configuration for Backuptable Command Controller: adds a "DROP TABLE {tablename} IF EXISTS" statement to the backup|
|backup.publishTableCommand.zipBackup|bool|TRUE|Backup configuration for Backuptable Command Controller: If TRUE, backups of tables will be stored in ZIP files instead of plain sql file. Saves a lot of disc space.|

## Example File

Filename: `ForeignConfiguration.yaml`

```YAML
#
#    Example Configuration for in2publish
#

---

# Logger configuration
log:

  # 0:Emergency, 1:Alert, 2:Critical, 3:Error, 4:Warning, 5:Notice, 6:Info, 7:Debug (int)
  logLevel: 5

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
```
