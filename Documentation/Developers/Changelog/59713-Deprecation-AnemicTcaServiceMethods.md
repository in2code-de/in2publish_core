# Deprecation: Anemic TcaService methods

Issue https://projekte.in2code.de/issues/59713

## Description

The Methods
* `\In2code\In2publishCore\Service\Configuration\TcaService::getDeletedField`
* `\In2code\In2publishCore\Service\Configuration\TcaService::getDisableField`
* `\In2code\In2publishCore\Service\Configuration\TcaService::getLanguageField`
* `\In2code\In2publishCore\Service\Configuration\TcaService::getTransOrigPointerField`
are deprecated.

## Impact

The mentioned methods will be removed in in2publish_core v13.

## Affected Installations

All.

## Migration

Directly access `$GLOBALS['TCA']` instead. Don't forget to use a null coalesce operator.

Old: `\In2code\In2publishCore\Service\Configuration\TcaService::getDeletedField`
New: `$GLOBALS['TCA'][$table]['ctrl']['delete'] ?? ''`

Old:  `\In2code\In2publishCore\Service\Configuration\TcaService::getDisableField`
New: `$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'] ?? ''`

Old:  `\In2code\In2publishCore\Service\Configuration\TcaService::getLanguageField`
New: `$GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? ''`

Old:  `\In2code\In2publishCore\Service\Configuration\TcaService::getTransOrigPointerField`
New: `$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? ''`
