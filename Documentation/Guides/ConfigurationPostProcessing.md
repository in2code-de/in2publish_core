# Configuration Post Processing Guide

Configuration post processing allows the complete alteration of the configuration array.

## Custom Post Processor

A post processor must implement `PostProcessorInterface`.

```php
<?php

namespace MyVendor\MyPackage\Config\PostProcessor;

class MyPostProcessor implements \In2code\In2publishCore\Config\PostProcessor\PostProcessorInterface
{
    public function process(array $config): array
    {
        // Implement your logic and return the modified configuration array
        return $config;
    }
}
```

Register your post processor in your `ext_localconf.php`:

```php
$configContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\In2code\In2publishCore\Config\ConfigContainer::class);
$configContainer->registerPostProcessor(\MyVendor\MyPackage\Config\PostProcessor\MyPostProcessor::class);
```

**Attention:** The post processor will be called multiple times per request.
If your configuration processing requires some more resources you should definitely consider at least a runtime caching
mechanism or even the usage of the caching framework.
The configuration passed to your post processor may be incomplete, because a provider like the `PageTsProvider` may not
be ready (because no page id has been identified, yet).
