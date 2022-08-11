# Deprecation: Tools registration in ext_tables.php

Issue https://projekte.in2code.de/issues/47973

## Description

Content Publisher Tools have been registered in the file `ext_tables.php` using the ToolsRegistry. This approach
clutters that file and does not allow the ordering of these tools. To leverage the power of the new service
configuration, tools are registered using a service tag.

## Impact

Deprecated class:

1. `\In2code\In2publishCore\Tools\ToolsRegistry`

## Affected Installations

All.

## Migration

Instead of calling `$toolsRegistry->addTool()` use the service tag `in2publish_core.admin_tool` instead.

Read the documentation about [custom tools](../Guides/CustomTools.md) to get more information.
