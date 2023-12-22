# In2publish Core Change Log

12.3.1:

- [META] Set the EM conf version number to 12.3.1
- [META] Mark TYPO3 v12 as compatible in ext_emconf.php
- [RELEASE] Version 12.3.0 with TYPO3 v12 compatibility

12.3.0:

- [DOCS] Update Changelog.md
- [META] Set the EM conf version number to 12.3.0
- [CLEANUP] Remove redundant PHP version constraints
- [DOCS] Add known issue about missing file links
- [DOCS] Remove section about Typoscript paths for templates in UPGRADING.md
- [DOCS] Add upgrading information for version 12.3.0
- [BUGFIX] Revert making file publisher reversible, they aren't!
- [TEST] Fix FileSystemPublisherTests
- [DOCS] Add instruction to quote asterisks in YAML for admins
- [BUGFIX] Show reasons why files are not publishable in the filelist
- [FEATURE] Adds another test return to get more specific testresult
- [BUGFIX] Increase max depth when dumping compatible TCA
- [FEATURE] Add LinkProcessor for TYPO3 v12
- [DOCS] Add admin changelog for YAML
- [BUGFIX] Add PreProcessor for TCA type file
- [BUGFIX] Corrects Loading of Middleware
- [DOCS] Adjust Feature List
- [BUGFIX] Restore the confirmation modal for file and folder publishing
- [BUGFIX] Ignore _file.publicUrl because it will always be different and is a computed property
- [BUGFIX] Fix ignoring ctrl.versioningWS
- [BUGFIX] Try to build absolute URLs to files for the preview buttons in the filelist module
- [BUGFIX] Redirect to the previous page after publishing, not the published page
- [BUGFIX] Restore context menu publishing for TYPO3 v12
- [BUGFIX] Validate the resolver cache
- [BUGFIX] Query sys_file_reference.table_local only in TYPO3 v11 as it was removed
- [BUGFIX] Use middleware to inject the loading overlay, split JS into specific modules
- [BUGFIX] Allow passing nodes directly to JS overlay and modal functions
- [BUGFIX] Use Connection::PARAM_STR_ARRAY instead of ArrayParameterType
- [REFACTOR] Extract $GLOBALS access to method
- [BUGFIX] Overwrite toString for PageTreeRootRecord to return the "sitename"
- [BUGFIX] Make PublisherService::publishRecordTree internal to force everyone to use the PublishingContext
- [BUGFIX] Correctly exclude folders and files for the recursiveState of Folder Records
- [BUGFIX] Resolve storage for StorageRootFolderRecords
- [BUGFIX] Access correct property to set the name attribute of folders
- [REFACTOR] Rename ResolverService::getResolversForTable to getResolversForClassification
- [BUGFIX] Introduce a special class for folders which are file storage roots and display the correct icon
- [FEATURE] Add some Injection Traits
- [BUGFIX] Close modals in TYPO3 v12 via new API
- [CLEANUP] Remove unused imports from FileController
- [BUGFIX] Fix multiple errors that occur when calling publishRecordTree more than once
- [FEATURE] Change Order of Modules in Publish Tools
- [CODESTYLE] Add Annotations for Exceptions
- [BUGFIX] Fix Database Compare Table View
- [CLEANUP] Remove Logs Integration
- [BUGFIX] Correct Caption of english Labels
- [FEATURE] Moved all Labels from Publish Tools Module to locallang_mod4.xlf
- [WIP] Changed all Templates of Publish Tools Module
- Revert "[DOC] Add documentation for changed ext_typoscript_template suffix"
- [BUGFIX] Fetch folder records with demand/resolver structure
- [DOC] Add documentation for changed ext_typoscript_template suffix
- [BUGFIX] Corrects styling of redirects module
- [BUGFIX] Add Padding at the Top of the module
- [BUGFIX] Jumpmenu Label is now rendered inline
- [BUGFIX] Change order of flashmessage container
- [REFACTOR] Remove outdated overlay div
- [REFACTOR] Replace version_compare calls with TYPO3_V11 constant
- [BUGFIX] Define (namespaced) constants for TYPO3 version for easier up/down-compatibility
- [BUGFIX] Tighten Colors between v11 & v12
- [BUGFIX] Show colors of badges in file module again
- [REFACTOR] Removes deprecated QueryBuilder methods
- [BUGFIX] Removes deprecated getSchemamanager call
- [BUGFIX] Removes check for directory typo3conf in TYPO3v12
- [REFACTOR] Use be.infobox viewhelper instead of own markup
- [REFACTOR] Simplifies the generation of an controller alias
- [FEATURE] Changes AdminButton to show primary styling correct
- [BUGFIX] Remove deprecations from RegistryController
- [BUGFIX] Remove deprecations from LetterBox
- [REFACTOR] Remove deprecations fomr LogsExporter
- [REFACTOR] Remove deprecated execute from GarbageCollectorTest
- [BUGFIX] Return ResponseInterface in publishFile and publishFolder action
- [REFACTOR] Removes unnecessary Event & Middleware
- [BUGFIX] Add correct hrefs to Buttons on Publish Tools
- [FEATURE] Register Publish Tools Menu in Modules.php
- [REFACTOR] Module Registration for Publish Tools Module refactored
- [REFACTOR] Changes backend module registration for m1, m3, m5
- [BUGFIX] Make the PageTsProvider a Singleton to unlock it globally
- [BUGFIX] Return the RedirectResponse in TYPO3 v12
- [BUGFIX] Rename ext_typoscript_setup suffix to typoscript
- [CLEANUP] Remove superfluous empty lines from ext_tables.php
- [BUGFIX] Enable autowiring of the dynamic PageTypeService
- [BUGFIX] Allow deserialization of TYPO3 v12 Site objects
- [BUGFIX] Create a TYPO3 version aware service to replace TcaService::getTablesAllowedOnPage
- [BUGFIX] Overwrite callActionMethod instead of initializeView to prevent version issues
- [BUGFIX] Implement TYPO3 version specific code to translate the label of the Publish Overview Module shortcut button
- [BUGFIX] Use withRequest to alter immutable request objects
- [BUGFIX] Use the objects view property instead of initializeView arguments
- [REFACTOR] Changes Icon from Tools Module to IconFactory
- [REFACTOR] Change compare of version
- [BUGFIX] Removes trailing slash in Module Configuration
- [REFACTOR] Changes backend module registration for m1, m3, m5
- [BUGFIX] Do not register the BackendRouteInitialization XCLASS in TYPO3 v12
- [BUGFIX] Replace deprecated EventManager::getListeners with getAllListeners
- [TASK] Remove deprecated TYPO3 constants
- [BUGFIX] Set correct narrowed return type hint for ConnectionFactory
- [BUGFIX] Handle constructor differences in PublishItemProvider between t3v11 and t3v12
- [FEATURE] Cache the TcaPreProcessing result
- [CLEANUP] Remove unused import/empty line
- [BUGFIX] Reduce resolver meta info to required keys class and args
- [BUGFIX] Replace Spyc with symfony/yaml
- [TASK] Update composer requirements
- [TASK] Add new branch aliases for develop branch
- [TASK] Allow PHP8.1 as requirement and remove outdated branch-aliases
- [BUGFIX] Inherit the base Exception from in2publish_core, not in2publish
- [BUGFIX] Show debugged queries in separate tab for each request
- [BUGFIX] Show the sum of query duration when debugging queries
- [BUGFIX] Sort grouped queries by amount of calls
- [REFACTOR] Use the CachedRuntimeCache instead of the custom implementation in ForeignSiteFinder
- [BUGFIX] Use runtime cache to prevent multiple cache hits
- [BUGFIX] Increment logged SQL queries statically
- [TESTS] Update unit tests for PublishFileInstructions
- [BUGFIX] Disable the function bar if publishing is not available
- [BUGFIX] Ignore table tx_in2publishcore_filepublisher_instruction by default
- [BUGFIX] Require table tx_in2publishcore_filepublisher_instruction instead of _task
- [DOCS] Update changelog

12.2.0:

- [META] Set the EM conf version number to 12.2.0
- [META] Set the branch alias version number to 12.3.x-dev
- [CLEANUP] Remove all leftover unused imports and add trailing commas
- [BUGFIX] Use AddFolderInstruction to create folders
- [BUGFIX] Restore the compare and preview links in the Publish Overview Module
- [BUGFIX] Hide Preview/Compare links for non-renderable pages
- [BUGFIX] Restored Compare & Foreign Preview Button
- [CLEANUP] Remove unused imports, fix code style
- [BUGFIX] Identify file states and publish replaced files correctly
- [DOCS] Update Demands::addXyz deprecation with issue
- [BUGFIX] Exclude extension-provided forms from publishing
- [FEATURE] Introduce demand types to expand the possibilities of record resolving
- Wip: [BUGFIX] Support Form Framework
- [BUGFIX] Prevent exceptions when legacy adapters extend SshConnectionDefiner
- [BUGFIX] Initialize TransmissionAdapterRegistry::$legacyAdapters
- [FEATURE] Cache all yaml config files based on content
- [FEATURE] Register adapters using the service container
- [FEATURE] Register dynamic value provider through the service container
- [FEATURE] Register config definer, providers, migrations and post processors via interface
- [BUGFIX] Respect TCA type category relationship setting
- [BUGFIX] Only publish the selected record without translations
- [FEATURE] Restore the single record publishing feature
- [BUGFIX] Remove method Record::getParentPageRecord because it is inaccurate
- [CLEANUP] Remove console.log from BackendEnhancements.js
- [REFACTOR] Inject the Core UriBuilder via injection trait
- [CODESTYLE] Fix indentation in new DirtyProperties partials
- [REFACTOR] Split up DirtyProperties Partial
- [BUGFIX] Rename IgnoredFieldsMigration
- [BUGFIX] Move all migrations to their feature folders (into in2publish)
- [BUGFIX] Fix SolrIntegrationMigration
- [TASK] Delete SolrFalMigrationMigration because feature is not available yet
- [TASK] Add configuration migration classes for 7 moved configurations
- Revert "[TASK] Add configuration migration classes for 7 moved configurations"
- [BUGFIX] Correctly check if there are no reasons
- [BUGFIX] Support ignoredFields.*.ctrl.enablecolumns
- [TASK] Add configuration migration classes for 7 moved configurations
- [FEATURE] Make the Dependency Modal reusable and add missing interface
- [BUGFIX] Reenable preloading overlay on publishing
- [BUGFIX] Adds method to record interface
- [FEATURE] Make Dependency Modal usable from other then the Overviewmodule
- [DOCS] We develop and test mostly on LINUX, not UNIX
- [FEATURE] Cache incomplete config based on providers to reduce computations
- [FEATURE] Introduce ConditionalProviderInterface to disable provider on the fly
- [FEATURE] Introduce ConditionalDefinerInterface to disable definer on the fly
- [BUGFIX] Check for the constant TYPO3 instead of TYPO3_REQUESTTYPE in ext_localconf.php
- [REFACTOR] Import all used classes in ext_*.php files
- [BUGFIX] Make the new RTBRequest parameter optional as other methods can not provide it
- [CODESTYLE] Fix remaining code style issues
- [CODESTYLE] Fix whitespace and linebreak issues
- [REFACTOR] Import all classes in test cases and fix import ordering
- [CODESTYLE] Add/remove empty lines where required
- [CODESTYLE] Add trailing commas in multi line argument lists where possible
- [FEATURE] Allow setting the content recursion limit in the RecordTreeBuildRequest
- [BUGFIX] Dispatch the event CollectReasonsWhy... when a record is asked if it is publishable
- [CLEANUP] Remove unused method isRemovedOnOneSideAndDeletedOnTheOtherSide
- [BUGFIX] Set the RecordBuildRequest for the RecordTree if the requested page is not the default language record
- [BUGFIX] Split RunningRequest inserts into chunks
- [BUGFIX] Respect allowLanguageSynchronization behaviour when defining visibility dependencies
- [BUGFIX] Rewrite the label that explains the visibility dependency on the default language for brevity and clarity
- [BUGFIX] Replace deprecated (PHP 8.1) function strftime with date
- [BUGFIX] Set correct value for publish files data-name attribute
- [FEATURE] Implement treatRemovedAndDeletedAsDifference as new feature
- [DEPRECATION] Deprecate anemic TcaService methods
- [TASK] Hide records deleted on foreign and removed on local and vice versa if feature is disabled (default)
- [TASK] Enable feature and add documentation
- [BUGFIX] Update FileList to show identifiers for moved files
- [BUGFIX] Pass on non-page records in the RecordTreeBuilder to find records by TCA
- [DOC] Adjust LocalConfiguration.yaml.example
- [BUGFIX] Add the request for the RecordTree to the RecordTree
- [BUGFIX] Show dirty properties of page records
- [CLEANUP] Remove option for inclusion of translations in RecordTreeBuilder
- [TASK] Add label for Publish redirects module m5
- [TASK] Add label for Publish redirects module m5
- [TASK] Enable option to exclude translations from record tree
- [TASK] Only skip files starting with a dot in PublishFilesModule
- [BUGFIX] Avoid FalPublisherExecutionFailedException when a file is attempted to be transferred twice in one Publishing request
- [TASK] Skip OS-specific files in the PublishFiles module
- [BUGFIX] Fix file publishing
- [BUGFIX] Corrects SSH Adapter
- [DOC] Fix typos in documentation
- [BUGFIX] Add missing methods to tcaService
- [BUGFIX] Dispatch correct event after publishing
- [TASK] Add option to include child pages when publishing a record tree
- [BUGFIX] Avoid empty array key access error
- [FIX] Corrects Backend Module Tools View
- [TASK] Add method to get current page in pageTree
- [BUGFIX] Restore condition isPublishingAvailabe in RecordController indexAction
- [BUGFIX] Fix links to file previews in PublishFiles module
- [CLEANUP] Remove outdated configuration option once again
- [BUGFIX] Fix typo in publish success message
- [TASK] Add missing methods to Record
- [TASK] Adjust documentation and argument description
- [TASK] Add configuration option to add a timeout after file transmission in order to restore file transmission on slow file systems
- [TASK] Adds Badge Style for States
- [BUGFIX] Fix links to file previews in PublishFiles module
- [BUGFIX] Fix errors in partial FileList
- [TASK] Make timeout for file transmission configurable
- [TASK] Add comment with sleep statement to make AssetTransmitter test run on Macs
- [TASK] Adjust documentation and argument description
- [TASK] Add methods for language fields in TcaService
- [TASK] Add missing methods to Record
- [TASK] Adds Badge Style for States
- [BUGFIX] Corrects Caption of Login Configuration
- [WIP] Mark deprectated test as skipped
- [DOCS] Add upgrade documentation
- [BUGFIX] Add missing return type to Dependency::isReachableisReachable
- [REFACTOR] Accumulate dependencies by an array passed as reference
- [META] Update branch-alias to 12.2.x
- [CLEANUP] Remove the no-op usage of the publish ns in the PageRecursion template
- [FEATURE] Provide new events to actually replace the old publishing events
- [CLEANUP] Remove the setting filePreviewDomainName, it is obsolete
- [BUGFIX] Update the copyright year range
- [BUGFIX] Make the support links absolute so they open in a new tab
- [DOCS] Remove reference to the managed config of the enterprise edition
- [BUGFIX] Show loading indicator when changing the depth in the overview module
- [BUGFIX] Don't search for sys_file _file children when the _file occurs as parent
- [BUGFIX] Remove previous sys_file _file parents if the file state is moved
- [BUGFIX] Early return when resolving an empty files demand to prevent useless RCE dispatching
- [REFACTOR] Extract record state to variable to call method once
- [CODESTYLE] Remove superfluous whitespace after unset
- [CLEANUP] Remove unused methods from SysRedirectRepository

12.1.0:

- **Everything included in the 11.0.4 release**
- [CLEANUP] Remove the redundant declaration of Record::getChildren()
- [BUGFIX] Restore the breadcrumb feature
- [DEV] Remove backslashes from the testing docker-compose.yml
- [BUGFIX] Remove buggy record child recursion when adding new records to the index
- [REFACTOR] Move the DbRecordFactoryFactoryCompilerPass into the core component folder
- [BUGFIX] Remove non-existent table tx_in2publishcore_remotefaldriver_file from default ignored tables
- [BUGFIX] Trigger event RecordRelationsWereResolved also for Publish Files Module operations
- [FEATURE] Add new event DemandsWereResolved after resolving the demands
- [CLEANUP] Remove unused injection trait
- [BUGFIX] Set the correct default value for DatabaseSchemaService::$tables
- [BUGFIX] Always append a slash at the targetDir for drivers which don't canonicalize the dir name
- [DEV] Use docker compose instead of docker-compose
- [BUGFIX] Catch all driver exception to prevent errors when files don't exist
- [BUGFIX] Fix sys_redirect query when there are no deleted redirects on foreign
- [TESTS] Ignore code coverage of the exceptions, middlewares and compiler passes
- [CLEANUP] Remove unused ForeignFileInfoExecutionFailedException
- [TYPO] Correct "slitted" to "split"
- [BUGFIX] Correctly inject the deps of the ReplaceMarkersService mock
- [TESTS] Ignore code coverage for all event classes
- [CLEANUP] Remove unused imports from tests
- [TESTS] Update code coverage annotations for all PreProcessors
- [TESTS] Ignore code coverage for dumb PreProcessors
- [DEPRECATION] Deprecate ArrayUtility::getValueByPath
- [TESTS] Remove incorrect covers annotation from EnvironmentServiceTest
- [TEST] Set correct coversDefaultClass for DbInitQueryEncodedCommand
- [DEPRECATION] Deprecate AbstractTask::getConfiguration
- [BUGFIX] Remove duplicate recursion prevention to actually register records in publishing
- [TEST] Import class Testbase in FunctionalTestsBootstrap
- [DEV] Update PHPUnit XSD version
- [TESTS] Don't mock Connection::createSchemaManager when it does not exist
- [DOC] Minor editorial changes in documentation
- [DOC] Fix typo in documentation
- [DOC] Edit documentation for 25505-BreakingChanges-QueryAggregation.md
- [DOCS] Explain how to exclude a record by its parent state and property
- [DOCS] Add missing docs for the events DemandsWereCollected and DemandsForTextWereCollected
- [BUGFIX] Prevent infinite recursion when rendering the overview tree
- [RELEASE] Version 12.0.0 with Query Aggregation


12.0.0:

