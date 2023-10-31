# DemandsForTextWereCollected

## When

Right after a text of a record was examined.

## What

* `demands`: The demands object that contains all structured select, join, and sys_redirect queries as structured array.
* `record`: The record where the text is from.
* `text`: The text that was examined.

## Possibilities

You can add and remove records that should be selected by using the demand's methods.

### Example

```php
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Event\DemandsForTextWereCollected;

class RecordIgnoreListener
{
    public function __invoke(DemandsForTextWereCollected $event)
    {
        $text = $event->getText();
        if (preg_match('###special_link_marker_(?P<uid>\d+)###', $text, $matches)) {
            $demand = new SelectDemand('tx_myext_domain_model_linktarget', '', 'uid', $matches['uid']);
            $event->getDemands()->addDemand($demand);
        }
    }
}
```
