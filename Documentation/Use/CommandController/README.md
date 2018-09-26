# CommandController

Command controller provide information about the system, are used for scheduled publishing and much more.
Some command controllers are intended for CLI usage, some as scheduler tasks. This document describes how, when and where to use them.

**The following pages describe how command controllers can be used**:

* [PublishTasksRunner](PublishTasksRunner.md) 
* [Status](Status.md) 
* [Table](Table.md)

## General

All command controllers require the in2publish context variable IN2PUBLISH_CONTEXT set the the applicable context.
Extbase will show you any executable command with ``./typo3/cli_dispatch.phpsh extbase help``
The backend user "_cli_lowlevel" is required.

The command controllers depend on the context variable, because they have to access the matching configuration.
When calling the extbase help function without any context, you will at least these commands::

    status:all
    status:version
    status:globalConfiguration

To call a command, you need to provide an extra environment variable named "IN2PUBLISH_CONTEXT".
Here is an example::

    IN2PUBLISH_CONTEXT=Local ./typo3/cli_dispatch.phpsh extbase status:version

The example command will boot in2publish with the local configuration and print the current version. The output should look like this::

    Version: 2.2.2

Hint:

> The version number may differ depending on your copy of in2publish_core.

Hint:

> If you have conflicting CommandController names you can add the extension name to clarify which one should be called:

    IN2PUBLISH_CONTEXT=Local ./typo3/cli_dispatch.phpsh extbase in2publish_core:status:globalConfiguration
