# Status

| Applicable Context | Usage         |
|--------------------|---------------|
| Local, Foreign     | internal, CLI |

This command controller is intended to gather information about the system, early error search and for environment testing.
This is the only command controller that will be shown without any context, to identify errors, but requires the context for command execution, because it utilizes the ConfigurationUtility.

Hint:
> Any command of this controller will inform you about an erroneous context or if none is set. If you need more information
> about the execution of in2publish command controllers you can refer to the CommandController Usage index.
