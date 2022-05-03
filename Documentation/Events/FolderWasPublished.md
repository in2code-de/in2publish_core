# FolderWasPublished

Replaces the `\In2code\In2publishCore\Domain\Service\Publishing\FolderPublisherService / afterPublishingFolder` Signal.

## When

Every time a folder was published, regardless of the success.

## What

* `storage`: The storage identifier which contains the folder.
* `folderIdentifier`: The folder identifier inside the storage. This is usually the full relative path to the folder (in
  fileadmin).
* `success`: A bool to indicate if the publishing was successful or not.

## Possibilities

You can listen on this event to publish additional information associated with the folder (like in2publish does for EXT:
fal_securedownload)

### Example

```php
use In2code\In2publishCore\Event\AllRelatedRecordsWereAddedToOneRecord;

class AdditionRelationAdder
{
    public function __invoke(AllRelatedRecordsWereAddedToOneRecord $event): void
    {
        $record = $event->getRecord();
        if ('tx_myext_domain_model_something' === $record->getTableName()) {
            $relatedRecords = $this->fetchRelatedRecordsByRecord($record);
            $record->addRelatedRecord($relatedRecords);
        }
    }
}
```
