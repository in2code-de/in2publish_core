# DemandsWereCollected

## When

Right before a demands object will be resolved.

## What

* `demands`: The demands object that contains all structured select, join, and sys_redirect queries as structured array.

## Possibilities

You can add and remove records that should be selected by using the demand's methods.

### Example

```php
use In2code\In2publishCore\Component\Core\Demand\Remover\SelectDemandRemover;
use In2code\In2publishCore\Event\DemandsWereCollected;

class RecordIgnoreListener
{
    private const UID_TO_IGNORE = [
        1,
        11,
        42,
        1456,
    ];

    public function __invoke(DemandsWereCollected $event)
    {
        $demands = $event->getDemands();
        foreach (self::UID_TO_IGNORE as $uid) {
            $demands->unsetDemand(new SelectDemandRemover('my_ext_domain_model_something', 'uid', $uid));
        }
    }
}
```