- [META] Set the branch alias version number to 12.0.x-dev
- [META] Set the EM conf version number to 12.0.0
- [META] Mark as compatible with PHP 8.1
- [META] Update branch alias
- [!!!][FEATURE] Aggregate Queries to improve performance and reduce stability
- [CLEANUP] Remove unused imports
- [BUGFIX] Import all functions and constants
- [BUGFIX] Output the exception message when file publishing failed
- [BUGFIX] Make the FileEdgeCacheInvalidationService public to be instantiable in the task
- [BUGFIX] Exclude fully deleted redirects from the list
- [TASK] Add data attribute record-identifier required for acceptance tests
- [META] Update branch-alias
- [COMMENT] Ignore TranslationConfigurationProvider injection for CodeCov
- [COMMENT] Ignore LabelService injection for CodeCov
- [COMMENT] Ignore UriBuilder injection for CodeCov
- [REFACTOR] Introduce trait to inject the RequiredTablesDataProvider
- [COMMENT] Ignore TestCaseService injection for CodeCov
- [COMMENT] Ignore PackageManager injection for CodeCov
- [REFACTOR] Extract ExtensionUtility::getExtensionVersion to ExtensionService with injection trait
- [COMMENT] Ignore ConnectionPool injection for CodeCov
- [COMMENT] Ignore ListUtility injection for CodeCov
- [COMMENT] Ignore SystemInformationExportService injection for CodeCov
- [REFACTOR] Introduce trait to inject the RawRecordService
- [REFACTOR] Replace the static call to DatabaseUtility with the ConnectionFactory
- [REFACTOR] Replace TYPO3 Random class with two function calls
- [DOCS] Add v11 to v12 UPGRADING section
- [DEV] Add PhpStorm meta for Dependency constants
- [REFACTOR] Introduce trait to inject the FalStorageTestSubjectsProvider
- [COMMENT] Ignore IconRegistry injection PhpUnused inspection
- [COMMENT] Ignore Remote- and TransmissionAdapterRegistry injection for CodeCov
- [COMMENT] Ignore SysRedirectRepository injection for CodeCov
- [COMMENT] Ignore LinkService injection for CodeCov
- [COMMENT] Ignore RunningRequestRepository injection for CodeCov
- [COMMENT] Ignore StreamFactory injection for CodeCov
- [REFACTOR] Introduce trait to inject the TableTransferService
- [REFACTOR] Introduce trait to inject the TableBackupService
- [COMMENT] Ignore ToolsRegistry injection for CodeCov
- [CLEANUP] Remove unused injections from TcaController
- [REFACTOR] Introduce trait to inject the SimpleStopwatch
- [REFACTOR] Introduce trait to inject the PermissionService
- [REFACTOR] Introduce trait to inject the PublisherService
- [REFACTOR] Introduce trait to inject the FailureCollector
- [REFACTOR] Introduce trait to inject the ModuleTemplateFactory
- [REFACTOR] Introduce trait to inject the DefaultFalFinder
- [REFACTOR] Introduce trait to inject the PageRenderer
- [COMMENT] Ignore ForeignEnvironmentService injection for CodeCov
- [COMMENT] Ignore EnvelopeDispatcher injection for CodeCov
- [COMMENT] Ignore AdapterInterfaces injection for CodeCov
- [COMMENT] Ignore TaskFactory injection for CodeCov
- [COMMENT] Reformat DocBlocks in ConnectionFactory
- [REFACTOR] Introduce trait to inject the cache
- [TEST] Test that the cache populates columns and tables of the DatabaseSchemaService
- [COMMENT] Ignore DatabaseRecordFactoryFactory injection for CodeCov
- [REFACTOR] Introduce trait to inject the ForeignSiteFinder
- [CLEANUP] Remove unused class DatabaseSchemaService
- [REFACTOR] Introduce trait to inject the TaskRepository
- [REFACTOR] Introduce trait to inject the ExtensionConfiguration
- [REFACTOR] Introduce trait to inject the Registry
- [COMMENT] Add comment about the service config of the SingleDatabaseRepository
- [REFACTOR] Use DI to configure the injection of the "other" connection
- [REFACTOR] Inject the ExtensionConfiguration object instead of the configured accessed value
- [REFACTOR] Introduce trait to inject the DynamicValueProviderRegistry
- [REFACTOR] Introduce trait to inject the TcaPreProcessingService
- [REFACTOR] Introduce trait to inject the ReplaceMarkersService
- [REFACTOR] Introduce trait to inject the DatabaseSchemaService
- [COMMENT] Ignore RawRecordService injection for CodeCov
- [COMMENT] Ignore TcaService injection for CodeCov
- [REFACTOR] Introduce trait to inject the DemandBuilder
- [REFACTOR] Introduce trait to inject the IgnoredFieldsService
- [COMMENT] Ignore TaskExecutionService injection for CodeCov
- [COMMENT] Ignore AssetTransmitter injection for CodeCov
- [REFACTOR] Introduce trait to inject the RemoteCommandDispatcher
- [COMMENT] Ignore DriverRegistry injection for CodeCov
- [COMMENT] Ignore TableContentService injection for CodeCov
- [REFACTOR] Introduce trait to inject the ExcludedTablesService
- [COMMENT] Ignore FileDemandResolver injection for CodeCov
- [REFACTOR] Introduce trait to inject the Letterbox
- [REFACTOR] Introduce trait to inject the RecordTreeBuilder
- [REFACTOR] Introduce trait to inject the FileSystemInfoService
- [REFACTOR] Introduce trait to inject the FalDriverService
- [REFACTOR] Introduce trait to inject the ForeignFileSystemInfoService
- [REFACTOR] Introduce trait to inject the ResourceFactory
- [REFACTOR] Introduce trait to inject the SingleDatabaseRepository
- [REFACTOR] Introduce trait to inject the DemandResolverCollection
- [REFACTOR] Introduce trait to inject the DemandResolver
- [REFACTOR] Introduce trait to inject the ValidationContainer
- [REFACTOR] Introduce trait to inject the Typo3Version
- [REFACTOR] Introduce trait to inject the TestingService
- [REFACTOR] Introduce trait to inject the DemandsFactory
- [REFACTOR] Introduce trait to inject the SiteFinder
- [COMMENT] Ignore CommandRegistry injection for CodeCov
- [COMMENT] Ignore Container injection for CodeCov
- [REFACTOR] Introduce trait to inject the DualDatabaseRepository
- [REFACTOR] Introduce trait to inject the ContextService
- [REFACTOR] Introduce trait to inject the ResolverService
- [REFACTOR] Introduce trait to inject the FlexFormFlatteningService
- [REFACTOR] Introduce trait to inject the IconFactory
- [REFACTOR] Introduce trait to inject the FlexFormService
- [REFACTOR] Introduce trait to inject the FlexFormTools
- [REFACTOR] Introduce trait to inject the EventDispatcher
- [REFACTOR] Introduce trait to inject the RecordIndex
- [REFACTOR] Introduce trait to inject the ForeignDatabase and the reconnected variant
- [REFACTOR] Introduce trait to inject the LocalDatabase
- [REFACTOR] Introduce trait to inject the TcaEscapingMarkerService
- [REFACTOR] Introduce trait to inject the RelevantTablesService
- [REFACTOR] Introduce trait to inject the RecordFactory
- [REFACTOR] Introduce trait to inject the EnvironmentService
- [CLEANUP] Remove old commented code
- [REFACTOR] Introduce trait to inject the config container
- [DOCS] Update docs with ssh adapter hints, remove old or resolved known issues, update FAQ
- [BUGFIX] Pass a RecordTreeBuildRequest to the method buildRecordTree
- [DOCS] Update all docs for v12
- [BUGFIX] Find the execution line number for the first frame when dumping queries
- [BUGFIX] Fix undefined array key warning in PublishOverviewModule
- [FEATURE] Improve query logs for smaller memory usage, and added args
- [BUGFIX] Use the enableField's name if the field does not have a defined label
- [BUGFIX] Use a bound closure to be able to use $this
- [BUGFIX] Correctly check if pages is the only child classification
- [FEATURE] Allow editors to publish pages with unreachable dependencies
- [FEATURE] Detect moved files and display them accordingly
- [BUGFIX] Update the default setting for excludeRelatedTables
- [BUGFIX] Ignore legacy options ignoreFieldsForDifferenceView if it's empty
- [BUGFIX] Fix error when publishing renamed files
- [FEATURE] Support files and folders in the RecordInspector admin tool
- [TEST] Fix InputProcessorUnitTest
- [BUGFIX] Fix undefined array key exception in InputProcessor
- [CLEANUP] Remove unused classes
- [BUGFIX] Ignore invalid flex form identifier
- [BUGFIX] Early return when a FlexFormStructure does not have a dataStructureKey
- [BUGFIX] Restore the publish arrow icon
- [BUGFIX] Use envelopes instead of a another crappy implementation of rFALd
- [BUGFIX] Use the "transient" folder to temporary publish files
- Revert "[BUGFIX] Fix classification for FileRecords"
- [BUGFIX] Extract the identifier hash from the temporary file path
- [TEST] Fix test failing due to lower PHP version in test setup
- [TEST] Add RecordTreeBuilderTest
- [BUGFIX] Fix name of injection method for DemandResolver
- [TEST] Fix unit tests
- [TEST] Add RecordTreeTest and RecordTreeBuildRequestTest
- [CLEANUP] Remove superfluous comma in FileDemandResolverTest
- [TEST] Add FolderRecordTest and TtContentDatabaseRecordTest
- [TEST] Add more unit tests in folder Component/Core/Record
- [TEST] Add DatabaseRecordFactoryFactoryTest and FileRecordTest
- [TEST] Fix covers annotations
- [TEST] Add dependency test
- [WIP][TEST] Add RecordFactoryTest
- [TEST] Fix covers annotation for FileDemandResolver and cleanup code
- [WIP][TEST] Add FileDemandResolverTest
- [TEST] Add FileRecordListenerTest
- [BUGFIX] Fix classification for FileRecords
- [TEST] Add FlexResolverTest
- [TEST] Add test for resolve with additionalWhereClause to SelectMmResolverTest
- [TEST] Add more resolver unit tests
- [API] Add getter for dependency properties
- [REFACTOR] Simplify code to get unfulfilled dependencies as strings
- [FEATURE] Add superseding of dependencies and list all unfulfilled in the DirtyPropertiesList
- [FEATURE] Add reduce to FlatCollection
- [BUGFIX] Fix labels for dependencies
- [BUGFIX] Initialize the FlatCollection objects property with an empty array
- [BUGFIX] Mark pages as publishable if their children's dependencies would be fulfilled by publishing the page
- [FEATURE] Display the reasons a record is not publishable in the record details
- [FEATURE] Make the dependencyRecursionLimit dynamically
- [BUGFIX] Resolve CType shortcut record dependencies only once
- [REFACTOR] Extract common collection properties to FlatCollection
- [FEATURE] Use the record index to query less records in the RawRecordService
- [META] Add expected arguments for all RawRecordService methods
- [FEATURE] Additionally group queries by caller when debugging queries
- [BUGFIX] Recursively resolve 3 levels of dependencies
- [CLEANUP] Remove the experimental RecordDependencyResolver
- [CODESTYLE] Reformat the labelArgumentsFactory of the AbstractDatabaseRecord
- [BUGFIX] Early return for added/deleted records in dependency check
- [BUGFIX] Use the correct recordCollection to find pages recursively
- [CLEANUP] Remove unused imports from RecordController and AbstractDatabaseRecord
- [BUGFIX] Correctly use the decision of CollectReasonsWhyTheRecordIsNotPublishable
- [FEATURE] Collect and debug SQL queries, replace ExecutionTimeService with SimpleStopwatch
- Wip: [FEATURE] Introduce dependencies between records that prohibit unsafe publishing
- [TEST] Adjust covers annotations, refactor variable names
- [TEST] Add testIsSysCategoryField to SelectProcessorTest
- [TEST] Add ExtNewsRelatedProcessorTest
- [TEST] Add missing strict_types declaration in test classes
- [TEST] Add CategoryProcessorTest
- [TEST] Add some more test cases to GroupProcessorTest
- [TEST] Use camel case for table and field names in GroupProcessorTest
- [TEST] Add some more tests to GroupProcessorTest
- [TEST] Add TcaEscapingMarkerServiceTest
- [TEST] Fix InlineProcessorTest
- [TEST] Fix GroupProcessorTest
- [TEST] Add InlineProcessorTest
- [TEST] Add some more tests to AbstractProcessorTest
- [META] Add overrides for factory methods
- [META] Remove redundant phpstorm meta
- [TEST] Add AbstractProcessorTest
- [TEST] Add test for mmTableRelations with MM_match_fields
- [TEST] Refactor GroupProcessorTest and add some more tests
- [TEST] Add GroupProcessorTest
- [TEST] Test calling of cancel, reverse and finish method
- [TEST] Unset global variables after test completion
- [TESTS] Fix all coverage annotations
- [TEST] Add PublisherServiceTest
- [TEST] Remove unused global variable in PublisherCollectionTest
- [TEST] Test publish method in PublisherCollectionTest
- [TEST] Fix covers annotations
- [TEST] Add tests for finish, cancel and reverse to PublisherCollectionTest
- [TEST] Add additional test to DatabaseRecordPublisherTest
- [TEST] Add PublisherCollectionTest
- [TEST] Add more tests to FileRecordPublisherTest
- [TEST] Add FolderRecordPublisherTest
- [TEST] Add DatabaseRecordPublisherTest and FileRecordPublisherTest
- [CLEANUP] Remove refactoring remnants
- [REFACTOR] Move all record models to Component/Core/Record and Domain/Services to Services
- [FEATURE] Add the execution time to the publishing result flash message
- [BUGFIX] Always return a RecordTree of the requested record
- [FEATURE] Set the maximum page recursion in the Publish Overview Module
- [REFACTOR] Rename TcaHandling to Core
- [BUGFIX] Revert change to WeakReference in RecordColletion to prevent unintentional record dropping
- [BUGFIX] Assign common variables to all admin tools templates
- [FEATURE] Add AdminTool to inspect record trees
- [DOCS] Add missing view.breadcrumb setting to LocalConf file
- [CLEANUP] Remove unused fluid template imports
- [BUGFIX] Get a record from the index instead of using a generator as array
- [CLEANUP] Remove debug.disableParentRecords
- [REFACTOR] Resolve todo to refactor to AbstractTask::toArray
- [META] Add return values meta for ContextService::getContext
- [CODESYTLE] Chop down Builder::addOptionalArray
- [REFACTOR] Move the ConfigContainer to the component folder
- [META] Add metadata for all remaining methods that expect constants
- [BUGFIX] Add missing parameter type hint for FalStorageTestSubjectsProvider::getStorages
- [CLEANUP] Remove unused adapter type constants from adapter interfaces
- [BUGFIX] Fix inheritance of RedirectController and use BE_USER
- [BUGFIX] Configure cache injection of ForeignSiteFinder
- [BUGFIX] Add missing property type hints
- [META] Add expectedArguments for ProcessingResult::__construct
- [BUGFIX] Remove superfluous space after regex delimiter
- [BUGFIX] Add JSON_THROW_ON_ERROR flag where possible
- [BUGFIX] Explicitly use '/' as regex delimiter
- [COMMENT] Ignore false-positive UnsupportedStringOffsetOperationsInspection
- [REFACTOR] Use php language features to reduce code
- [BUGFIX] Add missing return type hints
- [BUGFIX] Add exceptions classes for all missing concrete exceptions
- [BUGFIX] Set correct return type of Envelope::getRequest
- [CLEANUP] Remove redundant type annotation from FlexResolver
- [BUGFIX] Streamline parameter name of Node::addChild
- [COMMENT] Ignore false-positive unnecessary curly braces
- [BUGFIX] Unwrap condition which is always true
- [CLEANUP] Use the actual Connection class to reference dbal constants
- [CLEANUP] Remove redundant default parameters
- [BUGFIX] Add missing return in getSysRedirectSelect
- [CLEANUP] Remove unused parameters
- [CLEANUP] Remove unused methods from AbstractTask and BackendUtility
- [TEST] Fix DbInitQueryEncodedCommandTest namespace
- [REFACTOR] Move the ExecuteCommand into its component folder
- [REFACTOR] Move all "communication" parts to "component"
- [REFACTOR] Rewrite the RecordCollection to use Generators
- [CLEANUP] Remove all performance tests, they have never contributed anything else that confusion
- [DOCS] Add missing event documentation
- [BUGFIX] Add the option the ignore file and folder records
- [CLEANUP] Remove leftover PhysicalFileWasPublished.md
- [CLEANUP] Remove unused event FolderWasPublished
- [CLEANUP] Remove unused event FolderInstanceWasCreated
- [CLEANUP] Remove unused event RootRecordCreationWasFinished
- [FEATURE] Extract table publishing commands to a feature and improve service loading
- [CODESTYLE] Reformat code to fit PSR-12
- [CLEANUP] Remove unused utilities/utility methods and UidReservationService
- [FEATURE] Display the environment status information in the footer
- [BUGFIX] Strip the executionTime microsendonds to the first 4 digits
- [FEATURE] Reorganize demand resolver and create a factory for easier injection
- [BUGFIX] Actually implement JoinResolver findMissingTableRecords which was only copied from the SelectDemandResolver
- [BUGFIX] Correctly access an array with square brackets
- [BUGFIX] Set correct order of joinTables/tables in JoinRowCollection
- [BUGFIX] Early return in the FlexResolver when a record has no flexform data
- [BUGFIX] Strictly check if the deleteField value is null
- [REFACTOR] Replace anonymous functions with arrow functions
- [CLEANUP] Remove unused method getAllTableNamesAllowedOnRootLevel from TcaService
- [FEATURE] Restore the filter of allowed tables per doktype for searching in all tables
- [REFACTOR] Inline one-line-methods from TcaService
- [CLEANUP] Remove unused MissingArgumentException
- [CLEANUP] Remove abstract controllers from the hierarchy, always show the executionTime
- [BUGFIX] Don't use the RecordCollection as array anymore
- [BUGFIX] Detect renamed files and publish them as such
- [BUGFIX] Restore the filelist comparison view
- [CLEANUP] Remove unused viewhelpers and nesting of partials
- [REFACTOR] Move RunningRequests to feature PreventParallelPublishing
- [REFACTOR] Create BackButton class to transfer logic from RedirectController
- [CLEANUP] Remove the unused class PageSlugService and table tx_in2publishcore_pages_slug_data
- [CLEANUP] Remove unused event class AllRelatedRecordsWereAddedToOneRecord
- [FEATURE] Restore the PageRecordRedirectEnhancer (with dynamic target urls only)
- [BUGFIX] Remove runTasks call from the RedirectController, it's now part of the publishing process
- [BUGFIX] Restore the selectSiteAndPublish function of feature SysRedirectSupport
- [CLEANUP] Inline TcaService::getNameOfSortingField
- [FEATURE] Reduce data written to the task table by the NewsSupport feature
- [BUGFIX] Restore the FileEdgeCacheInvalidator feature
- [COMMENT] Clarify why a clone of the foreignDatabase is made
- [BUGFIX] Create the parent folder if necessary before publishing a file
- [BUGFIX] Restore feature ContextMenuPublishEntry
- [BUGFIX] Restore task execution feature after everything was published
- [CLEANUP] Remove unused imports from RecordController
- [BUGFIX] Strip objects from the compatible TCA export
- [TESTS] Add missing covers annotations to command tests
- [TESTS] Remove unused EventDispatcher from QueryServiceTest
- [CLEANUP] Remove unused imports from DefaultFalFinder
- [TESTS] Revert changes to DemandsCollection/SelectDemands
- [FEATURE] Use new demand/resolver to find and publish sys_redirects
- [BUGFIX] Fix TATAPI return value and UniqueStorageTargetTest
- [BUGFIX] Delete folders recursively
- [BUGFIX] Redirect user if selected folder was deleted on both sides
- [BUGFIX] Add fallback if no folder was selected in the publish files module
- [BUGFIX] Use type array instead of string for argument messages in UniqueStorageTargetTest
- [CLEANUP] Remove FalHandling and RecordHandling
- [FEATURE] Finish file publishing in the File Publish Module
- [BUGFIX] Restore the edit metadata functionality in the Publish Files Module
- [FEATURE] Rewrite folder publishing by using the new publishing service
- [FEATURE] Remove FalPublisher and use RecordTree as Publish Files Module root node
- [BUGFIX] Fix displaying of folder names and subfolders
- [BUGFIX] Use file extension to determine the file icon when there is no mimetype
- [FEATURE] Rewrite DefaultFalFinder
- [REFACTOR] Replace controller constructor injection with setter injection
- [REFACTOR] Replace constructor injections with setter injections for all commands
- [FEATURE] Migrate all publishing-related events to the new PublisherService
- [META] Add phpstorm meta file
- [BUGFIX] Remove unused parameter from FileDemandResolver::resolveDemand
- [FEATURE] Split resolver, use objects to collect rows, pass RecordCollections instead of creating them
- [COMMENT] Add missing type hint for RecordCollection's record property
- [REFACTOR] Rename THE SERVICE to RecordTreeBuilder
- [BUGFIX] Explicitly provide the delimiter for preg_quote
- [CODESTYLE] Import all DBAL exceptions and wrap long signatures/calls
- [CODESTYLE] Remove superfluous empty lines
- [CLEANUP] Remove unused imports
- [BUGFIX] Add missing return type to PublisherCollection::addPublisher
- [CLEANUP] Remove unused methods from SingleDatabaseRepository
- [REFACTOR] Rename DatabaseIdentifierQuotingService to TcaEscapingMarkerService
- [TESTS] Don't mock the Demand in DemandServiceTest to actually collect demands
- [TESTS] Fix FlexFormFlatteningServiceTest by expecting the right return value
- [BUGFIX] Search for table records of JOIN queries which were missed
- [BUGFIX] Correctly check if the record collection is empty
- [TESTS] Add TextResolverTest
- [TESTS] Add missing covers annotations
- [WIP] Add FlexFormFlatteningServiceTest
- [TESTS] Add missing annotations
- [TESTS] Move PreProcessorTests to separate directory
- [TEST] Fix dependencies in Unit tests
- [BUGFIX] Add missing return CallerAwareDemandsCollection::getFiles
- [BUGFIX] Do not skip resolvers for empty tables, that is what ResolverService does
- [BUGFIX] Make getColumnNames return all columns properly typed
- [FEATURE] Support arbitrary FlexForm sections
- [FEATURE] Add option to trace the origin of demands for easier debugging
- [BUGFIX] Fix correct order of arguments
- [FEATURE] Support inline relations without foreign_field
- [CLEANUP] Remove wrong copyright notices
- [BUGFIX] Do not create resolver that would build demands on excluded tables
- [BUGFIX] Do not search for sys_file and sys_file_metadata by PID
- [FEATURE] Resolve TCA escape characters once in the processors instead of the resolver
- [FEATURE] Add CategoryProcessor for categorized tables
- [TESTS] Enable forceCoversAnnotation and add missinf covers annotations to DemandServiceTest
- [TESTS] Add test for Demands
- [TESTS] Fix tests, remove Record Test
- [FEATURE] Finish basic file publishing (without edge cases)
- Wip: [FEATURE] Create file records and attach them to sys_files
- Wip: [FEATURE] Begin FAL handling
- [FEATURE] Finish simple record publishing process with transaction
- [CLEANUP] Remove tca pre processor config which was replaced by DI container features
- [FEATURE] Replace ignoreFieldsForDifferenceView with more powerful ignoredFields
- [FEATURE] Add optional array for deprecation of configuration trees
- [BUGFIX] Re-add removed import which breaks LSP when building the DI container
- [BUGFIX] Reset state after getStateRecursive and exclude pages from recursive state
- [BUGFIX] Support single table group relations which contain the table in the value
- Wip: [FEATURE] Develop publishing strategy
- [CLEANUP] Remove event VoteIfSearchingForRelatedRecordsShouldBeSkipped
- [CLEANUP] Remove event VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped
- [CLEANUP] Remove event VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped
- [FEATURE] Replace VoteIfRecordShouldBeIgnored with DecideIfRecordShouldBeIgnored
- [CLEANUP] Remove event VoteIfPageRecordEnrichingShouldBeSkipped
- [CLEANUP] Remove event VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped
- [CLEANUP] Remove event VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped
- [CLEANUP] Remove event VoteIfFindingByPropertyShouldBeSkipped
- [FEATURE] Refactor services to follow SRP, skip resolver for empty and excluded tables
- [CLEANUP] Remove unnecessary empty lines from RecordFactory
- [BUGFIX] Use the correct EventDispatcher in TextResolver
- [REFACTOR] Inject database connection in TableInfoService instead of using static access
- [REFACTOR] Move ColumnNameService to TcaHandling Component
- [BUGFIX] Replace event VoteIfFindingByIdentifierShouldBeSkipped with DemandsWereCollected
- [FEATURE] Replace event RelatedRecordsByRteWereFetched with DemandsForTextWereCollected
- [CLEANUP] Remove event RelatedRecordsByRteWereFetched
- [CLEANUP] Remove event RecordWasEnriched
- [CLEANUP] Remove unused methods from DualDatabaseRepository
- [REFACTOR] Inject dependencies in resolvers, make resolvers injectable
- [CLEANUP] Remove feature SimplifiedOverviewAndPublishing
- [REFACTOR] Move Dual-/SingleDatabaseRepository classes to TcaHandling component
- [BUGFIX] Search records that have not been found by properties because these are different in the other database
- [BUGFIX] Correctly detect if records are moved by their PID or sortBy field if it exists
- [BUGFIX] Prevent infinite recursion when calling Record::getStateRecursive
- [REFACTOR] Extract resolver logic to classes
- [BUGFIX] Fix tests and replace demand array with object for better readability
- [BUGFIX] Add records found by join demand to the list of found records
- [BUGFIX] Add missing record keys to demands
- [FEATURE] Introduce RecordCollection to replace the structured array
- [TEST] Fix tests by including global functions
- [BUGFIX] Fix records overwriting demand target records
- [BUGFIX] Return '---' for sys_file_references that do not exist
- [BUGFIX] Use deeply nested array for collecting records, remove showRecordDepth and allInformation feature
- [BUGFIX] Update replaceSiteMarker method to work with the new DatabaseRecord
- Wip: [FEATURE] Create the central service for the core process, show the new record tree in html
- Wip: [FEATURE] Create a RecordIndex of all records found, support RegEx in excluded table
- [TEST] Add DemandServiceTest and FlexProcessorTest
- Wip: [FEATURE] Create TcaHandling component to resolve TCA into closures
- [GIT] Ignore build and qa files when creating a production dist
- [GIT] Ignore the .github folder when creating a production dist

