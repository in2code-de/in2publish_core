# Content Publisher CLI Commands

These commands fulfill different requirements and are therefore diverse
in their implementation.

First of all they do not have DocBlocks, because all types can be
inferred from the code,
secondly they break with the "init dependencies in constructor" pattern
that is used throughout the rest of the extension. Since commands are
executed once and only require dependencies in the `execute` method
their scope is limited to exactly that methods.

All commands must exist in a sub directory.
All commands must have a public `IDENTIFIER` constant that reflects the
folder structure and command file name.
