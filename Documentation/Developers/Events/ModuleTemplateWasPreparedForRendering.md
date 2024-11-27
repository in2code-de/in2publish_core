# ModuleTemplateWasPreparedForRendering

## When

This event is fired each time a backend module is opened.

## What

* `moduleTemplate`: an object of type TYPO3\CMS\Backend\Template\ModuleTemplate
* `controllerClass`: controller class calling the event
* `actionMethodName`: action method name calling the event

## Possibilities

With this event you can add e.g. buttons to the docheader of a backend module.

### Example

This example shows you how to add a button to the docheader of the redirect module.

```php
use In2code\In2publish\Features\ContentLanguageControl\Toolbar\LanguageSelectionButtonInjection;
use In2code\In2publishCore\Event\ModuleTemplateWasPreparedForRendering;
use In2code\In2publishCore\Features\RedirectsSupport\Controller\RedirectController;

class ModuleTemplateButtonBarSelectionRenderer
{
    use LanguageSelectionButtonInjection;

    public function whenModuleTemplateWasPreparedForRendering(ModuleTemplateWasPreparedForRendering $event): void
    {
        if (RedirectController::class === $event->getControllerClass() && 'listAction' === $event->getActionMethodName()){
            $moduleTemplate = $event->getModuleTemplate();
            $moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($this->languageSelectionButton);
        }
    }
}
```