1.0.9:
- [META] Set the EM conf version number to 11.0.9
- [BUGFIX] Search for file links only in href attributes
- [BUGFIX] Do not enhance records with sys_redirects if they are excluded from publishing
- [RELEASE] Version 11.0.8 with correct performance test

11.0.8
- [META] Set the EM conf version number to 11.0.8
- [BUGFIX] Sort query results by language
- [BUGFIX] Use suitable default values in SQL dumps
- [BUGFIX] Add missing word in german translation
- [BUGFIX] Fix DiskSpeed performance test units
- [BUGFIX] Fix DBInit performance test units

11.0.7:
- [TASK] Raise version number in ext_emconf.php
- Update de.locallang.xlf

11.0.6:
- [META] Set the EM conf version number to 11.0.6
- [DOCS] Update changelog
- [TASK] Add label for Publish redirects module
- [BUGFIX] Correct SSH Adapter
- [BUGFIX] Avoid error log entries for unset backend users in clear cache command
- [BUGFIX] Update definition of defaultIgnoredTables for publishing

11.0.5:
- [META] Set the EM conf version number to 11.0.5
- [DOCS] Update changelog
- [BUGFIX] Avoid undefined array key access in ShallowRecordFinder
- [BUGFIX] Fix registration of BackupCommand and description of ImportCommand
- [TASK] Allow PHP 8.1 as requirement

11.0.4:
- **Everything included in the 10.2.5 release**
- [META] Set the EM conf version number to 11.0.4
- [DOCS] Update changelog
- [TESTS] Configure Code Coverage and add missing coversDefaultClass
- [META] Mark as compatible with PHP 8.1
- [RELEASE] Version 11.0.3 with bug fixes

11.0.3:

- **Everything included in the 10.2.4 release**
- [META] Set the EM conf version number to 11.0.3
- [CLEANUP] Remove useless parentheses
- [QA] Update QA tools
- [BUGFIX] Set the correct return type for FileController::getRecordToPublish
- [COMMENT] Update rFALd::getFoldersInFolder return annotation
- [BUGFIX] Call the correct (renamed) method rebuildAll for TYPO3 v11
- [REFACTOR] Reduce cyclomatic and npath complexity
- [QA] Force installation of unsigned phars (like phpmd) and add all phars to PATH
- [TESTS] Remove duplicate import of RuntimeException
- [TESTS] Remove duplicate import of Testbase
- [BUGFIX] Set correct flash message severity when file publishing fails and show collected failures
- [DOCS] Add UPGRADING for v10 to v11 and some publish files module screenshots
- [BUGFIX] Add missing slash in combined folder identifier
- [BUGFIX] Fetch all RPC rows instead of just the first

11.0.2:

- **Everything included in the 10.2.3 release**
- [BUGFIX] Modify FAL folder identifier to work with most 3rd party drivers
- [BUGFIX] Replace deprecated FILTER_SANITIZE_STRING option from filter_var with htmlspecialchars
- [BUGFIX] Hide publish file/redirect button if the record is being published
- [BUGFIX] Resolve doctrine/dbal deprecations
- [BUGFIX] Implement missing replacement for SITE markers in TCA
- [REFACTOR] Inject a DB connection in ReplaceMarkersService to get rid of static calls
- [TASK] Apply review feedback
- [BUGFIX] Add missing SiteFinder use statement
- [TASK] Also replace SITE: markers in FlexForms
- [TASK] Add test for replaceSiteMarker functionality
- [BUGFIX] Adjust test cases for ReplaceMarkersService to match new constructor
- [FEATURE] Implement replacement of SITE: markers in TCA
- [RELEASE] Version 11.0.1 with minor bug fixes

11.0.1:

- [META] Set the EM conf version number to 11.0.1
- [DOCS] Update changelog
- [META] Set the EM conf version number to 10.2.2
- [BUGFIX] Show the actual key for differences in site configs
- [BUGFIX] Check the actual delete field of a table to determine if the record is deleted
- [BUGFIX] Prevent access on undefined array key
- [CLEANUP] Strip a superfluous function import as alias
- [BUGFIX] Actually use the value formatted by its TCA eval field
- [BUGFIX] Include array key existence check when looking for group/db MM tables
- [BUGFIX] Format arrays as strings before comparing them with array_diff
- [BUGFIX] Fix array key for registered tool actions
- [BUGFIX] Check if deprecated config values are set before evaluation
- [TYPO] Fix message when there are no envelopes to flush
- [RELEASE] Version 11.0.0 with TYPO3 v11 compatibility

11.0.0:

- [META] Set the branch alias version number to 11.0.x-dev
- [META] Set the EM conf version number to 11.0.0
- [!!!][FEATURE] Add TYPO3 v11 compatibility
- [META] Set correct PHP compatibility in ext_emconf.php
- [DOCS] Add alternative finder configuration as example to the config file
- [BUGFIX] Update Module Layout of Redirects assignment view
- [BUGFIX] Make Record- and FalHandler public
- [BUGFIX] Load CSS/JS only when required
- [BUGFIX] Fix overview comparison container toggle
- [CODESYTLE] Unify SQL code style
- [BUGFIX] Recompile the CSS
- [BUGFIX] Upgrade lower bound of typo3/testing-framework to a working version
- [BUGFIX] Require polyfills (required in GH actions)
- [BUGFIX] Require typo3/cms-reports (required in GH actions)
- [BUGFIX] Declare missing return type for EnvelopeDispatcher::getFileObject
- [DEV] Add make targets to run tests and QA tools
- [BUGFIX] Remove int type casting from splitCombinedIdentifier (strings are valid identifiers)
- [BUGFIX] Correctly split identifier for Record::getPageIdentifier
- [BUGFIX] Remove TaskRepository from service definition
- [BUGFIX] Remove CommonRepository from service definition
- [BUGFIX] Fix all psalm issues
- [BUGFIX] Fix issues reported by PHPStorm
- [CLEANUP] Resolve all deprecation introduced in CP CE v10
- [BUGFIX] Fix all PHPMD issues
- [CLEANUP] Remove deprecated method ToolsRegistry::removeTool
- [CODESTYLE] Fix phpcs errors
- [CODESTYLE] Automatically fix with php-cs-fixer
- [QA] Add qa tools
- [BUGFIX] Allow skipping notification (for enterprise edition)
- [UPDATE] Require at least TYPO3 v11.5
- [TESTS] Remove listener from publishing test
- [FEATURE] Add more data attributes to identify rows
- [BUGFIX] Ignore related folders for the recursive state of records
- [BUGFIX] Add classes to file module control columns
- [BUGFIX] Self-close core:icon ViewHelper
- [BUGFIX] Remove duplicate filter listener setup
- [REFACTOR] Replace legacy fluid NS imports with html tags in file related fluid files
- [CLEANUP] Remove unused fluid NS imports in File Layout
- [BUGFIX] Add missing ignored fields for sys_redirects
- [BUGFIX] Add missing data attributes for folders in the file list
- [BUGFIX] Move the file state filter toggles back to in2publish_core
- [DOCS] Lowercase all code block language injections
- [REFACTOR] Wrap the File/DirtyProperties partial in html/xmlns tags
- [DOCS] Add the factroy.fal.(finder|publisher) settings to the example yaml config
- [FEATURE] Refactor FAL handling into finder/publisher, support localized file meta, overlay and more
- [FEATURE] Rename the JS module and move the multi-publishing function to the enterprise edition
- [BUGFIX] Set correct condition to enable the file icon type fallback
- [FEATURE] Extract FAL file factories and processors to component
- [FEATURE] Always render file icons and fix missing empty buttons
- [BUGFIX] Respect setting view.records.filterButtons
- [FEATURE] Simplify filter status toggles
- [BUGFIX] Use and check against the local file to show the foreign-side file type icon
- [BUGFIX] Use resource path for foreign images and f:image for local ones to prevent Fluid errors
- [TASK] Add state label for "moved-and-changed" status
- [BUGFIX] Fix for-loop as-property breaking edit link generation
- [BUGFIX] Wrap image/file output that is potentially null with proper conditions
- [TASK] Remove displayed storage/page path from layout
- [TASK] Add localizations for new file publishing UI
- [FEATURE] Initial frontend integration of multi-selection in file publish module
- [FEATURE] Add search functionality to file publish list
- [TASK] Add branch alias to make installation work with in2publish enabled
- [TASK] Highlight changed properties in property list
- [TASK] Show specific file-type icons with core:iconForResource in file publishing list
- [TASK] Rename state color classes to use a uniform in2publish-state- prefix
- [TASK] Show filename of deleted file on local side
- [TASK] Re-add filtering functionality in file publishing overview
- [WIP][TASK] Add HTML structure for new filter display in function bar
- [WIP][TASK] Implement initial version of new properties list, showing more file properties
- [WIP][TASK] More closely follow TYPO3's way of using tables in the backend
- [WIP][TASK] Integrate new file publishing overview design
- [BUGFIX] Update the compare DB tabs to work with TYPO3 v11 JS
- [BUGFIX] Fix admin tools welcome page layout
- [BUGFIX] Keep all request arguments for module bookmarks
- [BUGFIX] Update module layouts for TYPO3 v11
- [REFACTOR] Extract AbstractController::runTasks to trait and use setter injection
- [CLEANUP] Remove ActionController::addFlashMessage workaround
- [REFACTOR] Replace property type annotations with property type hints
- [REFACTOR] Move extbase classes BackendUser and BackendUserRepository from in2publish_core to in2publish
- [REFACTOR] Run rector and adapt some special cases
- [BUGFIX] XCLASS the BackendRouteInitialization middleware to create an event after loading ext_tables
- [BUGFIX] Create the ConfigurationManager via interface
- [UPGRADE] Move deprecated generic Extbase domain classes BackendUser and BackendUserRepository to own Extbase classes
- [UPGRADE] Copy deprecated QueryHelper and QueryGenerator functions to Database utility
- [UPGRADE] Remove call of deprecated method ExtbaseActionController::initializeView
- [CLEANUP] Remove non-existent tables from excludeRelatedTables
- [UPGRADE] Remove deprecated use of ObjectManager
- [META] Support PHP 8.0
- [TESTS] Run tests with PHP 7.4 and 8.0
- [META] Remove ignore-as-root composer extra
- [TESTS] Fix functional tests for TYPO3 v11
- [UPGRADE] Add category and language TCA processor
- [TESTS] Update tests for TYPO3 v11
- [BUGFIX] Use internal_type=db as default type for type=group
- [BUGFIX] Replace isLoaded in Services.php with a workaround
- [BUGFIX] Remove extTablesInclusion-PostProcessing hook from ext_localconf.php
- [UPGRADE] Replace deprecated constant TYPO3_REQUESTTYPE
- [UPGRADE] Replace TableConfigurationPostProcessingHookInterface with TYPO3\CMS\Core\Core\Event\BootCompletedEvent
- [CLEANUP] Remove outdated compatibility class SignalSlotReplacement
- [!!!][UPDATE] Require TYPO3 v11

10.2.6:
- [META] Set the EM conf version number to 10.2.6
- [BUGFIX] Use correct key "foreign" to index a row from Foreign
- [BUGFIX] Support resolving related redirects with query URL parts
- [BUGFIX] Show undecoded message line if it could not be decoded
- [BUGFIX] Throw an exception if the file to retrieve does not exist
- [BUGFIX] Do not try to publish files that neither exist on local nor foreign
- [RELEASE] Version 10.2.5 with non-empty bulk inserts

10.2.5:

- [META] Set the EM conf version number to 10.2.5
- [GIT] Ignore the .github folder when creating a production dist
- [COMMENT] Update the return annotation of findRedirectsByDynamicTarget
- [BUGFIX] Ensure the rowCount is always an integer
- [BUGFIX] Don't try to bulkInsert an empty data set
- [RELEASE] Version 10.2.4 with bug fixes

10.2.4:

