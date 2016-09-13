# Table

| Applicable Context | Usage                        |
|--------------------|------------------------------|
| Local, Foreign     | cronjob, Scheduler Task, CLI |

The table command controller can import, export and backup a table of your choice. Any command accepts exactly one table name as argument.
Please ensure you use the Local context for table publishing and import. The backup command can be called on both systems.
Requirement for all commands to work properly is a correctly configured backup path in the relevant configuration.
Refer to the backup section of LocalConfiguration.yaml and ForeignConfiguration.yaml for more information on how to configure these commands properly.

Since 4.4.0: cleanup tables Command: Deletes all entries from all in2publish_* tables which are older than a given time and obsolete. (without optimize).
When executed on Local then Local's and Foreign's database is cleaned up, when executed on Foreign only Foreign's tables are cleaned up.
