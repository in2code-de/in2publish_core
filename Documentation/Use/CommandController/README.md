# CommandController

Command controller provide information about the system, are used for scheduled publishing and much more.
Some command controllers are intended for CLI usage, some as scheduler tasks. This document describes how, when and where to use them.

**The following pages describe how command controllers can be used**:

* [PublishTasksRunner](PublishTasksRunner.md) 
* [Status](Status.md) 
* [Table](Table.md)

## General

All command controllers require the in2publish context variable IN2PUBLISH_CONTEXT set the the applicable context.
The TYPO3 cli bin will show you any executable command with `./vendor/bin/typo3`

The command controllers depend on the context variable, because they have to access the matching configuration.
When calling the TYPO3 cli  bin without any context, you will at least these commands:

```TEXT
in2publish_core:environment:rewritenonutf8charactersforfiles
in2publish_core:environment:rewritenonutf8charactersforfolders
in2publish_core:publishtasksrunner:runtasksinqueue
in2publish_core:rpc:execute
in2publish_core:status:all
in2publish_core:status:createmasks
in2publish_core:status:dbinitqueryencoded
in2publish_core:status:globalconfiguration
in2publish_core:status:shortsiteconfiguration
in2publish_core:status:siteconfiguration
in2publish_core:status:typo3version
in2publish_core:status:version
in2publish_core:table:backup
in2publish_core:table:import
in2publish_core:table:publish
```

To call a command, you need to provide an extra environment variable named "IN2PUBLISH_CONTEXT".
Here is an example::

```SHELL SCRIPT
IN2PUBLISH_CONTEXT=Local ./vendor/bin/typo3 in2publish_core:status:version
```

The example command will boot in2publish with the local configuration and print the current version. The output should look like this:

```TEXT
Version: 8.0.0
```

Hint:

> The version number may differ depending on your copy of in2publish_core.