- [META] Set the EM conf version number to 10.2.4
- [BUGFIX] Prevent infinite recursion when collecting records that are going to be published
- [BUGFIX] Remove the HostNameValidator
- [BUGFIX] Override database state if sys_file records have been changed
- [BUGFIX] Early fail the UniqueStorageTargetTest when EXT:scheduler is not installed
- [BUGFIX] Remove duplicate line which should have been removed
- [BUGFIX] Prevent undefined array key access in DatabaseUtility
- [TASK] Fix TestLabelLocalizer error
- [BUGFIX] Allow input fields with renderType inputLink
- [BUGFIX] Do not exclude columns which have an itemsProcFunc
- [BUGFIX] Do not ignore MM-records to excluded tables
- [BUGFIX] Use the group MM tablenames field instead of true/false
- [BUGFIX] Fix type error in FileEdgeCacheInvalidationService class
- [BUGFIX] Fallback to the foreignUid if localUid is null
- [BUGFIX] Add conditions to not break resetting backend user preferences
- [BUGFIX] Update the list of required tables
- [BUGFIX] Allow envelopes to grow arbitrarily huge
- [TESTS] Add missing ticket annotations for command tests
- [TESTS] Add functional test to assert that MM records can be marked as publishing
- [BUGFIX] Allow MM records to be marked as running
- [BUGFIX] Make RunTasksInQueueCommand compatible with symfony/console 4.4 and add test
- [TESTS] Fix tests for different dbal and TYPO3 versions
- [TESTS] Mock Result class which exists in composerMinInstall
- [TESTS] Fix DbConfigTestCommandTest, remove BackupCommandTest
- [BUGFIX] Ensure all (testable) commands are executable
- [BUGFIX] Modify FAL folder identifier to work with most 3rd party drivers
- [RELEASE] Version 10.2.3 with publishing indicator

10.2.3:

- [META] Set the EM conf version number to 10.2.3
- [BUGFIX] Correctly indent other icons in the changed content area
- [CODESTYLE] Fix indent and line length issues
- [DOCS] Add missing Event descriptions, reformat all files
- [CODESTYLE] Fix all CS issues in the Tests folder
- [BUGFIX] Add explanation to the failing TableGarbageCollectorTest result
- [CLEANUP] Remove unnecessary string concatenation from BackendUtilityTest
- [REFACTOR] Replace classes for constant access with the class that defines the constant
- [COMMENT] Add copyright blocks to new RunningRequest classes
- [BUGFIX] Deny publishing of a record that is being published, show the process in the View
- [BUGFIX] Mark all records as being published when bulk-publishing
- [BUGFIX] Write all records recursively to the running requests table
- [BUGFIX] Ensure all links in the publish overview mod in yellow rows are black
- [BUGFIX] Use a static property, because ViewHelper Singletons don't work
- [BUGFIX] Only check for publishing processes running in other processes
- [BUGFIX] Remove condition around garbage collector configuration
- [BUGFIX] Use count instead of select to count scheduler task rowsrows
- [FEATURE] Add RegisterViewHelper to set variables in templates that survive scope popping
- [BUGFIX] Move closing quote to the line it belongs
- [BUGFIX] Show the publish loader if redirects are publishing
- [BUGFIX] Use a random token to identify a request instead of the BE user token
- [BUGFIX] Set Records as not publishable if they are already publishing
- [BUGFIX] Replace publishing finished event with shutdown function
- [BUGFIX] Position the publish link instead of just the icon, show publishing records
- [FEATURE] Add Record::isPublishing to determine if a record is currently being published
- [CODESTYLE] Remove blank lines
- [BUGFIX] Fix label for missing garbage collector task
- [TASK] Add a publish tools test checking the existence of a garbage collector task for table tx_in2publishcore_running_request
- [REFACTOR] Move denial of publishing of running request from record to PublishingRequestIsRunningVoter
- [TASK] Do not show publish button if a request is running for publishing of record
- [BUGFIX] Fallback to the foreign property when identifying files
- [BUGFIX] Support doctrine/dbal < 2.11 again
- [BUGFIX] Replace what used to be fetchOne with fetchColumn
- [BUGFIX] Fix non-existing statement method calls in non-composer mode setups
- [BUGFIX] Fix array keys that are used in PHP 8 when using call_user_func_array
- [BUGFIX] Check if a TCA column MM key is set before accessing it
- [BUGFIX] Respect the actual sortby field from the TCA to detect if records were moved
- [BUGFIX] Skip FlexForm select fields without a foreign table
- [BUGFIX] Check if a key exists in the TCA instead of relying on unset key behavior
- [BUGFIX] Prevent undefined index access in DefaultRecordFinder
- [META] Replace extension/module icons
- [BUGFIX] Always preprend the eventListener for the publishing confirmation
- [BUGFIX] Set start depth to 1 for the SimplifiedOverviewAndPublishing root record
- [BUGFIX] Respect record language when attaching pages to parents
- [RELEASE] Version 10.2.2 with minor bug fixes

10.2.2:

- [META] Set the EM conf version number to 10.2.2
- [BUGFIX] Show the actual key for differences in site configs
- [BUGFIX] Check the actual delete field of a table to determine if the record is deleted
- [BUGFIX] Prevent access on undefined array key
- [BUGFIX] Include array key existence check when looking for group/db MM tables
- [BUGFIX] Format arrays as strings before comparing them with array_diff
- [BUGFIX] Check if deprecated config values are set before evaluation
- [TYPO] Fix message when there are no envelopes to flush
- [RELEASE] Version 10.2.1 with a lot of bugfixes

10.2.1:

- [META] Set the EM conf version number to 10.2.1
- [BUGFIX] Provide performance threshold for RCE HttpAdapter
- [REFACTOR] Use variables to prevent codestyle issues
- [CODESTYLE] Fix imports and length exceeding lines
- [CODESTYLE] Reformat config files
- [BUGFIX] Define RecordFinder/-Publisher as public services
- [BUGFIX] Use annotations instead of type hint and provide default value for array property
- [BUGFIX] Don't fetch redirects multiple times, fix redirect merging
- [BUGFIX] Resolves redirects by their t3 URI target
- [BUGFIX] Introduce TaskExecutionWasFinished Event
- [BUGFIX] Keep PageTsProvider disabled until the BE User was determined
- [BUGFIX] Declare missing return type for EnvelopeDispatcher::getFileObject
- [BUGFIX] Early return in EnvelopeDispatcher::getPublicUrl when a file could not be found
- [BUGFIX] Add fallback for empty paths (especially filePreviewDomainName)
- [BUGFIX] Streamline local/foreign props in Record and fix TypeError when calculating dirty props
- [BUGFIX] Correctly split identifier for Record::getPageIdentifier
- [BUGFIX] Cast string replacements to string before using it
- [BUGFIX] Add missing import of GeneralUtility in ContextServiceTest
- [BUGFIX] Use annotation instead of property type in DefaultRecordPublisher
- [BUGFIX] Annotate TYPO3 variables in ext_emconf.php
- [BUGFIX] Require typo3/cms-redirects for development
- [CODESTYLE] Fix CS issues and imports
- [BUGFIX] Rethrow the existing DBAL exception instead of creating a new one
- [BUGFIX] Process the configuration in ConfigContainerExporter again
- [BUGFIX] Replace non-existent class with its string representation
- [BUGFIX] Remove ToolsController check from AbstractController, inheritance was removed
- [BUGFIX] Fix message composing in MissingRequiredAttributesException
- [BUGFIX] Return correct property in VoteIfRecordShouldBeSkipped::getCommonRepository
- [BUGFIX] Declare missing property DefaultRecordPublisher::$visitedRecords
- [CLEANUP] Remove nullable modifier from DefaultRecordFinder injections
- [BUGFIX] Prevent registration of commands on sides which are not allowed
- [BUGFIX] Set correct return type for TaskRepository::findByExecutionBegin
- [BUGFIX] Ignore if "foreign_types" is used in inline MM relations
- [RELEASE] Version 10.2.0 with SimplifiedOverviewAndPublish

10.2.0:

- [META] Set the branch alias version number to 10.2.x-dev
- [META] Set the EM conf version number to 10.2.0
- [FEATURE] Merge SimplePublishing into SimpleOverviewAndAjax to create SimplePublishing
- [QA] Fix unit tests by providing correct constructor args and trimming superfluous whitespace
- [BUGFIX] Actually compare the given tablename in ShallowRecordFinder
- [DOCS] Update readme: better description, removed missing images, updated codacy badge
- [FEATURE] Automatically migrate SimpleOverviewAndAjax and SimplifiedOverviewAndPublishing to SimplifiedOverviewAndPublishing
- [DOCS] Document the new feature SimplifiedOverviewAndPublishing and its caveats
- [BUGFIX] Correctly prefetch files for storages and return fifo with appropriate key
- [FEATURE] Implement batch-prefetching for FalIndexPostProcessor, too
- [FEATURE] Speed up file operations by prefetching all files by their identifier
- [FEATURE] Support more features, process files, extract DB Queries to Repos
- [FEATURE] Use the shallow record as record to publish
- [META] Set branch alias for the branch feature/simple-publishing
- [FEATURE] Split RecordFinder->findRecordByUid by use case
- [FEATURE] Support most common and required events during record creation
- [CODESTYLE] Remove doubled whitespace from string concatenation
- [DEPRECATION] Deprecate static access to TcaProcessingService
- [CLEANUP] Remove useless "controls" section from the Inspect TCA Tool
- [BUGFIX] Ignore sys_redirect fields which must/can be different for the comparison
- [DEPRECATION] Deprecate RecordFactory getters from all events
- [BUGFIX] Limit breadcrumbs to the first page up in the hierarchy
- [FEATURE] Support the VoteIfRecordShouldBeIgnored event in the ShallowRecordFinder
- [BUGFIX] Do not show page records in breadcrumbs
- [BUGFIX] Set correct depth for records from ShallowRecordFinder
- [FEATURE] Support disablePageRecursion flag in ShallowRecordFinder
- [CLEANUP] Remove the event CommonRepositoryWasInstantiated
- [FEATURE] Replace SimpleOverViewAndAjax with SimplifiedOverviewAndPublishing
- [FEATURE] Split CommonRepository into finder and publisher classes
- [FEATURE] Merge simpleOverviewAndAjax and SimplePublishing
- [BUGFIX] Restore default excluded table "pages" when publishing
- [CLEANUP] Remove useless logging from RecordController
- [REFACTOR] Skip unnecessary ActionController parent
- [RELEASE] Version 10.1.1 with TATAPI subfolder and an actual TATAPI test

10.1.1:

- [META] Set the EM conf version number to 10.1.1
- [BUGFIX] Use subfolder inside of typo3temp to allow symlinking the location
- [BUGFIX] Add missing test to assert that the TATAPI works as expected
- [RELEASE] Version 10.1.0 with consolidated tasks API, redirect filter and admin DB compare tool

10.1.0:

- [META] Set the branch alias version number to 10.1.x-dev
- [META] Set the EM conf version number to 10.1.0
- [TESTS] Require the extensionmanager and fix loading order in Functional Tests
- [BUGFIX] Replace parent::class with the actual parent class (magic constant)
- [FEATURE] Add admin tool to scan the databases for simple differences
- [DOCS] Add missing docblocks in CompareDatabaseTool classes
- [FEATURE] Highlight differences and make transfering possible
- [FEATURE] Admin overview for compare function in tools module
- [DOCS] Add missing copyright blocks
- [FEATURE] Rewrite Tools to service based API
- [FEATURE] Rewrite tools registration with service configuration
- [FEATURE] Add Publish Redirects filter
- [FEATURE] Add association status filter
- [REFACTOR] Move find-redirect-query to repo
- [FEATURE] Persist the current filter in the session to keep it during pagination
- [REFACTOR] Use a DTO for the redirects filter
- [WIP] Add filter for redirect status
- [TASK] Filter redirects in publish_redirects module as in redirects_module
- [TASK] Move query on redirects to repository
- [BUGFIX] Unify and consolidate Tasks API, add garbage collection
- [REFACTOR] Move everything task-related to a component folder
- [BUGFIX] Remove obsolete Tasks
- [BUGFIX] Use ExtensionManagementUtility instead of not yet existing ExtensionUtility method
- [BUGFIX] Split service configuration of features and add logs condition
- [BUGFIX] Ensure types of identifiers when matching RTE content
- [BUGFIX] Index fetched MM records with all idFields
- [BUGFIX] Use all identifying fields of an MM record for its combinedIdentifer
- [TESTS] Move scheduled tests to 6:40 to reduce parallel load on packagist
- [BUGFIX] Allow a SysRedirect siteID to be null
- [BUGFIX] Support different controller names in the tools action menu
- [TYPO] Fix typo in upgrading instructions headline
- [TEST] Test if select relations are resolved and published
- [CLEANUP] Remove codeception remnants from TableCacheRepositoryTest
- [CLEANUP] Remove unused imports from UnitTestsBoostrap
- [CLEANUP] Remove legacy phpunit file
- [DEV] Detect ROOT_DIR on both Linux and Darwin
- [BUGFIX] Move the TcaProcessingService initialization to a method called after the logger injection
- [RELEASE] Version 10.0.0 without TYPO3 v9 support

10.0.0:

