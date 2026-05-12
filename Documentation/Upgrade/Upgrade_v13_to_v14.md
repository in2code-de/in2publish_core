# Upgrading instructions for in2publish_core from v13 to v14

This version raises the requirements to TYPO3 v14.2 and PHP 8.2 – 8.5. There are no breaking
configuration changes; existing `LocalConfiguration.yaml` files remain compatible.

## Summary

1. Upgrade your TYPO3 instances to v14.2 first (Local and Foreign).
2. Upgrade `EXT:in2publish_core` to v14.
3. Clear all TYPO3 caches on Local and Foreign.
4. Run the tests in the Publish Tools Module. **All tests must pass before you can proceed.**

## Not yet supported in v14

The following adapter extensions are **not yet available** for TYPO3 v14 and must be disabled
until a compatible release is published:

- `EXT:in2publish_http` (HTTP adapter for file transfer in SSH-less environments)
- `EXT:in2publish_local` (native file publishing adapter)
- `EXT:in2publish_native` (SSH adapter using OS `ssh`/`scp`)
- `EXT:in2publish_seclib` (SSH adapter using phpseclib)

If you rely on one of these adapters, defer the upgrade until the matching v14 release is
available.

## Configuration Changes

No breaking configuration changes are introduced in this version. All existing configurations
remain compatible.
