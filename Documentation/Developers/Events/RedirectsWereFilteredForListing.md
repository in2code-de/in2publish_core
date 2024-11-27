# RedirectsWereFilteredForListing

## When

The event is fired after the redirects are filtered for display in the listAction of the RedirectController.

## What

* `redirects`: an array of SysRedirectDatabaseRecord

## Possibilities

With this event you can filter the records in more detail on your own.

### Example

This example show how to filter the redirects with the Language Uid of the Parent Page.

```php
class FilterRedirectsBySelectedLanguages
{
    use UserSelectionServiceInjection;
    use RawRecordServiceInjection;

    public function __invoke(RedirectsWereFilteredForListing $event): void
    {
        $redirects = $event->getRedirects();
        $selectedLanguages = [-1,0,4];

        $redirects = array_filter($redirects, function (SysRedirectDatabaseRecord $redirect) use ($selectedLanguages) {
            $pid = (int)$redirect->getLocalProps()['pid'];
            if (0 === $pid) {
                return true;
            }
            $record = $this->rawRecordService->getRawRecord('pages', $pid, 'local');
            if (in_array($record['sys_language_uid'], $selectedLanguages)){
                return true;
            }
        });

        $event->setRedirects($redirects);
    }
}
```
