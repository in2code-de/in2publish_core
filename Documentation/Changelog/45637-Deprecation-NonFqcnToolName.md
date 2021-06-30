# Deprecation: Non-FQCN tool name

Issue https://projekte.in2code.de/issues/45637

## Description

Dropping support for TYPO3 v9 also means dropping support for non-fqcn controller in plugin and module registration.
Hence, you can as of now use the magic `::class` constant to specify the tool's controller.

## Impact

```php
$toolsRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\In2code\In2publishCore\Tools\ToolsRegistry::class);
$toolsRegistry->addTool(
    'LLL:EXT:in2publish/Resources/Private/Language/locallang.xlf:moduleselector.edit_config',
    'LLL:EXT:in2publish/Resources/Private/Language/locallang.xlf:moduleselector.edit_config.description',
    'Tools', // deprecated
    'edit,update'
);
```

Will trigger a `E_USER_DEPRECATED` error.

## Affected Installations

All extensions which register a tool.

## Migration

Use the magic class constant to pass the FQCN to the tools registration.

```php
$toolsRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\In2code\In2publishCore\Tools\ToolsRegistry::class);
$toolsRegistry->addTool(
    'LLL:EXT:in2publish/Resources/Private/Language/locallang.xlf:moduleselector.edit_config',
    'LLL:EXT:in2publish/Resources/Private/Language/locallang.xlf:moduleselector.edit_config.description',
    \In2code\In2publishCore\Controller\ToolsController::class,
    'edit,update'
);
```