- [META] Set the branch alias version number to 10.0.x-dev
- [META] Set the EM conf version number to 10.0.0
- [!!!][FEATURE] Remove TYPO3 v9 support
- [BUGFIX] Ignore redirects source host if the following redirect's host is generic
- [BUGFIX] Ignore errors from storage FlexForms whose drivers were uninstalled
- [BUGFIX] Set a redirects host to '*' if it is selected as site_id during publishing
- [BUGFIX] Collect redirects redirecting to assigned redirects
- [BUGFIX] Properly select all redirects associated with a page
- [REFACTOR] Import all functions and constants
- [TESTS] Remove codeception, ignore phpunit result cache, fix testing matrix
- [TESTS] Rewrite tests following the official docs
- [BUGFIX] Collect publishing failures via real singleton and print exception messages
- [BUGFIX] Remove wrong service defintion which overwrites the RedirectSourceHostReplacement
- [BUGFIX] Cast all UIDs to int when publishing redirects
- [BUGFIX] Mark ForeignSiteIdentifierItemProcFunc as public
- [BUGFIX] Disable page recursion and cast pid to int in when publishing via page tree
- [TYPO] Rename losse to loose
- [BUGFIX] Mark PublishPageAjaxController as public
- [BUGFIX] Bring back all default constructor values for NullRecord
- [BUGFIX] Make FileIndexPostProcessor public
- [META] Add branch alias for 45637-rem-t3v9
- [BUGFIX] Actually publish instead of import the table in PublishCommand
- [REFACTOR] Streamline Import-/PublishCommand
- [CLEANUP] Remove superfluous empty lines, reformat build files
- [CODESTYLE] Remove empty lines, chop down multi-line statements
- [REFACTOR] Move all conditional event listener to Services.php
- [BUGFIX] Call TcaProcessingService::preProcessTca in the constructor
- [BUGFIX] Import constant PHP_EOL in TestResult
- [REFACTOR] Use a constant instead of property for a fixed string
- [CLEANUP] Remove unused methods, constants and properties
- [BUGFIX] Ensure all messages added to AbstractTask are strings
- [REFACTOR] Instantiate UidReservationService in the construtor instead of in-place
- [REFACTOR] Inject the extensions extConf instead of ExtensionConfiguration
- [REFACTOR] Simplify code flow in SshAdapter
- [REFACTOR] Explicitly set second argument for uniqid
- [REFACTOR] Use UnexpectedMissingFileException constructor instead of static factory method
- [REFACTOR] Use TooManyFilesException constructor instead of static factory method
- [COMMENT] Add missing copyright notice in AllSitesCommandException
- [REFACTOR] Use ForeignSiteConfigUnavailableException constructor instead of static factory method
- [REFACTOR] Simplify DocBlocks, streamline properties, update injections
- [REFACTOR] Use FileMissingException constructor instead of static factory method
- [REFACTOR] Use InvalidDynamicValueProviderKeyException constructor instead of static factory method
- [REFACTOR] Use InvalidPageIdArgumentTypeException constructor instead of static factory method
- [META] Ignore false positive on forgotten debug statements
- [BUGFIX] Use $threshold argument in TooManyFilesException message
- [BUGFIX] Make all fopen reads/writes binary safe
- [REFACTOR] Use static closures where appropriate
- [REFACTOR] Simplify nested if statements
- [BUGFIX] Extract value to variable before passing it to a function as referenced parameter
- [COMMENT] Ignore Exceptions, missing parent calls and non-looping loops
- [META] Ignore some control flow "errors" which are intentionally
- [BUGFIX] Define access level for all class constants
- [BUGFIX] Fix wrong DocBlocks
- [FEATURE] Define all missing return type hints
- [BUGFIX] Set the correct return type hint for Abs-X-Nodes
- [!!!][CLEANUP] Remove the unused AbstractTask::modifyConfiguration method
- [CLEANUP] Remove redundant DocBlocks
- [FEATURE] Inject the database connections in RawRecordService
- [BUGFIX] Declare the PermissionService public
- [BUGFIX] Replace all remaining occurrences of TcaProcessingService::getInstance
- [CLEANUP] Remove unused cache property from DatabaseSchemaService
- [FEATURE] User constructor injection and LoggerAwareInterface where possible
- [BUGFIX] Make CommonRepo connections nullable again
- [BUGFIX] Catch exceptions from AbstractDomainTest::determineDomainTypes
- [BUGFIX] Construct connections with a factory that throws exception
- [REFACTOR] Use more readable string keys in the DynamicValuesPostProcessor RegEx
- [BUGFIX] Correctly check for all Provider implementations in the ConfigContainer
- [CLEANUP] Remove parent constructor call in ActionController
- [CLEANUP] Remove unused imports
- [REFACTOR] Simplify conditions, statements and ease debugging
- [BUGFIX] Throw a specific exception on Local when the AllSitesCommand failed
- [FEATURE] Inject the dependencies of all commands
- [CLEANUP] Remove unused PageDoesNotExistException
- [CODESTYLE] Add blank line after opening tag
- [TYPO] Fix writing of documentation
- [FEATURE] Add method to add multiple records to the event RelatedRecordsByRteWereFetched
- [BUGFIX] Remove or fix wrong type hints
- [UPDATE] Require the latest version of typo3/testing-framework
- Wip: [WIP] Add return types
- [BUGFIX] Add missing return type hint for RecordController::publishRecord
- [CLEANUP] Remove redundant class DocBlocks
- [BUGFIX] Ensure the given envelope uid for the RCE command is an integer
- [CODESTYLE] Fix multiline indents
- [CODESTYLE} Enforce PSR-12 Rule "4.5 Method and Function Arguments"
- [CODESTYLE] Enforce PSR-12 rule "5.1 if, elseif, else"
- [BUGFIX] Rename all inherited parameters to their parent to
- [CLEANUP] Remove duplicate DriverInterface implementing
- [TESTS] Remove Bootstrap::initializeBackendRouter from functional bootstrap
- [BUGFIX] Add missing required PHP extensions "pdo" and "zip" to composer.json
- [REFACTOR] Replace all ternary "X ? X : null" with null coalesce operator
- [REFACTOR] Import all FCQNs in docs
- [META] Ignore some unsolvable PhpStorm inspections
- [CLEANUP] Remove unnecessary type casts
- [BUGFIX] Fix diverged identifier issue when resolving MM records
- [COMMENT] Add missing DocBlock for property tis in SkipTableVoter
- [TESTS] Set ApplicationContext via In2publishCore helper
- [CLEANUP] Remove unused imports
- [TYPO] Fix some types in the docs and labels
- [!!!][FEATURE] Add all (easy) return/type hints
- [CLEANUP] Remove unused method LabelService::getLabelFieldsFromTableConfiguration
- [CLEANUP] Remove unused method BaseRepository::quoteString
- [FEATURE] Implement the new doctrine/dbal API from 2.11.0
- [CLEANUP] Remove unnecessary catching of UnsupportedRequestTypeException
- [REFACTOR] Extract common parts from condition branches
- [CLEANUP] Remove redundant type casts or fix the false positive by annotation
- [REFACTOR] Import all classes in files where FQCNs are not required
- [REFACTOR] Resolve unnecessary local variables
- [CLEANUP] Remove extraneous arguments from fetchAllAssociative calls
- [CLEANUP] Remove unused imports, parameters, and variables
- [CLEANUP] Remove all remaining deprecated classes and methods
- [CLEANUP] Remove deprecated argument tableName from CR::getDefaultInstance
- [CLEANUP] Remove the deprecated optionality of the tableName argument in RecordFactory
- [CLEANUP] Remove the deprecated field tableName from BaseRepository
- [CLEANUP] Remove deprecated property identifierFieldName from BaseRepository
- [BUGFIX] Add various missing type hints
- [BUGFIX] Replace deprecated DBAL fetch methods
- [REFACTOR] Remove explicit default values
- [CLEANUP] Remove anything sys_domain table related
- [REFACTOR] Use existing variable instead of redundant array key access
- [DOCS] Fix wrong or missing links in docs
- [DEV] Change the continuation_indent_size editorconfig rule to intellij specific
- [FEATURE] Replace GU::getApplicationContext with Environment class
- [FEATURE] Replace TYPO3_branch constant with Typo3Version class
- [DOCS] Replace old SignalSlotDispatcher docs and examples with event listener
- [CODESTYLE] Enforce PSR-12
- [CLEANUP] Remove unused imports, separate import groups
- [CLEANUP] Remove outdated commands readme
- [CLEANUP] Remove unused imports from CommonRepositoryTest
- [FEATURE] Replace RequiredTablesDataProvider overruleTables signal with event
- [CLEANUP] Remove the unused SignalSlotDispatcher from ext_tables
- [FEATURE] Replace AbstractController checkUserAllowedToPublish signal with event
- [CLEANUP] Remove unused SignalSlotDispatcher from CommonRepository
- [FEATURE] Replace FolderPublisherService afterPublishingFolder signal with event
- [BUGFIX] Add type cast for untyped but annotated parameter
- [TEST] Update test files and test runner stuff
- [DOCS] Document publishing and tools module events
- [BUGFIX] Respect changes in the signal collectSupportPlaces for the event
- [FEATURE] Rewrite SkipEmptyTable to use the new event
- [FEATURE] Rewrite PublishSorting to use the new event
- [FEATURE] Rewrite RedirectCacheUpdater to use the new event
- [FEATURE] Create new event PhysicalFileWasPublished and rewrite FileEdgeCacheInvalidator to use that event
- [FEATURE] Rewrite NewsSupport to use the new event
- [FEATURE] Rewrite RefIndexUpdate to use the new event
- [FEATURE] Rewrite SysLogPublisher to use the new event
- [FEATURE] Rewrite PhysicalFilePublisher to use the new event
- [REFACTOR] Make the AssetTransmitter logger aware and set type hints
- [REFACTOR] Make the AdapterRegistry logger aware and remove redundant stuff
- [CLEANUP] Remove unused signal slot dispatcher from AdapterRegistry
- [REFACTOR] Rename RemoteFalDriver parameter targetFolderId to adhere to LSP
- [FEATURE] Rewrite CacheInvalidation to use the new event
- [REFACTOR] Implement LoggerAware in the SiteService to get the logger injected
- [FEATURE] Rewrite RedirectsSupport to use the new event
- [CLEANUP] Remove superfluous newline from CommonRepository
- [CLEANUP] Remove redundant tableName from PublishingOfOneRecord* events
- [BUGFIX] Do not unpack the support lines after event dispatching
- [BUGFIX] Restore original signal behavior of filterStorages
- [BUGFIX] Actually replace the afterRecordEnrichment signal with the new event
- [BUGFIX] Restore original signal arguments order for publishRecordRecursiveBeforePublishing and publishRecordRecursiveAfterPublishing
- [BUGFIX] Restore original signal arguments order for publishRecordRecursiveEnd
- [BUGFIX] Restore original signal arguments order for publishRecordRecursiveBegin
- [BUGFIX] Restore addition of support labels following CreatedDefaultHelpLabels event
- [FEATURE] Replace filterStorages signal with event
- [FEATURE] Replace collectSupportPlaces signal with event
- [FEATURE] Rewrite File/Fal-IndexPostProcessing to use the new event
- [FEATURE] Replace beforePublishing signal with event
- [BUGFIX] Restore original behavior of the relationResolverRTE signal and allow relatedRecords override in the event
- [FEATURE] Replace deprecated afterRecordEnrichment signal with event
- [FEATURE] Replace publishRecordRecursiveBeforePublishing and publishRecordRecursiveAfterPublishing signals with events
- [FEATURE] Replace publishRecordRecursiveEnd signal with event
- [FEATURE] Replace publishRecordRecursiveBegin signal with event
- [BUGFIX] Fix signal name in SignalSlotReplacement
- [FEATURE] Replace relationResolverRTE signal with event
- [FEATURE] Replace CommonRepository instanceCreated signal with event
- [FEATURE] Replace addAdditionalRelatedRecords signal with event
- [FEATURE] Replace rootRecordFinished signal with event
- [FEATURE] Replace RecordFactory::instanceCreated signal with event
- [REFACTOR] Make all events final
- [FEATURE] Replace isPublishable signal with event
- [FEATURE] Replace shouldSkipSearchingForRelatedRecordsByProperty signal with event
- [FEATURE] Replace shouldSkipSearchingForRelatedRecordsByFlexFormProperty signal with event
- [FEATURE] Replace shouldSkipSearchingForRelatedRecordsByFlexForm signal with event
- [FEATURE] Replace shouldSkipSearchingForRelatedRecords signal with event
- [FEATURE] Replace shouldSkipSearchingForRelatedRecordByTable signal with event
- [FEATURE] Replace shouldSkipFindByProperty signal with event
- [FEATURE] Replace shouldSkipFindByIdentifier signal with event
- [FEATURE] Replace shouldSkipEnrichingPageRecord signal with event
- [FEATURE] Replace shouldIgnoreRecord signal with event
- [FEATURE] Replace shouldSkipRecord signal with event
- [DOCUMENTATION] Add upgrade documentation with details about replacement of signal slots
- [FEATURE] Replace beforeDetailViewRender signal with event
- [FEATURE] Replace folderInstanceCreated signal with event
- [REFACTOR] Resolve extension scanner problems
- [BUGFIX] Do not throw an exception for an invalid value if none was given
- [UPDATE] Move commands registration to Services.yaml
- [UPDATE] Use FQCN to register plugin and module controller
- [CLEANUP] Remove useless logger
- [UPDATE] Register modules and plugins with extension key instead of package format
- [DEPRECATION] Deprecate tools registration using a controller name instead of the class
- [REFACTOR] Use FQCN to register the redirects module controller
- [REFACTOR] Use FQCN to register a plugin controller
- [UPDATE] Import new PageRepository namespace
- [CLEANUP] Resolve version comparisons for TYPO3 < v10
- [!!!][UPDATE] Drop support for TYPO3 v9
- [RELEASE] Version 9.5.1 with support for TSconfig markers in FlexForms

9.5.1:

- [META] Set the EM conf version number to 9.5.1
- [BUGFIX] Ignore missing slashes which some drivers omit (mistakenly)
- [DOCS] Add missing condition for folderFileLimit setting
- [BUGFIX] Support TSconfig markers in FlexForm additional_where_clause
- [BUGFIX] Catch exceptions from the ForeignEnvironmentService before they appear in actions
- [RELEASE] Version 9.5.0 with performance improvements, sorting publishing, and more features and fixes

9.5.0:

