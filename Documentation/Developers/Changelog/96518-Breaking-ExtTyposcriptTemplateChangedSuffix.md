# ext_typoscript_setup changed suffix

Breaking Change TYPO3 https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-96518-Ext_typoscript_txtFilesNotIncludedAnymore.html

## Description

The suffix of the `ext_typoscript_setup` has been changed from `.txt` to `.typoscript`.

## Impact


## Affected Installations

All installations that overwrite Content Publisher module templates and explicitly including the file
`EXT:in2publish_core/ext_typoscript_setup.txt` either via include statement of in the backend.

## Migration

* Include the file `EXT:in2publish_core/ext_typoscript_setup.typoscript` instead of `EXT:in2publish_core/ext_typoscript_setup.txt`.
