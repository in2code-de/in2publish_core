# Upgrading instructions for EXT:in2publish_core from v12.5 to v12.6

This document outlines the changes and new features introduced in version 12.6.0 of EXT:in2publish_core.

## Major Changes in v12.6.0

### New Publish Overview Module

Version 12.6.0 introduces a completely redesigned Publish Overview module with significant UI improvements:

- **Enhanced Layout**: Improved visual hierarchy and cleaner design close to TYPO3's core style
- **Language Handling**: Languages/translations can now be published separately and filtered in the overview module

When using the Enterprise Edition, the Publish Overview module also includes:

- **Multi-Select**: Multiple records can be selected for publishing/editing
- **Workflow Integration**: Workflows states are displayed and can be edited in the Publish Overview module


## Configuration Changes

No breaking configuration changes are introduced in this version. All existing configurations remain compatible.

## Upgrade Steps

1. **Backup**: Always backup your database and files before upgrading
2. **Update Extension**: Update in2publish_core to version 12.6.0
3. **Clear Caches**: Clear all TYPO3 caches after the update
4. **Test Publishing**: Explore the new Publish Overview module and features and verify that publishing functionality works as expected

