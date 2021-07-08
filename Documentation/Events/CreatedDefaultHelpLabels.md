# CreatedDefaultHelpLabels

Replaces the `\In2code\In2publishCore\Controller\ToolsController / collectSupportPlaces` Signal.

## When

Each time the `ToolsController::indexAction` is executed, right before the support information labels are assigned to
the view.

## What

* `supports`: An array of strings which are going to be displayed in the Publish Tools introductions section beneath "
  Getting Help"

## Possibilities

You can add a link to the bug tracker of your agency if you are integrating the Content Publisher for your own customer.

### Example

```php
use In2code\In2publishCore\Event\CreatedDefaultHelpLabels;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class SupportInfoProvider
{
    public function __invoke(CreatedDefaultHelpLabels $event)
    {
        $event->addSupport(LocalizationUtility::translate('help.support_label', 'myextension'));
    }
}
```
