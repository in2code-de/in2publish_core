# RecordRelationsWereResolved

## When

After the `RecordTree` was built.

## What

* `recordTree`: The recordTree that has been built. It contains all records and all children. The child/translation
  relations have been resolved already.

## Possibilities

This is the central event to process the recordTree before it will be displayed in the UI or published. You can:

* Traverse the record tree to:
    * change record values
    * add/remove records
    * change relations between records
* Get specific records from the record tree by their classifier and identifier to:
    * Recurse through the record tree to get a specific record
    * ...

... virtually anything you can imagine.

### Example

```php
use In2code\In2publishCore\Event\RecordRelationsWereResolved;

class RecordTreeCompletionListener
{
    public function __invoke(RecordRelationsWereResolved $event)
    {
        $recordTree = $event->getRecordTree();
        $page = $recordTree->getChild('pages', 1);
        if (null !== $page) {
            $foreignProperties = $page->getForeignProps();
            $foreignProperties['my_property'] = 'my_value';
            $page->setForeignProps($foreignProperties);
        }
    }
}
```
