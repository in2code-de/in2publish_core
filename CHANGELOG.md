# In2publish Core Change Log

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
