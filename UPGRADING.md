# v10 to v11

## For users & admins

The Publish Files Module was rewritten and comes with a new interface. It shows the same files as before, but it
contains some new controls. Have a look at the [editors documentation](Documentation/Editors/PublishFilesModule.md)
which contains some screenshots and descriptions.

## For developers

* General
    * The FAL handling got overhauled. The settings `factory.fal.finder` and `factory.fal.publisher` were introduced.
      They
      replace the enterprise edition setting `features.simpleFiles.enable`.
      See [48269-Config-SimpleFilesEnableChanged.md](https://github.com/in2code-de/in2publish/blob/9636a805c28c3c0e1db55c57e2691318061a7300/Documentation/Changelog/48269-Config-SimpleFilesEnableChanged.md)
      for more information.
* Controller:
    * The method `runTasks` was moved. Use the `RunTasks` trait to trigger task execution.
    * The templating was rewritten on the basis of the trait `ControllerModuleTemplate`. CSS and JS can be still added
      in the template but is discouraged.
    * AdminTools: Please use the trait `AdminToolsModuleTemplate` in your AdminTool Controller.
* Enhancement:
    * Instead of `\TYPO3\CMS\Core\Core\Event\BootCompletedEvent`, listen to the event `ExtTablesPostProcessingEvent`
      which
      the Content Publisher adds as a quality replacement
      for `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing']`.
* View/ViewHelper: (Adapt your templates/partials accordingly.)
    * TYPO3 Removed the PaginationViewHelper. We are now using the new "pagination API".
    * Some ViewHelpers were deleted, because they are not required anymore.
    * The `ToolsWhite` layout was removed. Please use `Default` instead.
* Services
    * Classes that implement `FalFinder`, `FalPublisher`, and `PostProcessor` are now automatically registered and
      public.

# v9 to v10

## For users & admins

There were no changes to the UI or behavior of the Content Publisher.

## For developers

### Replace signal slots by PSR-14 events

TYPO3 deprecated the Signal Slot Dispatcher in TYPO3 v10 and introduced PSR-14 Events as a replacement. To keep track
with this change, all Content Publisher Signals are replaced with Events, too. This change is backwards compatible in
the version 10 of in2publish_core. The backwards compatibility layer will be removed in in2publish_core v11, because
that version will only be compatible with TYPO3 v11, where the Signal Slot Dispatcher will be removed.

* https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/10.0/Feature-88770-PSR-14BasedEventDispatcher.html
* https://usetypo3.com/psr-14-events.html

The following table lists all signals of in2publish_core with their event replacement:

| Signal Class                                                              | Signal Name                                            | Event                                                                                                                                               |
|---------------------------------------------------------------------------|--------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------|
| `In2code\In2publishCore\Domain\Model\RecordInterface`                     | isPublishable                                          | [VoteIfRecordIsPublishable](../Events/VoteIfRecordIsPublishable.md)                                                                                 |
| `In2code\In2publishCore\Domain\Factory\RecordFactory`                     | instanceCreated (RecordFactory)                        | [RecordInstanceWasInstantiated](../Events/RecordInstanceWasInstantiated.md)                                                                         |
| `In2code\In2publishCore\Domain\Factory\RecordFactory`                     | rootRecordFinished                                     | [RootRecordCreationWasFinished](../Events/RootRecordCreationWasFinished.md)                                                                         |
| `In2code\In2publishCore\Domain\Factory\RecordFactory`                     | addAdditionalRelatedRecords                            | [AllRelatedRecordsWereAddedToOneRecord](../Events/AllRelatedRecordsWereAddedToOneRecord.md)                                                         |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | afterRecordEnrichment (deprecated!)                    | [RecordWasEnriched](../Events/RecordWasEnriched.md)                                                                                                 |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | relationResolverRTE                                    | [RelatedRecordsByRteWereFetched](../Events/RelatedRecordsByRteWereFetched.md)                                                                       |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | publishRecordRecursiveBegin                            | [RecursiveRecordPublishingBegan](../Events/RecursiveRecordPublishingBegan.md)                                                                       |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | publishRecordRecursiveEnd                              | [RecursiveRecordPublishingEnded](../Events/RecursiveRecordPublishingEnded.md)                                                                       |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | publishRecordRecursiveBeforePublishing                 | [PublishingOfOneRecordBegan](../Events/PublishingOfOneRecordBegan.md)                                                                               |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | publishRecordRecursiveAfterPublishing                  | [PublishingOfOneRecordEnded](../Events/PublishingOfOneRecordEnded.md)                                                                               |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | shouldSkipRecord                                       | [VoteIfRecordShouldBeSkipped](../Events/VoteIfRecordShouldBeSkipped.md)                                                                             |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | shouldIgnoreRecord                                     | [VoteIfRecordShouldBeIgnored](../Events/VoteIfRecordShouldBeIgnored.md)                                                                             |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | shouldSkipEnrichingPageRecord                          | [VoteIfPageRecordEnrichingShouldBeSkipped](../Events/VoteIfPageRecordEnrichingShouldBeSkipped.md)                                                   |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | shouldSkipFindByIdentifier                             | [VoteIfFindingByIdentifierShouldBeSkipped](../Events/VoteIfFindingByIdentifierShouldBeSkipped.md)                                                   |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | shouldSkipFindByProperty                               | [VoteIfFindingByPropertyShouldBeSkipped](../Events/VoteIfFindingByPropertyShouldBeSkipped.md)                                                       |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | shouldSkipSearchingForRelatedRecordByTable             | [VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped](../Events/VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped.md)                       |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | shouldSkipSearchingForRelatedRecords                   | [VoteIfSearchingForRelatedRecordsShouldBeSkipped](../Events/VoteIfSearchingForRelatedRecordsShouldBeSkipped.md)                                     |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | shouldSkipSearchingForRelatedRecordsByFlexForm         | [VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped](../Events/VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped.md)                 |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | shouldSkipSearchingForRelatedRecordsByFlexFormProperty | [VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped](../Events/VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped.md) |
| `In2code\In2publishCore\Domain\Repository\CommonRepository`               | shouldSkipSearchingForRelatedRecordsByProperty         | [VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped](../Events/VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped.md)                 |
| `In2code\In2publishCore\Controller\FileController`                        | folderInstanceCreated                                  | [FolderInstanceWasCreated](../Events/FolderInstanceWasCreated.md)                                                                                   |
| `In2code\In2publishCore\Controller\RecordController`                      | beforeDetailViewRender                                 | [RecordWasCreatedForDetailAction](../Events/RecordWasCreatedForDetailAction.md)                                                                     |
| `In2code\In2publishCore\Controller\RecordController`                      | beforePublishing                                       | [RecordWasSelectedForPublishing](../Events/RecordWasSelectedForPublishing.md)                                                                       |
| `In2code\In2publishCore\Controller\ToolsController`                       | collectSupportPlaces                                   | [CreatedDefaultHelpLabels](../Events/CreatedDefaultHelpLabels.md)                                                                                   |
| `In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider`      | filterStorages                                         | [StoragesForTestingWereFetched](../Events/StoragesForTestingWereFetched.md)                                                                         |
| `In2code\In2publishCore\Domain\Service\Publishing\FolderPublisherService` | afterPublishingFolder                                  | [FolderWasPublished](../Events/FolderWasPublished.md)                                                                                               |
| `In2code\In2publishCore\Controller\AbstractController`                    | checkUserAllowedToPublish                              | [VoteIfUserIsAllowedToPublish](../Events/VoteIfUserIsAllowedToPublish.md)                                                                           |
| `In2code\In2publishCore\Testing\Data\RequiredTablesDataProvider`          | overruleTables                                         | [RequiredTablesWereIdentified](../Events/RequiredTablesWereIdentified.md)                                                                           |