- [META] Set the branch alias version number to 9.5.x-dev
- [META] Set the EM conf version number to 9.5.0
- [CLEANUP] Remove superfluous empty lines and add ones where appropriate
- [CLEANUP] Remove unused imports and superfluous lines in import sections
- [BUGFIX] Always create the additional redirects fields if the extension is installed
- [BUGFIX] Allow null as return value in FalIndexPostProcessor::getStorage
- [BUGFIX] Register the RedirectsSupport SQL slot in all TYPO3 modes
- [BUGFIX] Use the deprecated Connection::fetchAll for TYPO3 v9 compatibility
- [FEATURE] Include the sites config in the sysinfo export
- [FEATURE] Respect rootLevel, allowedTables and skip empty tables or missing PIDs
- [BUGFIX] Respect TCA rootLevel and PAGES_TYPES allowedTables when searching for related records by PID
- [FEATURE] Move the rootLevel decision to the TcaService, merge all SkipTableVoters
- [FEATURE] Create SkipRootLevelVoter to skip searching for records in tables not allowed on that page
- [FEATURE] Lazy register SkipTableByPidVoter, lazy init tables
- [FEATURE] Add Signal to skip searching for records by pid if no such PID exists
- [BUGFIX] Skip post processing of sys_file records which do not exist
- [BUGFIX] Return the DatabaseFields slot arguments as array
- [BUGFIX] Add SQL and TCA for sys_redirects only when EXT:redirects is loaded
- [CODESTYLE] Add empty line before multi-line condition
- [BUGFIX] Add missing return type hints commands
- [FEATURE] Lazily inspect if tables are empty, log query stats
- [FEATURE] Lazy register the SkipTableVoter and use an object instead of class name
- [BUGFIX] Remove spamming debug logger
- [REFACTOR] Use a single method to set the rows index
- [FEATURE] Add a SkipTableVoter to skip querying empty tables
- [BUGFIX] Try to get a cached record when searching by an identifier
- [CLEANUP] Remove useless logging of relation recursion
- [CODESTYLE] Remove blank line between import groups in BaseRepo
- [BUGFIX] Use eq() instead of like() for int values (fixes #84 closes #85)
- [BUGFIX] Set the parent record of translated records
- [FEATURE] Add feature "publishSorting" to publish the sortings of all affected records
- [REFACTOR] Simplify the sorting collecting and publishing
- [CODESTYLE] Indent the config definer on the "chop level"
- [BUGFIX] Respect the publishSorting enable setting
- [CODESTYLE] Reformat code
- [REFACTOR] Get name of sorting field from tca
- [REFACTOR] Add return type hints
- [CLEANUP] Remove unused use statements and initialization of variables
- [CLEANUP] Remove unused variable
- [BUGFIX] Fix field name for enable field in PublishSortingDefiner
- [COMMENT] Adjust comments
- [BUGFIX] Remove static pid used for testing
- [TASK] Enable publishing of changes on sorting
- [BUGFIX] Removed duplicate file name (closes #83)
- [RELEASE] Version 9.4.0 with redirects support

9.4.0:

- [META] Set the branch alias version number to 9.4.x-dev
- [META] Set the EM conf version number to 9.4.0
- [BUGFIX] Fix exception when publishing deleted redirects
- [TASK] remove unused import and use right indent
- [TASK] enable publish deletion of redirects
- [BUGFIX] Remove redundant module.m5 option, add docs for redirects support
- [BUGFIX] Publish redirects which were filtered becasue they are deleted and unpublished
- [BUGFIX] Add test to identify site config language differences
- [BUGFIX] Provide missing icons for TYPO3 v9
- [BUGFIX] Always publish language originals
- [TASK] publish default and translated page
- [BUGFIX] Add missing exception code
- [TYPO] transission -> transmission
- [BUGFIX] Never relate redirects by their PID
- [BUGFIX] Share the RecordFactories runtimeCache between instances
- [BUGFIX] Skip URL generation for deleted pages
- [BUGFIX] Publish translation original when publishing via specific record publishing
- [BUGFIX] Check if the pointer field is set before accessing it
- [TASK] publish default and translated page
- [BUGFIX] Do not enable redirects support when EXT:redirects is not loaded
- [BUGFIX] Typecast properties to be used in str_replace
- [FEATURE] Support TYPO3 redirects
- [DOCS] Add known issue for missing icons in publish redirect module in v9
- [CODESTYLE] Reformat TypoScript and add editorconfig rules
- [BUGFIX] Add extbase table mapping for TYPO3 version 9
- [BUGFIX] Remove trailing method call comma for PHP 7.2 compat
- [BUGFIX] Replace Command::SUCCESS constant with 0 for TYPO3 v9
- [BUGFIX] Skip constraint for deleted redirects if none are deleted on foreign
- [FEATURE] Rebuild redirects cache after publishing a redirect
- [BUGFIX] Hide the edit button for deleted records
- [FEATURE] Exclude fully deleted redirects from the module
- [BUGFIX] Include deleted redirects
- [FEATURE] Remove discarded concepts, add support in simplePublish
- Wip: [FEATURE] Finish backend module for redirect publishing
- Wip: [FEATURE] List all redirects with state and publish button
- Wip: [FEATURE] Add Backend Module to publish redirects
- [BUGFIX] Ignore pages without sites
- [BUGFIX] Resolve redirects recursively
- [FEATURE] Resolve relations to TYPO3 v10 redirects and publish them with changed source_host
- [BUGFIX] Do not log failed site searches for disconnected pages.
- [BUGFIX] Output non-breaking space if no preview URL could be rendered.
- [FEATURE] Show dirty properties for deleted records
- [BUGFIX] Show foreign properties on dirty properties foreign side
- [TASK] show properties if deleted on right side
- [TASK] show deletion state and history button
- [BUGFIX] Skip DataHandler cmdmap with integer keys in getPageIdentifier
- [BUGFIX] Detect pid from newly created records
- [REFACTOR] Move ssh connection debug option to ssh connection definer
- [BUGFIX] Do not select from sys_domain if table does not exist
- [GIT] Don't export the Build folder into releases
- [RELEASE] Version 9.3.1 with fixes for TCA pre proc., empty pointer values and deleted records

9.3.1:

- [META] Set the EM conf version number to 9.3.1
- [BUGFIX] Define TCA user fields as unfit for relation resolving
- [BUGFIX] Limit "treatRemovedAndDeletedAsDifference" to actual removed and deleted records
- [BUGFIX] Fallback to foreign's sys_file_processedfile sys_file pointer value
- [RELEASE] Version 9.3.0 with publish page context menu entry, file edge cache invalidation, and much more-

9.3.0:

- [META] Set the branch alias version number to 9.3.x-dev
- [META] Set the EM conf version number to 9.3.0
- [FEATURE] Add option to treat different levels of deletion differently
- [BUGFIX] Do not retry to search for a site which can not be found
- [BUGFIX] Correctly detect deleted states of records removed from local
- [FEATURE] Display removed records with a black background and X-Icon
- [FEATURE] Add option to treat removal as difference to deletion
- [FEATURE] Clear caches of all related pages when publishing files
- [FEATURE] Add file edge cache invalidator
- [FEATURE] Add publish page option to page tree context menu
- [FEATURE] Add enable config option for feature contextMenuPublishEntry
- [FEATURE] Add translations, document dumb menu entry
- [FEATURE] Ask external voters if a record can be published
- [WIP][FEATURE] Add a context menu action to publish a page
- [DOCS] Add known issue about broken foreign file preview urls
- [BUGFIX] Add missing return type hints in Record/Interface
- [BUGFIX] Hide duplicate sys_file records
- [BUGFIX] Skip the attempt to generate a preview URL for page ID 0
- [TYPO] Fix some typos in FolderRecordFactory developer guide comments
- [REFACTOR] Move TCA record label construction logic to TcaService
- [BUGFIX] Add additionWhere only if it's not empty
- [BUGFIX] Remove error level from performance tests and raise the warning limits
- [BUGFIX] Use DBAL count method to count instead of SQL function name as column
- [BUGFIX] Support sql escape sequence in FlexForm foreign_table_where
- [RELEASE] Version 9.2.0 with env vars, internal_type file_reference and config debug

9.2.0:

- [META] Set the branch alias version number to 9.1.x-dev
- [META] Set the EM conf version number to 9.2.0
- [BUGFIX] Use provided editRecord VH and fix RecordHistory VH return URL
- [FEATURE] Debug provider specific config in "show config" and sysinfo export
- [FEATURE] Support the use of env vars in the yaml config
- [BUGFIX] Allow empty database password (e.g. for local development)
- [DOCS] Add a security notice about public yaml config files
- [DOCS] Update the example configuration to encourage the use of env vars
- [DOCS] Add the guide about configuration post processing
- [FEATURE] Support the use of env vars in the yaml config
- [DOCS] Update installation for new core version
- [FEATURE] Support TCA type group internal_type file_reference
- [FEATURE] Support internal type file_reference
- [BIGFIX] Log "unauthorized" if no backend user is yet logged in
- [RELEASE] Version 9.1.0 with translated record handling and support info

9.1.0:

- [META] Set the branch alias version number to 9.1.x-dev
- [META] Set the EM conf version number to 9.1.0
- [BUGFIX] Run tasks after publishing files and folders
- [FEATURE] Handle translated records as a special kind of record
- [BUGFIX] Include translated records in changed state calculation
- [FEATURE] Identify translations of records as special records, display with flag
- [BUGFIX] Do not attempt to modify preview URLs of files which already are full qualified
- [BUGFIX] Inherit the correct FQCN from the changed EXT:logs controller
- [BUGFIX] Allow FlexForm config arrays without TCEforms index
- [BUGFIX] Prevent PageTS caching before ext_tables and the TCA is loaded
- [FEATURE] Show support info in publish tools module index
- [DOCS] Add known issue about typo3/cms-redirects
- [BUGFIX] Build compare URIs with the correct arguments for cHash calculation
- [BUGFIX] Do not rely on the internal implementation of ArrayObject
- [RELEASE] Version 9.0.2 with fixed RCE option type

9.0.2:

- [META] Set the EM conf version number to 9.0.1
- [BUGFIX] Ensure all RCE command arguments, options and names are strings
- [RELEASE] Version 9.0.1 with stability improvements

9.0.1:

- [META] Set the EM conf version number to 9.0.1
- [BUGFIX] Add page records to pages again
- [BUGFIX] Add missing type hints for BackendUtility::getDomainFromPageIdentifier
- [BUGFIX] Pass properties to be merged as strings to string functions
- [BUGFIX] Ensure the returned uid of a sent envelope is always an int
- [FEATURE] Add performance tests
- [RELEASE] Version 9.0.0 with TYPO3 v10 compatiblity

9.0.0:

- [META] Set the stability to stable
- [META] Set the branch alias version number to 9.0.x-dev
- [META] Set the EM conf version number to 9.0.0
- [TEST] Update RecordTest to test that adding "language parents" of pages is allowed
- [!!!][FEATURE] Support TYPO3 v10
- [BUGFIX] Ignore sys_file_metadata tstamp and crdate by default
- [COMMENT] Streamline copyright comment according to definition
- [BUGFIX] Allow searching for l10Parents despite of excluded tables
- [CODESTYLE] Apply PSR-12 and editorconfig
- [BUGFIX] Do not add related pages twice
- [BUGFIX] Use sites for all frontend links
- [CODESTYLE] Fix line breaks for long line in ext_localconf
- [FEATURE] Add option to disable the foreign key fingerprint check
- [BUGFIX] Use the new approach to preview links also in the Compare plugin
- [BUGFIX] Correctly assemble preview URLs for site & sys_domain based preview links
- [DOCS] Be more verbose about setting the filePreviewDomainNames
- [BUGFIX] Ensure the remaining path after extracting the host part from an uri is a string
- [REFACTOR] Replace user_error with trigger_error
- [DEPRECTATION] Deprecate unused getFirstDomain from DomainService
- [BUGFIX] Use normalized Uris to build the file preview URL
- [BUGFIX] Identify sites without host correctly and also check local
- [BUGFIX] Return an empty domain if the target page can not be found
- [BUGFIX] Return an empty domain if the page's site could not be identified
- [BUGFIX] Early return when searching domains for records with uid 0
- [RELEASE] Version 9.0.0-rc2 with various bug fixes

9.0.0-rc2:

- [BUGFIX] Change the log level of record publishing logs to info
- [BUGFIX] Reduce "Task execution results" log severity to info
- [BUGFIX] Reduce severity of sys_log publishing to info
- [BUGFIX] Convert TYPO3 v10 log level names to integers for comparison
- [BUGFIX] Set the log level to the level's name for TYPO3 v10
- [BUGFIX] Return missing exit code after command execution
- [CODESTYLE] Reorder imports and add missing blank line in FileProvider
- [REFACTOR] Use shorthand syntax for array destructuring
- [CLEANUP] Remove legacy commandController registrations
- [BUGFIX] Add missing enable condition for context specific commands
- [CLEANUP] Remove unused logger from all affected files
- [COMMENT] Add missing license information in RunTasksInQueueCommand
- [CODESTYLE] Add missing return type in PageDoesNotExistException
- [BUGFIX] Remove all restrictions for searching page language parents
- [BUGFIX] Skip definition entries that are not an array
- [DOCS] Add known issue about broken sorting detection
- [BUGFIX] Use the language parent to get sites and do not fall back to sys_domain in TYPO3 v10
- [RELEASE] Version 9.0.0-rc1 with TYPO3 v10 support.

9.0.0-rc1:

- [BUGFIX] Force exception if the in2publish_core cache does not exist
- [CODESTYLE] Reorder use statements and copyright comments
- [BUGFIX] Remove second argument for BackendUtility::getPagesTSconfig
- [REFACTOR] Extract and move SSH command assembling to the parent class
- [CODESTYLE] Add PSR-12 constant visibility to all class constants
- [BUGFIX] Use newer LanguageService class namespace
- [DOCS] Remove stale documentation, update requirements and fix typos
- [CLEANUP] Remove unwanted file from vcs
- [CLEANUP] Remove unused function imports and DbUtil::isTableExistingOnLocal method
- [REFACTOR] Simplify all commands
- [BUGFIX] Rewrite all CommandControllers as symfony commands
- [BUGFIX] Use the backend router to process module paths and generate module urls
- [CODESTYLE] Remove superfluous whitespace after list and braces
- [BUGFIX] Add mapping for warning test result severity
- [BUGFIX] Replace PATH_site with Environment::getPublicPath
- [BUGFIX] Add missing cache initialization
- [BUGFIX] Call to parent::__construct in the ActionController if it exists
- [BUGFIX] Remove sys_domain from default ignored tables
- [UPDATE] Raise supported TYPO3 version to 10.1
- [BUGFIX] Add missing test result severity class mapping
- [BUGFIX] Replace removed BackendUtility::getModTSconfig with the new way of accessing user TS
- [REFACTOR] Move logging conf to ext_localconf, reformat and reorder ext_tables
- [REFACTOR] Reorder ext_localconf and replace call_user_func with immediate execution
- [BUGFIX] Replace every access to extConf with the new API method
- [REFACTOR] Introduce constants for rce/tatpi adapter registration
- [CLEANUP] Remove RealUrlSupport
- [DEPRECATION] Deprecate and replace typo3conf as default configuration folder
- [CLEANUP] Resolve all version_compare calls
- [CLEANUP] Remove the environment command controller
- [UPGRADE] Resolve all upgrade TODOs
- [META] Add branch alias for TYPO3 v10 development
- [UPDATE] Raise PHP version requirements to at least 7.2 (like TYPO3 v9 minimum)
- [UPDATE] Raise TYPO3 version constraint to 9-10
- [BUGFIX] Support PAGE_TSCONFIG_* marker in additional where clause
- [FEATURE] Introduce new voting signals to skip record resolving by flexform
- [FEATURE] Add column name to the list of information passed to the slot
- [CODESTYLE] Remove empty line
- [FEATURE] Add signals to skip related records in FlexForms
- [RELEASE] Version 8.5.0 with tests, warningOnLive and don't publish when cancelled

8.5.0:

- [META] Set the branch alias version number to 8.5.x-dev
- [META] Set the EM conf version number to 8.5.0
- [BUGFIX] Explicitly search for any translation of any records
- [DOCS] Add docs how to render an alternative field from pages in the POM
- [BUGFIX] Add missing translation
- [BUGFIX] Translate "stage" and "production" system to german
- [BUGFIX] Check if files exist before publishing
- [FEATURE] Add feature to paint the foreign system typo3 status bar
- [FEATURE] Add WarningOnLive to color the foreign backend status bar
- [BUGFIX] Stop any other click handler if publishing confirm was cancelled
- [FEATURE] Add test to check the foreign configuration format and values
- [TEST] Add test to ensure sys_categories are always resolved
- [BUGFIX] Add acceptance and functional tests folders for codeception
- [TEST] Remove the LOG configuration to prevent log processing and writing
- [TEST] Use blob instead of mediumblob for compatibility issues
- [TEST] Restructure tests on codeception basis
- [DOCS] Add tests run instructions
- [TESTS] Update coverage for CommonRepository and set IN2PUBLISH_CONTEXT
- [TESTS] Add IN2PUBLISH_CONTEXT env var to the test runner
- [TESTS] Test content to image relation resolving
- [TESTS] Set up second database to test CommonRepository function
- [TESTS] Make Travis execute the codeception tests
- [TESTS] Configure sqlite database and enable database depdendent BackenUtility tests
- [TESTS] Introduce codeception and port all tests
- [RELEASE] Version 8.4.5 with sys_category publishing fix

8.4.5:

- [META] Set the EM conf version number to 8.4.5
- [BUGFIX] Prevent empty config keys to result in config merge conflicts
- [DOCS] List options which can be overridden by Page/UserTS
- [DOCS] Add detailed information about user TS / page TS config options
- [BUGFIX] Identify and process combined identifier in AbstractRecordActionLinkVH
- [BUGFIX] Allow relations to sys_catgory despite having MM_opposite_field
- [DOCS] Annotate config options that can be overridden with PageTS/UserTS
- [RELEASE] Version 8.4.4 with file publishing fix (group preprocessor)

8.4.4:

- [META] Set the EM conf version number to 8.4.4
- [BUGFIX] Move MM_opposite_field to the list of forbidden group fields
- [RELEASE] Version 8.4.3 with stability fixes

8.4.3:

- [META] Set the EM conf version number to 8.4.3
- [BUGFIX] Include single quotes in marker replacement because the value will be quoted itself
- [BUGFIX] Forbid processing of foreign side relations for inline, select and group
- [CODESTYLE] Fix whitespace and newline CS issues in SCSS files
- [CODESTYLE] Fix code style in tests
- [CODESTYYLE] Correctly indent testing docker-compose.yml
- [CLEANUP] Remove unused imports from all affected files
- [CODESTYLE] Introduce empty line before return annotation
- [BUGFIX] Display errors and warnings also after building the record
- [BUGFIX] Use the data from the FlexForm to resolve inline elements
- [BUGFIX] Ensure changedRelatedRecords returns records only once
- [TEST] Provide docker test env, test runner script, travis config and fix all tests
- [COMMENT] Annotate correct variable type for ToolsController jsonFile argument
- [BUGFIX] Fix return value of findByPropert(y|ies) for cached records
- [COMMENT] Fix return annotation for Builder
- [CLEANUP] Remove unused instantiation of the letterbox
- [CLEANUP] Remove unused imports
- [TYPO] Fix some typos in the docs
- [BUGFIX] Add missing id for system export button label
- [DEV] Remove travis test file because these tests are not executed anymore
- [BUGFIX] Remove superfluous css unit from 0 value
- [DEV] Add bash script to compile sass in docker
- [REFACTOR] Import all unnecessary FQCNs
- [REFACTOR] Use short array syntax
- [CODESTYLE] Resolve PSR-12 parameter list code style issue
- [CODESTYLE] Resolve PSR-12 else if code style issues
- [CODESTYLE] Resolve PSR-12 keyword short form code style issues
- [BUGFIX] Use the correct charset for the foreign database
- [TYPO] Fix spelling of being
- [REFACTOR] Simplify the unique instance registration in FalIndexPostProcessor
- [RELEASE] Version 8.4.2 with fox for files publishing, multiline additional_where and PHP compat

8.4.2:

- [META] Set the EM conf version number to 8.4.2
- [BUGFIX] Ensure the unserialized extConf is a string
- [BUGFIX] Replace spl_object_id (PHP 7.2) with spl_object_hash (PHP 5.2)
- [BUGFIX] Ignore (match) multiline WHERE parts in additional_where clauses
- [BUGFIX] Ensure files are indexed at least once (reserveSysFileUids disabled)
- [BUGFIX] Add translated pages of TYPO3 v9 as fake pages_language_overlay records
- [RELEASE] Version 8.4.1 with deprecation, loop fix and MM_opposite_field for group relations

8.4.1:

- [META] Set the EM conf version number to 8.4.1
- [DOCS] Add all missing code block language annotations
- [DEPRECTATION] Prepare removal of CommonRepository::convertToRecord by deprecating it
- [BUGFIX] Add missing methods isForeignRecordDeleted isLocalRecordDeleted to RecordInterface
- [BUGFIX] Include the MM_opposite_field in TCA type group preprocessing
- [BUGFIX] Prevent infinite recursion in addChangedRelatedRecordsRecursive
- [CLEANUP] Remove the publishRecordRecursive action from the allowed module actions (enterprise feature)
- [BUGFIX] Ensure redirects to the index action target the RecordController
- [CLEANUP] Remove unused LogLevel import from AbstractController
- [BUGFIX] Hide pages which are deleted and do not contain pages in the overview module
- [BUGFIX] Remove deprecated table name argument from CommonRepository::getDefaultInstance
- [BUGFIX] Log all properties if a combined identifier could not be constructed
- [RELEASE] Version 8.4.0 with sysinfo export and fix disappearing tool buttons

8.4.0:

- [META] Set the EM conf version number to 8.4.0
- [META] Set the branch alias version number to 8.4.x-dev
- [FEATURE] Add tools module to export relevant debugging information
- [BUGFIX] Add missing test class key to tests that returned warnings
- [BUGFIX] Use the column names as they are returned (as string)
- [FEATURE] Add the database schema to the sysInfo output
- [BUGFIX] Ignore missing config paths when masking protected values and fallback for TYPO3 < v9
- [FEATURE] Add sysinfo download and upload possibilities
- [FEATURE] Add system information module which contains server, system, and TYPO3-information as well as test results
- [BUGFIX] Tools menu: show all entries in smaller view
- [RELEASE] Version 8.3.2 with findPropertiesByProperties method call fix

8.3.2:

- [META] Set the EM conf version number to 8.3.1
- [BUGFIX] Add missing method call arguments
- [RELEASE] Version 8.3.1 with better publishing feedback and false negative test fix

8.3.1:

- [META] Set the EM conf version number to 8.3.1
- [BUGFIX] Collect errors during publishing and display to the user if sth. went wrong
- [REFACTOR] Move the loglevel to message severity translator method to an Utility method
- [REFACTOR] Move the log level to severity converter to the abstract controller
- [BUGFIX] Display publishing errors in the flash message after publishing
- [BUGFIX] Rely on database values to check if connections are identical
- [RELEASE] Version 8.3.0 with new DB test, deprecations and test/type fixes

8.3.0:

- [META] Set the EM conf version number to 8.3.0
- [META] Set the branch alias version number to 8.3.x-dev
- [BUGFIX] Use TYPO3 v9 compat version of the random generator
- [FEATURE] Add test to detect if the used foreign database is different from foreign.database setting
- [DEPRECATION] Prepare removal of BaseRepository::identifierFieldName by deprecating it
- [DEPRECATION] Prepare removal of BaseRepository::tableName by deprecating it
- [CLEANUP] Remove unused constant with regex to parse a specific exception message
- [BUGFIX] Ensure the pid is an int for all requests except for file related modules
- [COMMENT] Add empty lines before return annotations
- [BUGFIX] Do not treat the number of affected rows as error information
- [COMMENT] Place copyright comment in ConfigurationUtility above imports
- [BUGFIX] Ignore the table's autoincrement and comment when comparing databases
- [BUGFIX] Respect definition keys when merging default config values
- [BUGFIX] Set correct return type annotation for moveForeignFile
- [RELEASE] Version 8.2.2 with multiline regex fix, exclude tables in RTE relations

8.2.2:

- [META] Set the EM conf version number to 8.2.2
- [BUGFIX] Improve the "order by" additional_where regex to support newlines
- [BUGFIX] Try dispatch the RTE relation resolver signal and log exceptions
- [CLEANUP] Remove unused methods getFlexFormDefinitionSource and resolveFlexFormSource from CommonRepository
- [BUGFIX] Respect excludedTableNames when resolving string (RTE) releations
- [RELEASE] Version 8.2.1 with non-composer mode compat

8.2.1:

- [META] Set the EM conf version number to 8.2.1
- [BUGFIX] Check if the constant TYPO3_COMPOSER_MODE exists before acessing it
- [RELEASE] Version 8.2.0 with new signal, better RTE and inline-relation support

8.2.0:

- [META] Set the EM conf version number to 8.2.0
- [CLEANUP] Remove duplicate condition from if statement
- [BUGFIX] Ensure type safety on UID identifier value
- [BUGFIX] Support inline relations without foreign_field
- [CLEANUP] Ignore foreign_record_defaults because it was removed in TYPO3 v8
- [BUGFIX] Fix string concatenation and inline if priority issue
- [REFACTOR] Remove line breaks from function call
- [BUGFIX] Use lazy quantifier for TCA marker matching
- [DOCS] Add a guide to help developers understand and create publishing tasks
- [BUGFIX] Ignore FlexForm Data that was parsed into wrong data types by TYPO3
- [META] Set the branch alias version number to 8.2.x-dev
- [FEATURE] Add signal to enable additional RTE content relation examination
- [BUGFIX] Support any RTE configuration in FlexForm text/input fields
- [RELEASE] Version 8.1.1 with test enhancements, type safety and CLI dispatcher autodetect

8.1.1:

- [META] Set the EM conf version number to 8.1.0
- [BUGFIX] Detect the cli dispatcher automatically, add an override option
- [BUGFIX] SimpleOverviewAndAjax: Deleted pages should be shown as deleted and not moved
- [BUGFIX] Prevent the log table from filling up with false positives during the ImportTableCommand.
- [BUGFIX] Add the missing configuration definition for the foreign configuration (backup settings)
- [CLEANUP] Remove any traces of the formerly per yaml defined log level setting
- [BUGFIX] Add test to detect the missing default FAL storage
- [BUGFIX] Prevent exceptions for missing annotations
- [BUGFIX] Ensure the strftime value is an integer
- [RELEASE] Version 8.1.0 with configurable folder file limit

8.1.0:

- [META] Set the branch alias version number to 8.1.x-dev
- [META] Set the EM conf version number to 8.1.0
- [BUGFIX] Log folderFileLimit exceeding, always render the flash message, clean up controller
- [BUGFIX] Do not search for the fileadmin folder in foreign instance tests (fixes #64)
- [CLEANUP] Remove the unused threshold from FodlerRecordFactory
- [TASK] Update year in copyright footer
- [BUGFIX] Let DBAL take care of identifier escaping
- [BUGFIX] Use the connection for the staging level not a new local one
- [FEATURE] Introduce a configuration option for the folder file limit
- [BUGFIX] Check the extConf array before accessing it
- [META] Update extension icon (upgrade to SVG)
- [CLEANUP] Remove superfluous empty line from locallang.testing.xlf
- [BUGFIX] Add test to check if the foreign dispatcher is callable and the context is correct
- [BUGFIX] Support lowercase (case insensitive) "and" in TCA additional where clauses
- [BUGFIX] Allow buildForeignDatabaseConnection to return null and log connection exceptions (fixes #60, fixes #59)
- [BUGFIX] Allow the deleteAlike action from EXT:logs in the tools module
- [BUGFIX] Set strings as default DB initCommands and process it that way (fixes #63)
- [TASK] Improve T3URN detection regex and move it to a constant
- [BUGFIX] Use lowercase command identifier
- [BUGFIX] Remove type hint and add null type to the return annotation of CommonRepository::findByIdentifierInOtherTable
- [RELEASE] Version 8.0.3 with TCA user error prevention, CLI dispatching fix and T3URN parsing enhancement

8.0.3:

- [META] Set the EM conf version number to 8.0.3
- [DOCS] Add known issues in the overview module in TYPO3 v9
- [BUGFIX] Enhance the T3 URN detection regex
- [BUGFIX] Use relative path of the foreign working directory to dispatch CLI calls (fixes #62, fixes #61)
- [BUGFIX] Prevent errors on empty user TCA
- [RELEASE] Version 8.0.2 with strict_types, production settings, softref and LL fixes

8.0.2:

- [META] Set the EM conf version number to 8.0.2
- [BUGFIX] Prevent compression of already optimized CSS files
- [BUGFIX] Inlcude input fields with softref in the canHoldRelation array
- [BUGFIX] Ensure the inspected text for RTE relations is a string
- [BUGFIX] Use legacy LanguageService namespace as long as TYPO3 v8 is supoprted
- [BUGFIX] Ensure the LabelService returns a string
- [BUGFIX] Remove strict type declaration for merged identifier
- [RELEASE] Version 8.0.1 with ZIP-installation fixes, TYPO3 URN support and initialization error handling

8.0.1:

- [META] Set the EM conf version number to 8.0.1
- [BUGFIX] Add missing getter for language related fields in TcaService
- [BUGFIX] Delay in2publish_core configuration until autoload information is available
- [BUGFIX] Prevent (elevated) errors when the extConf is not yet set
- [BUGFIX] Support non-composer installations by using the core/bin/typo3 cli (resolves #58)
- [BUGFIX] Prevent exceptions during test instantiation
- [BUGFIX] Catch the TypeError thrown in the Publish Tools Module when DB is not reachable
- [TASK] Set version to 8.0.0-dev
- [BUGFIX] Support TYPO3 URNs
- [DOCS] Rename foreign options in error messages
- [DOCS] Update docs to reflect new TYPO3 cli interaction (fixes #53 #54)
- [RELEASE] Version 8.0.0 with TYPO3 v8 & v9 support

8.0.0:

- [META] Set the EM conf version number to 8.0.0
- [!!!][FEATURE] Support TYPO3 v8 & v9
- [BUGFIX] Fix the compare view by using the right domain and protocol
- [BUGFIX] Add the protocol after domains if required
- [BUGFIX] Use the request host if the local site is configured with "/"
- [BUGFIX] Do not resolve the page record instance and support site configs
- [BUGFIX] Test the php binary and foreign document root separately
- [FEATURE] Allow the definition of arbitrary environment variables
- [BUGFIX] Use correct label IDs for missing root pages
- [DOCS] Add an example documentation about the __UNSET feature
- [CLEANUP] Always use the default TYPO3 flash message renderer
- [BUGFIX] Decouple the ForeignSysDomainTest from the SshConnection by requiring the virtual remote connection test
- [FEATURE] Add getLabelAltForceFromTable to the TCA Service
- [BUGFIX] Use the unset feature to remove not selected elements from definition sections
- [COMMENT] Add caching todo for performance
- [META] Update extension dependencies to the correct TYPO3 and PHP versions
- [BUGFIX] Don't query for page uid 0 rows
- [BUGFIX] Ensure arguments passed to strnatcmp are strings
- [BUGFIX] Limit the query and select only the first row when querying for records by uid
- [BUGFIX] Use the correct side's DB connection
- [BUGFIX] Identify the pages domain also from site configurations
- [CODESTLYE] Move all imports between class doc block and copyright comment
- [COMMENT] Update all copyright notices from docblock to comment
- [REFACTOR] Import all functions
- [COMMENT] Remove auto-generate todos
- [CLEANUP] Remove TYPO3 v7 setDBinit access from db init status command
- [REFACTOR] Use the already late static bound class constant instead of get_called_class
- [BUGFIX] Exclude the site configuration from the statusall command in TYPO3 v8
- [BUGFIX] Remove the call to the non existing parent constructor in AbstractCommandController
- [BUGFIX] Add missing comparison value to check the sys_domain count for TYPO3 v8
- [BUGFIX] Use the extbase FlexFormService as long as supporting TYPO3 v8
- [CODESTYLE] Wrap long lines and fix comparison line breaks in accordance to PSR-2
- [CLEANUP] Remove unused imports
- [COMMENT] Add missing doc blocks
- [CLEANUP] Remove unused import
- [BUGFIX] Ignore translated pages in the local and foreign domain test for TYPO3 v9
- [FEATURE] Support flux file relations
- [FEATURE] Add TYPO3 v9 slug TCA processor
- [CLEANUP] Remove unused import from In2publishCoreDefiner
- [BUGFIX] Check for already visited records not only hierachy downwards
- [FEATURE] Fix sys_domain fetching and support TYPO3 v9 site configurations
- [BUGFIX] Convert file and folder mask internally to integers (strict types)
- [TYPO] Add missing "found" in sys_domain check label
- Update .travis.yml
- [BUGFIX] Remove HostNameValidator for Foreign DB Definer
- [BUGFIX] Use the determined connection for retrieving envelopes in the letterbox
- [BUGFIX] Convert all query error info arrays to json encoded strings
- [BUGFIX] Prevent missing query results by always removing all default constaints
- [BUGFIX] Use DBAL for FolderRecordFactory
- [BUGFIX] validate return value of remoteFalDriver->createFolder
- [BUGFIX] Make BuildResourcePathViewHelper compatible for TYPO3 V9
- [BUGFIX] Add typecast for deleteRecord operation
- [DOCS] Add Known isuses for ile deletion process
- [BUGFIX] Make GetMergedPropertyViewHelper V9 compatible
- [BUGFIX] UidReservationService: Add fetch statemment to receive result
- [TASK] Add missing return type hints in RecordInterface
- [TASK] Add all return type declarations to the RecordInterface
- [BUGFIX] Set correct return type Record::addChangedRelatedRecordsRecursive
- [TASK] Require at least php 7.0
- [BUGFIX] Ensure GetPropertyFromStagingDefinitionViewHelper::getProperty returns a string
- [BUGFIX] Check if arbitrary table names passed by _GP are actually a table
- [REFACTOR] Build the local db connection once in BackendUtility::getPageIdentifier
- [BUGFIX] Use the provided table to query for a PID
- [BUGFIX] Remove wrong type hints from CommonRepository
- [BUGFIX] Remove the return type hint from RecordFactory::makeInstance because it can also return null
- [BUGFIX] Cast the property to quote to string before passing it to ::quoteString
- [BUGFIX] Allow getFirstDomainInRootLineFromRelatedRecords to return null
- [BUGFIX] Allow Record::getParentRecord to return null
- [BUGFIX] Let an empty PK secret stay a string since ssh2_auth_pubkey_file expects it
- [CLEANUP] Remove unused and legacy impoirt of DatabaseConnection in Fal test
- [BUGFIX] Catch all throwables instead of just exceptions
- [BUGFIX] Fix wrong argument type hint
- [BUGFIX] Fix wrong retrieval of table names
- [TASK] Add missing strict type declarations
- [CLEANUP] Refactor and cleanup various classes
- [CLEANUP] Refactor various classes
- [BUGFIX] Fix type check in business logic of cleanUpBackups()
- [TASK] Migrate UidReservationService to DBAL
- [TASK] Ensure that the ControllerContext is available when needed
- [CLEANUP] Fix deprecation warning
- [BUGFIX] Fix orderBy method argument
- [TASK] Avoid error in Publish Overview module
- [BUGFIX] Fix version constraints for typo3/cms-core
- [CLEANUP] Remove the unused DatabaseConnection import from SysLogPublisher
- [FEATURE] Rewrite the TableCacheRepo to use dbal
- [FEATURE] Update the RealUrlTask to clear caches with dbal
- [CLEANUP] Remove the unused db connections from the FakeRecordFactory
- [BUGFIX] Remove all restrictions prior to envelope burning
- [FEATURE] Migrate the SysFileService to dbal
- [FEATURE] Switch Letterbox Envelop burning to dbal
- [FEATURE] Migrate to the cores FlexFormService
- [BUGFIX] Add missing command controller parent constructor call to trigger deprecation notices
- [CODESTYLE] Add missing surrounding whitespace in ::fetchStorages
- [BUGFIX] Pass the update arguments in the correct order in Letterox::sendEnvelope
- [FEAUTRE] Update FileIndexFactory to use dbal
- [FEATURE] Extract order by statements from the additional where clause
- [CODESTYLE] Write first of twice method call also in one line
- [REFACTOR] Remove the unused database connections from the ReplaceMarkerService constructor
- [BUGFIX] Exclude file uploads from pid detection
- [BUGFIX] Pass the task where expressions unpackable
- [FEATURE] Use dbal for all queries in the BackendUtility
- [FEATURE] Change alle view helpers and db accesses to be able to run tests
- [FEATURE] Use dbal insert method in SysLogPublisher
- [BUGFIX] Rewrite the basic set of view helpers used in the overview module
- [FEATURE] Rewrite findPropertiesByProperty to work with dbal
- [BUGFIX] Remove all listeners from the foreign connection
- [FEATURE] Replace the legacy database connection with dbal in the DatabaseUtility
- [BUGFIX] Replace ExtensionManagementUtility::extRelPath with ExtensionManagementUtility::extPath
- [UPDATE] Require TYPO3 v8 to v9
- [RELEASE] Version 7.3.0 with PHP 7.3, DCE and Flux support

7.3.0:

- [RELEASE] Version 7.3.0 with PHP 7.3, DCE and Flux support
- [META] Update version information and changelog for the 7.30 release
- [FEATURE] Support FlexForm Sections and DCE with arbitrary el keys
- [FEATURE] Support file_reference used by flux for file relations
- [BUGFIX] Check for the method instead of the already existing class
- [BUGFIX] Add fluidtypo3/flux support for TYPO3 gte v8
- [TASK] Allow support for PHP version 7.3

7.2.0:

- [!!!][BUGFIX] Change default configuration for pages ignoreFieldsForDifferenceView - please read upgrade instruction

7.1.1:

- [TASK] branch alias for develop

7.1.0:

- [FEATURE] allow usage of nav_title in record index view
- [FEATURE] Enable numeric index overrule if the arrays key is named "definition"
- [BUGFIX] Add a possibility to unset unwanted array values
- [BUGFIX] Sort configs by the order of the overruling config
- [DOCS] Add infos about in2publish extbase commands
- [DOCS] Add documentation about how configuration is merged
- [TASK] Add missing flash message for tools modulue
- [BUGFIX] Avoid error while activating the extension due to not existent cache
- [TYPO] Fix typos in testing xlf

7.0.5:

- [BUGFIX] Reset collected cache clear entries after writing the task
- [BUGFIX] Use first registered controller actions when creating a link

7.0.4:

- [BUGFIX] Avoid endless loop
- [CLEANUP] Improve indentation
- [CLEANUP] Remove superfluous class import
- [CLEANUP] Remove superfluous empty lines

7.0.3:

- [BUGFIX] MySQL-Strict-Mode: Cache-Clear-Task (and others) are not executed when publishing

7.0.2:

- [BUGFIX] Fix markup in changelog file

7.0.1:

- [BUGFIX] Merge configuration more decently
- [BUGFIX] Handle optional configuration nodes appropriately

7.0.0:

- [TYPO] Correctly write "applies"
- [DOCS] Remove adapter configuration from example yaml
- [COMMENT] Ignore coupling of objects in AbstractController
- [CODESTYLE] PSR 2 fixes for TestResult.php
- [CLEANUP] Remove code which was moved to another class
- [DOCS] Update requirements and limitations
- [BUGFIX] Detect an empty testStatus array as no-error-state
- [BUGFIX] Pass the related records to their edit and history link view helper
- [BUGFIX] Always assign the publishing state for configured controllers
- [BUGFIX] Append additional Tests in the ext_tables instead of overwriting the whole array
- [TASK] Update test rendering for v8
- [BUGFIX] Pass null to the FolderRecordFactory if no folder has been selected
- [BUGFIX] Register adapter as early as possible
- [DOCS] Add a section about configuring in2publish_core in the extension manager
- [BUGFIX] Respect context when building defintion and building defaults
- [!!!][BUGFIX] Configure Adapter type in in2publish_core extConf
- [BUGFIX] Load RealUrl definition ony if realurl is installed
- [BUGFIX] Pass all arguments as single paramters
- [BUGFIX] Merge extConf only if it's an array
- [!!!][REFACTOR] Lazy create validation objects
- [CLEANUP] Remove duplicate config processing
- [CLEANUP] Remove unused imports and revert erroneous codestyle formats
- [CODESTYLE] Reindent all ConfigDefiner
- [BUGFIX] Resovle relations from the root page (ID=0)
- [BUGFIX] Add excluded related tables for realurl in the default config
- [FEATURE] Split the array  node type and don't compare array keys in the normal array node
- [DEV] Replace jshint and jscs with eslint
- [COMMENT] Update DocBlocks and add missing throws annotations
- [!!!][CLEANUP] Drop ExtConfAccessor
- [BUGFIX] Catch any internal SshAdapter exception and return it as Response
- [BUGFIX] Output errors and set correct exit code for failed remote table backup
- [!!!][REFACTOR] Rename log table to tx_in2publishcore_log
- [BUGFIX] Repair simpleOverviewAndAjax (html & js)
- [CLEANUP] Remove unused method getSubFolderOfCurrentUrl from Folderutility
- [!!!][CLEANUP] Remove the deprecated internal log reader
- [CLEANUP] Remove unused class Remote\Folder
- [REFACTOR] Move SysLogPublisher to feature folder
- [REFACTOR] Move SimpleOverviewAndAjax to feature folder
- [!!!][REFACTOR] Move refIndex updater to feature folder
- [!!!][REFACTOR] Move cache invalidation to feature folder
- [BUGFIX] Only enable the realurl anomaly if realurl is activated
- [REFACTOR] Move news support to feature folder
- [!!!][REFACTOR] Move realurl support to feature folder
- [CLEANUP] Remove disableUserConfig from normal configuration
- [!!!][REFACTOR] Move log level configuration to extconf
- [!!!][FEATURE] Rewrite configuration management to extensible structure
- [CLEANUP] Remove unused configuration values from the foreign example configuration
- [DOCS] add IN2PUBLISH_CONTEXT note
- [REFACTOR] Always include modules CSS in the backend
- [CLEANUP] Remove useless signal from RecordController
- [CLEANUP] Remove unused PageModule CSS
- [CLEANUP] Remove unused JS for setting classes which are not styled
- [CLEANUP] Remove custom message styling (already fully styled by TYPO3)
- [CLEANUP] Remove useless full-width class which had no effect anyway
- [REFACTOR] Replace custom btn class with bootstrap button classes
- [RELEASE] Version 6.2.2 with larger flex relation resolving (inline, input)
- [BUGFIX] Enable support for flex form relation type inline
- [BUGFIX] Support relations in inputs with wizard in flex forms
- [BUGFIX] Add missing TcaService method to get the TCA deleted field

6.2.2:

- [BUGFIX] Enable support for flex form relation type inline
- [BUGFIX] Support relations in inputs with wizard in flex forms
- [BUGFIX] Add missing TcaService method to get the TCA deleted field

6.2.1:

- [REFACTOR] Rewrite and register BackendModule.js as require module
- [REFACTOR] Rework ext_tables.php
- [CLEANUP] Remove forgotten qunit css file
- [CLEANUP] Remove unmaintained clickdummy
- [CLEANUP] Remove remaining unused libraries as bootstrap.js and jquery
- [CLEANUP] Remove any JS related hack and workaround for TYPO3 < 7.6
- [CLEANUP] Remove unused JS library pikaday.js
- [CLEANUP] Remove replaced workflow filter listener from BackendModule.js
- [CLEANUP] Remove obsolete/unused DateTimePicker.js and PageModule.js
- [REFACTOR] Remove RecordFactory::hasCachedRecord
- [BUGFIX] Ignore failing signals in RecordFactory
- [REFACTOR] Shorten RecordFactory's currentOverallRecursion to more meaningful currentDepth
- [REFACTOR] Use config field for RecordFactory instead of multiple single fields
- [BUGFIX] Log the object's class if the class is different from BeUserAuth
- [BUGFIX] Include ds_pointerField for flex fields again
- [BUGFIX] Log the backend user's type if no UID could be found

6.2.0:

- [FEATURE] Add option to include sys_file_references by PID again
- [META] Update branch alias to 6.1.x

6.1.0:

- [BUGFIX] Check all records to add and log and remove wrong values
- [DOCS] Rescue the FKFP guide from the depths of the git history
- [DEPRECATION] Deprecate the interal log API reader
- [FEATURE] Add optional integration of the external TYPO3 log API reader vertexvaar/logs
- [DEV] Update editor cfg
- [BUGFIX] Add newline after logo (better UI if CSS failed to load)
- [CODESTYLE] Reduce lines in ext_localconf
- [REFACTOR] Remove CommonRepository::getPropertiesForIdentifier
- [CLEANUP] Remove unsipported jscsrc rule validateJSDoc
- [BUGFIX] Directly log the publish permission voting results to use the assoc. keys
- [BUGFIX] Replace duplicated signal implementation with its valid predecessor
- [REFACTOR] Register extTables-PostProcessing hook upon ToolsRegistry usage
- [REFACTOR] Extract publishing permission check to service
- [CLEANUP] Remove deprecated SshConnection
- [META] Update license
- [FEATURE] Add signal for custom record relation resolving
- [CODESTYLE] Fix condition indentation in CommonRepo

6.0.4:

- [BUGFIX] Skip permission evaluation only on ResourceStorage
- [BUGFIX] Decouple the publishing confirmation from the overlay
- [DOCS] Add hint about in2publish' RCC feature
- [DOCS] Fix typos in ReqsAndLimits

6.0.3:

- [COMMENT] Update annotations of TableCommandController
- [BUGFIX] Dump debug log RCE response as strings
- [BUGFIX] Remove arguments from command identifiers
- [BUGFIX] Log error and output of failed remote table backups as strings
- [CLEANUP] Remove Overall.js
- [DOCS] Add Codacy Badge to readme
- [BUGFIX] Ignore completely removed records
- [DEV] Correctly link the extension in the virtual document root
- [TESTS][BUGFIX] Add record uid property for test records

6.0.2:

- [BUGFIX] Skip relation resolving for records that do not exist
- [REFACTOR] Simplify condition in CommonRepository
- [CLEANUP] Remove unreachable break statements
- [REFACTOR] Replace redundant method calls with local field
- [REFACTOR] Simplify condition and reduce code in FakeRecordFactory
- [REFACTOR] Move not implemented methods from rFALd to abstract superclass
- [REFACTOR] Reduce return points in Letterbox::sendEnvelope
- [REFACTOR] Remove superfluous variable assignment

6.0.1:

- [BUGFIX] Prefix all commands to avoid command name intersections (fixes #42)
- [BUGFIX] Use correct config path to moved foreignRootPath value (fixes #43)
- [BUGFIX] Initialize the tests array before acessing it
- [DOCS] Remove superfluous empty lines from changelog

6.0.0:

- [CLEANUP] Remove unused imports from FolderRecordFactory and SSH functions test
- [BUGFIX] Warn about superfluous config entries but allow them
- [BUGFIX] Move foreign config values to correct place in correct ConfigDefProvider
- [BUGFIX] Prevent overruling and disclosure of config value for foreign
- [DOCS] Fix links to configuration example files
- [!!!][BUGFIX] Reorder the configuration structure to separate adapter independent parts
- [FEATURE] Add test to check if the sleected adapters are valid and can be loaded
- [FEATURE] Support custom full qualified label identifier for test result messages
- [CODESTYLE] Add empty line between parameter and return annotation
- [DOCS] Fix tiny typo in php-ssh2 compilation walkthrough
- [BUGFIX] Do not initialize CommonRepository if foreign's db connection is not available
- [BUGFIX] Add empty templates for tool actions that aren't callable before config is set
- [BUGFIX] Check for SSH key file  existence only if ssh drivers are selected
- [CODESTYLE] Reduce TcaProcService's cache instantiation to a single line
- [DOCS] Update ssh2 compilation walkthrough for dF-Servers
- [CLEANUP] Remove deprecated attributes from container-VH usage
- [REFACTOR] Extract string auto casting to extra method
- [REFACTOR] Split SshBaseAdapter's configuration validation into mulitple methods
- [REFACTOR] Split PhysicalFilePublisher into multiple methods
- [REFACTOR] Shorten dbSchemaService variable name
- [CODESTYLE] Reindent chopped down attributes in Record FunctionBar
- [REFACTOR] Merge identical code of RecordEdit-VH and RecordHistory-VH in superclass
- [COMMENT] Add missing suppression annotation for LanguageService access
- [REFACTOR] Merge identical configDevProv for ssh based connections
- [CLEANUP] Remove unused code of the removed F&FF
- [BUGFIX] Remove duplicate css file inclusion
- [BUGFIX] Check if tests are registered for virtual tests
- [FEATURE] Add command controller to execute tests on the CLI
- [BUGFIX] Set RPC envelope uid in options instead of command
- [CODESTYLE] Break import statement at use before reaching line length limit
- [CLEANUP] Remove most of the unused CSS
- [FEATURE] Add labels to the adapter registration
- [FEATURE] Decouple communications adapter and use a registry to reference implementations
- [FEATURE] Introduce alternative Edit- & HistoryLinkVH attributes
- [BUGFIX] Support flex field DS default field if ds_pointer is not set
- [BUGFIX] Always include ext_emconf to get the uncached extension version
- [BUGFIX] Increase Task configuration and message field size to support huge installations
- [REFACTOR] Wrap main Template in Fluid HTML tag
- [BUGFIX] Build the return URL for the actual module and append all related query params
- [BUGFIX] Do not evaluate permissions of any FAL storage while extracting file information
- [REFACTOR] Replace record action uri VHs with link VHs

5.11.0:

- [BUGFIX] Do not directly resolve relations from pages to sys_file_reference
- [BUGFIX] Use the UID and relation targets of a sys_file_reference records as its label instead of just uid_local
- [BUGFIX] Set comamnd exit codes to be lower than 254 and finally document these
- [COMMENT] Exchange annotation of Record with RecordInterface
- [FEATURE] Add Anomaly to update sys_refindex on foreign after publishing
- [COMMENT] Ignore the coupling between objects of the ConfigurationUtility because there is currently no other solution
- [BUGFIX] Allow GeneralUtility to create an instance of ConfigurationUtility
- [BUGFIX] Ignore subsequent starts in the ExecutionTimeService
- [BUGFIX] Allow the deletion of log entries from the tools module again
- [BUGFIX] Prevent recursion of non-array values for superfluous index identification
- [BUGFIX] Ensure that the tested setting excludeRelatedTables is an array
- [BUGFIX] Use a specific class to style in2publish' modules
- [DOCS] Fix the example commands to configure foreign's webserver user on foreign
- [BUGFIX] Use the first of all allowed actions per tool as default action for the tool
- [BUGFIX] Use GeneralUtility instead of new operator to instantiate the ConfigurationUtility
- [COMMENT] Automatically return the right context specific class name for CfgUtility::getInstance()
- [BUGFIX] Make ConfigurationUtility's private methods protected
- [BUGFIX] Prohibit accessing the foreign database withtout any configuration
- [CLEANUP] Remove superfluous JavaScript
- [REFACTOR] Move the rendering of the tools footer to the layout
- [REFACTOR] Move the fluid condition inside of class attribute to maintain the HTML code structure
- [FEATURE] Add ToolsRegistry to dynamically add more tools to the module
- [TYPO] Fix typo in german label moduleselector.flush_registry.description
- [BUGFIX] Do not try to initialize the CommonRepo if the config check failed
- [BUGFIX] Remove duplicate introduction menu entry for "show configuration"
- [BUGFIX] Add the missing VHNS declaration
- [REFACTOR] Move in2publish tools menu and menu entries to partials
- [DOCS] Use 127.0.0.1 as example for the forwarded port host name instead of localhost
- [BUGFIX] Update the class name of the renamed (formerly known as:) TcaService (fixes #39)
- [DOCS] Exchange libssh2 with php-ext ssh2
- [BUGFIX] Add missing disabled state for in2publish buttons
- [DOCS] Add missing new line after tag in change log

5.10.1:

- [BUGFIX] Handle initialization of invalid or removed FAL storages oder drivers
- [BUGFIX] Compare lower string representations of values and search term in Worklfow Module
- [BUGFIX] Use the uid of the active page when reverting the history
- [DOCS] Remove superfluous whitespace from contribution guideline
- [DOCS] Add contribution guidelines
- [DOCS] Create the introduction to the editors manual (related #2)
- [DEV] Raise dev-master branch alias version

5.10.0:

- [CLEANUP] Remove the pagetreenodesstripes mixin (better version in enterprise edition)
- [BUGFIX] Calculate and add the cHash to the page compare preview URL
- [REFACTOR] Rename Domain\Service\TcaService to TcaProcessingServcie to reduce confusion with Service\TcaService
- [REFACTOR] Move RPC/Envelope API to Communication folder
- [FEATURE] Introcude TAT API (TemporaryAssetTransmission) and deprecate SshConnection
- [CLEANUP] Remove chmodEnabled from SshBaseAdapter
- [REFACTOR] Move desctructor to SshBaseAdapter
- [REFACTOR] Extract main ssh functionality to shared adapter class
- [COMMENT] Fix return type annotation for TCA delete field value
- [REFACTOR] Replace all occurences of ObjectManager with GeneralUtility
- [REFACTOR] Get rid of ObjectManager in RecordFactory at all
- [CLEANUP] Remove unused property objectManager from RecordFactory
- [REFACTOR] Get rid of all extbase injections
- [CODESTYLE] Single-line all simple GU::makeInstance calls
- [REFACTOR] Extract duplicate code to get drivers from FAL storages to utility class
- [COMMENT] Add suppression annotations for coupling in classes which got new imports
- [REFACTOR] Simplify identifier conversion for case insensitive storages
- [CLEANUP] Remove developer exceptions
- [REFACTOR] Replace all generic exceptions with at least In2publishCoreException and add missing expcetion codes
- [REFACTOR] Merge dirty property detection conditions into single method
- [REFACTOR] Resolve double condition body
- [REFACTOR] Change GeneralUtility:deprecationLog to ::logDeprecatedFunction for simplicity
- [BUGFIX] Implode the array of error messages before escaping the html
- [TYPO] Fix various typos found in test methods and messages
- [REFACTOR] Change GeneralUtility:deprecationLog to ::logDeprecatedFunction for simplicity
- [TASK] Update an error message in InlineProcessor
- [TYPO] Fix UnitTestBootstrap exception message
- [REFACTOR] Replace all self:: with static:: (where possble)
- [REFACTOR] Resolve all __FUNCTION__ constants
- [REFACTOR] Replace all calls to get_class with the late bound static FQCN constant
- [REFACTOR] Convert all arrays to short syntax
- [REFACTOR] Replace overlooked occurrence of a string class reference
- [REFACTOR] Use PHP 5.5 magic class constant for all class references
- [CODESTYLE] Add trailing comma in default tca processor list
- [CLEANUP] Remove unused import in StatusCommandController
- [BUGFIX] Support RTE for input fields if enabled in defaultExtras
- [COMMENT] Remove superfluous empty lines from AbstractProcessor

5.9.0:

- [BUGFIX] Retrieve pid from the given record information if it couldn't be determined (fixes in2code-de/in2publish#19)
- [REFACTOR] Call GeneralUtility::_GP only once for pageId
- [REFACTOR] Use distinct variable for get parameter page id
- [BUGFIX] Throw specific exception if allow_url_fopen is disabled and log all fopen errors (fixes #32)
- [FEATURE] Add a new test and docs to ensure SFTP requirements are met (related #32)
- [REFACTOR] Replace all class names and arrays in ext_localconf and ext_tables with class constants and array short snytax

5.8.2:

- [BUGFIX] Inject fal storages before filtering post processed fal records
- [BUGFIX] Include the Plugin definition as reference because it might be defined later (fixes #31)
- [DOCS] Add known limitation about moved/renamed folders
- [CLEANUP] Remove TYPO3 6.2 flashMessage rendering partial and related IsCompatVersionViewHelper
- [CLEANUP] Remove module link generation for TYPO3 6.2
- [CLEANUP] Remove access to TYPO3 6.2 specific globals
- [CLEANUP] Remove png module icon registration for TYPO3 6.2 and png files

5.8.1:

- [BUGFIX] Resolve MM relations with the correct identifier
- [DOCS] Add configuration setting dependencies to example config
- [BUGFIX] Display correct error if foreign document root does not exist
- [BUGFIX] Return failed response if RCE adapter failed to initialize
- [BUGFIX] Disable workflow publish button in page and list module when publishing is not available
- [LOGS] Log the specific reason the SshAdapter configuration validation failed
- [BUGFIX] Use FQCN for Core ArrayUtility to in Utility namespace
- [BUGFIX] Convert exception to string before passing it to the flash message
- [BUGFIX] Backport Extbase method because the Core version throws an exception if a value does not exist
- [REFACTOR] Replace all usages of Extbase ArrayUtility with the Core version

5.8.0:

- [BUGFIX] Replace file on foreign with new file in different location after it got moved and replaced (fixes #28)
- [DOCS] Fix link to Reqs and Limits
- [DOCS] Add link to requirements and limitations
- [DOCS] Add requirements and limitations abstract
- [DOCS] Fix some typos and wordings in readme
- [DOCS] Remove outdated information table from Docs/Readme
- [DOCS] Remove trailing whitespace from README
- [DOCS] Fix of a readme typo
- [DOCS] Update of readme.md with some more information and screenshots
- [BUGFIX] Remove text decoration by hover on icons in filelist
- [BUGFIX] Remove text decoration by hover on icons
- [BUGFIX] Use FolderCreateMask instead of FileCreateMask for folder permissions (fixes #27)
- [BUGFIX] Use RCE API to retrieve createMasks for SshConnection (related #25, fixes #26)
- [CLEANUP] Remove unused imports from ForeignEnvironmentService
- [BUGFIX] Always apply remote permissions on newly created files and folders when ssh2_sftp_chmod is not available
- [TYPO] Fix a typo in the error message if retrieving the foreign DB init failed
- [CLEANUP] Remove unused SshConnection from RemoteStorage (fixes #24)
- [BUGFIX] Require Spyc in ext_tables by the correct path (now same as in ext_localconf) (fixes #23)
- [FEATURE] Add option to configure the foreign CLI TYPO3 context (fixes #22)
- [DOCS] Update LocalConfig documentation to match current example configuration
- [CODESTYLE] Add blank lines to separate sections more clearly
- [COMMENT] Ignore superglobals access in StatusCommandController::dbInitQueryEncodedCommand because it's required
- [COMMENT] Ignore coupling of objects in SshConnection because those classes are not used
- [BUGFIX] Add SshConnectionTest as a dependency for ForeignDatabaseTest
- [BUGFIX] Catch RCE adapter exceptions and use the result as test result to identify SSH connection problems
- [BUGFIX] Properly overload the controller action if the database could not be initialized
- [REFACTOR] Lazy initialize the ssh session of SshAdapter
- [REFACTOR] Lazy initialize the RCE adapter
- [BUGFIX] Use RCE API to initialize the foreign database
- [BUGFIX] Add default TCA processor for TCA type imageManipulation
- [BUGFIX] Do not select envelopes from the letterbox if the database is not connected
- [FEATURE] Use caching for createMasks in SshConnection
- [DEPRECATION] Deprecate rewritten parts of SshConnection
- [FEATURE] Replace all SshConnection command related method calls with new RCE API
- [FEATURE] Rewrite command related parts of SshConnection as RCE API
- [COMMENT] Fix return type annotation of AbstractTask::getMessage
- [BUGFIX] Only build foreign database connection for table commands when on local
- [BUGFIX] Ensure table exists in the given database before creating a backup of it
- [CLEANUP] Remove leading empty line in Databaseutility method
- [BUGFIX] Add missing output values to status:all command
- [REFACTOR] Print separate lines of configuration values instead of manually breking the line
- [REFACTOR] Extract supported SSH2 key fingerprint hashing algorithm to class member
- [CODESTYLE] Rewrap multiline function call
- [CODESTYLE] Add missing comma on trailing array element
- [COMMENT] Add missing blank line between description and annotation
- [TYPO] Fix typo in SshConnection exception message
- [BUGFIX] Prevent workflowcontainer scrolling in non natural scrolling backend modules
- [TASK] Add storageUid to exception message if remote storage could not be found
- [FEATURE] Add backend test to detect if regular logins are permitted on foreign

5.7.0:

- [FEATURE] Add signal to FolderPublisherServive after publishing a folder
- [REFACTOR] Inline only once used variable
- [FEATURE] Add new signal tight after creation of folder records
- [DOCS] Elaborate about setting the auto_increment correctly for disabled reserveSysFileUids

5.6.0:

- [BUGFIX] Typecast sftp connection to int for use with ssh2 wrapper.
- [BUGFIX] Do not instantiate UidReservationService on foreign
- [FEATURE] Support remote setting [SYS][setDBinit]
- [FEATURE] Add possibility to remove in2publish_core related registry entries in the tools module
- [COMMENT] Fix constructor annotation for Envelope parameter $request
- [FEATURE] Enable MM relations of inline records
- [BUGFIX] Show correct uid of the FAL storage with a different driver

5.5.1:

- [TYPO] Fix typo3 in warning label for folders with too many files
- [COMMENT] Replace some words with better matches
- [COMMENT] Add missing annotation for record in FalIndexPostProcessor::getStorage
- [TYPO] Fix typo in SysLogPublisher::publishSysLog log notice message
- [TYPO] Fix typo in EnvelopeDispatcher->prefetchLimit DocBlock
- [REFACTOR] Replace all occurrences of Record with its interface in RecordFactory
- [API] Add lockParentRecord to RecordInterface
- [API] Add getColumnsTca, hasAdditionalPropertyand getPropertiesBySideIdentifier to RecordInterface
- [CLEANUP] Remove unused import from FileIndexPostProcessor
- [REFACTOR] Replcae all occurrences of Record with RecordInterface in DomainService
- [API] Add addRelatedRecords to RecordInterface and add type hint to setParentRecord
- [API] Add setParentRecord to RecordInterface
- [API] Add isChangedRecursive to RecordInterface
- [CODESTYLE] Chop down long method signatures from CommonRepository
- [REFACTOR] Extract duplicate code to FileController::tryToGetFolderInstance
- [REFACTOR] Replace all Record type hints and annotations in CommonRepository and ReplaceMarkerService
- [API] Add methods addRelatedRecord and isParentRecordLocked to RecordInterface
- [API] Add getRelatedRecordByTableAndProperty to RecordInterface
- [REFACTOR] Replace all type annotations of Record with RecordInterface in FolderRecordFactory
- [API] Add local-/foreignRecordExists to RecordInterface
- [BUGFIX] Update branch alias version in composer.json
- [BUGFIX] Detect files on the remote file system after renaming folders
- [REFACTOR] Extract variable that indicates if a files record got renamed
- [REFACTOR] Remove unnecessary argument variables
- [BUGFIX] Enhance file limit excess exception message pattern
- [BUGFIX] Respect file identifier context when PostProcessing
- [BUGFIX] Check if file exists in storage before deleting it
- [BUGFIX] Display warning if a folder contains too many files to be processed for the publish files module
- [TASK] Add sys_file.last_indexed to default excluded fields configuration

5.5.0:

- [DOCS] Add defaults, test data and documentation for disable auto_increment sync feature
- [TASK] Raise TYPO3 compatibility to match 8 LTS
- [BUGFIX] Prevent duplicate file indexing via slot
- [BUGFIX] Prefer local storage for file publishing
- [FEATURE] Enable File PostProcessing for reserveSysFileUids disabled
- [BUGFIX] Check for explicit disabled reserveSysFileUids feature
- [BUGFIX] Select correct default folder when nothing was selected
- [CODESTYLE] Chop down line exceeding method call
- [FEATURE] Automatically remove duplicate sys_file indices and support renaming
- [CLEANUP] Remove redundant setting of a storage uid
- [FEATURE] Set publishing relevant information for files and make them publishable
- [FEATURE] Implement index based file list diff
- [DOCS] Enhance FAQs
- [DOCS] Add a note about UTF8filesystem must be false (fixes #15)
- [CLEANUP] Replace ViewArrayViewHelper with cores debugging viewhelper (fixes #18)
- [TEST] Add unit tests for new REDIRECT_IN2PUBLISH_CONTEXT support
- [FEATURE] Support REDIRECT_IN2PUBLISH_CONTEXT environment variable (fixes #12)
- [TEST] Also mock isConnected and connectDB for DB related tests
- [DOCS] Remove enterprise version tables from example config and docs (fixes #16)
- [TASK] Always initialize the local database connection (fixes #14)
- [BUGFIX] Limit automatically prefetching files on folderExists call

5.4.1:

- [TYPO] Fix "installtion" in german warning label
- [BUGFIX] Support moving files between folders in a single storage
- [BUGFIX] Redirect after publishing errors after confirmation

5.4.0:

- [BUGFIX] Only create RealUrlCacheTasks for changed records
- [FEATURE] Add support for RealUrl > 2.2
- [TASK] Update affected versions of realurl in comments in RealUrlTask (these tables do not exist from 2.2 on)
- [API] Declare getDirtyProperties in RecordInterface
- [BUGFIX] Set the creation time manually when adding new tasks, because MySQL default was removed from field
- [BUGFIX] Force read access on FAL storage for all RPC/Envelope requests
- [BUGFIX] Do not drop all file information when related file does not exist on disk
- [BUGFIX] Typecast sftp resource for PHP7 stream wrapper compatibility
- [BUGFIX] Distinguish between insufficient permissions and PHP errors when opening remote streams
- [BUGFIX] Skip diff post processing of files which reside in deleted or unaccessible storages

5.3.8:

- [BUGFIX] Prevent file rename when file was not renamed
- [API] Add Record::removeRelatedRecord to it's interface since it's required by RecordFactory
- [TASK] Also log database errors in Letterbox
- [TASK] Add logger to Letterbox and handle failed envelopes

5.3.7:

- [BUGFIX] Resolve relations in input type fields with configured wizards
- [BUGFIX] Preserve the original record state when overriding with file state
- [BUGFIX] Limit the number of files to prefetch to prevent request field overflow
- [BUGFIX] Treat FAL storages as case sensitive by default
- [CODESTYLE] Update code style rules and apply them

5.3.6:

- [BUGFIX] Show all in2publish related logs
- [TASK] Always show full component name in logs
- [REFACTOR] Replace log table name field with constant
- [CLEANUP] Remove unneccessary log component filter
- [TASK] Define lightweight distribution properties
- [COMMENT] Set correct return type annotation for Record::hasDeleteField
- [API] Loosen Record implementation dependency by defining all required methods in the interface
- [BUGFIX] Allow null for strictly typed getRecordPath parameter

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
