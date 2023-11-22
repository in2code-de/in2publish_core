# List of Features  EXT:in2publish_core

The `in2publish_core` extension offers a variety of configurable features, each residing in its own subfolder under `Configuration/Features`. Here's an overview of these features:

## AdminTools
- Provides tools for administrators to ensure correct functioning of the content publisher and to analyse the system setup.

## CacheInvalidation
- Manages cache invalidation strategies for efficient content delivery and performance optimization.

## CompareDatabaseTool
- Offers a tool for comparing database states between foreign and local development, aiding in synchronization and troubleshooting.

## ContextMenuPublishEntry
- Enhances the TYPO3 context menu with publishing options.

## FileEdgeCacheInvalidator
- Handles invalidation of edge caches for files, ensuring clearing of caches on foreign systems after publishing

## FullTablePublishing
- Allows for the publishing of entire database tables, useful in scenarios requiring bulk data transfers without related records.

## HideRecordsDeletedDifferently
##[**HideRecordsDeletedDifferently**](HideRecordsDeletedDifferently.md)
- Offers the possibility to hide records from the Publish Overview Module if they are deleted on one side and removed from the database on the other, e.g. if using the Recycler.
- Configurable feature. Default: enabled.

## MetricsAndDebug
- Offers metrics collection and debugging tools, helpful for performance analysis and issue resolution.

## NewsSupport
- Specific support for handling news records and related content.

## PreventParallelPublishing
- Prevents parallel publishing operations, ensuring data integrity and avoiding conflicts during content deployment.

## PublishSorting
- Adds sorting capabilities to the publishing process, allowing for prioritization and organized content rollout.

## RecordBreadcrumbs
- Enhances the backend interface with breadcrumbs for records, improving navigation and context awareness.

## RecordInspector
- Provides an inspection tool for content records, aiding in content review and data analysis.

## [**Redirects Support**](RedirectsSupport.md)
- Integrates support for manual publishing of redirects.
- Configurable feature. Default: enabled.

## RefIndexUpdate
- Handles the updating of TYPO3's reference index, ensuring accurate content linking and reference management.

## ResolveFilesForIndices
- Resolves file paths for indexing, aiding in efficient content retrieval and search functionality.

## SysLogPublisher
- Integrates with TYPO3's system log for publishing-related logging, enhancing monitoring and auditing capabilities.

## SystemInformationExport
- Enables the export of system information, useful for diagnostics and system overviews.

## WarningOnForeign
- Implements warnings for operations performed on foreign (non-local) systems, enhancing operational safety.
