# In2publish Core Change Log

5.3.5:

- [TASK] Update CSS for in2publish' WFSA feature
- [BUGFIX] Return empty domain for page identifier if database is not connected
- [BUGFIX] Lazy initialize FalStorageTestSubjectsProvider (fixes #5)
- [BUGFIX] Escape database name for sysFile auto increment reflection (fixes #7)

5.3.4:

- [BUGFIX] Concentrate remote FAL operations for all files in Publish Files Module selected folder
- [BUGFIX] Cache rFALd results for the whole request
- [TASK] Include the failed Envelopes UID in the error message

5.3.3:

- [BUGFIX] SimpleOverviewAndAjax: Exclude tables without uid field to prevent failures

5.3.2:

- [BUGFIX] Remove SingletonInterface from RemoteFalDriver to ensure references don't get reinitialized with wrong properties
- [BUGFIX] Initialize RemoteFalDriver with proper arguments for each published file

5.3.1:

- [TESTS] Integrate Travis CI testing
- [TESTS] Purge manual autoload configuration
- [REFACTOR] Remove code doublet by merging them into single methods
- [STYLE] Add editor config file and fix all codestyle issues
- [TESTS] Set correct @covers annotations in unit tests for code coverage
- [DOCS] Add short developer explanation for JavaScript files
- [PURGE] Remove unused PageModule fluid layout
- [TASK] Add WFSA feature dependency of the enterprise version
- [BUGFIX] Fix version incompatibility with TYPO3 6.2 where a FFS-PreCaching requires a specific method
- [TASK] Require the existence of the RPC/Envelope table in the backend tests

5.3.0:

- [FEATURE] Cache all remote files for the Overview module for a vast performance increas
- [CODESTYLE] Adjust line breaks in RemoteFalDriver
- [BUGFIX] Increase row size for envelope responses (essentially for FFS RPC/Envelope)
- [REFACTOR] Rename EnvelopeDispatcher::getFileObjectWithoutIndexing to EnvelopeDispatcher::getFileObject
- [FEATURE] Prefetch all sibling file information upon remote file existence check
- [BUGFIX] Respect the storage uid in RemoteFalDriver caches

5.2.0:

- [FEATURE] Display an error if a storage is offline on foreign only
- [FEATURE] Show a warning for if offline storages were detected
- [BUGFIX] Do not test offline FAL storages

5.1.2:

- [BUGFIX] Downgrade array syntax the be PHP 5.3 compatible
- [DOCS] Update default excluded tables list
- [BUGFIX] Ignore assets of extensions simpleOverviewAndAjax

5.1.1:

- [BUGFIX] Use Records property setter to set local and foreign properties
- [BUGFIX] Redefine dependency to TYPO3

5.1.0:

- [FEATURE] Add full FAL support
- [FEATURE] Support case insensitive file systems
- [BUGFIX] Fix Record::getMergedProperty including the unit test
- [BUGFIX] Do not consider the root page (ID=0) as accessible in the frontend
- [FEATURE] Add RPC/Envelope system
- [!!!][CLEANUP] Remove legacy methods from File- and FolderUtility

5.0.1:

- [BUGFIX] Ignore TCA columns without config section
- [BUGFIX] List skipped columns without config section in incompatible section
- [BUGFIX] Use configured site name as rootpage title
- [TASK] Declare non-public API commands as internal

5.0.0:
- [RELEASE] Release in2publish_core alpha 1
- [TASK] Remove surplus features

Notice:
The previous changelog is not public. You can see it if you purchased the enterprise version.
