# ExtTablesPostProcessingEvent

## When

In the `BackendRouteInitialization` middleware

## What

nothing

## Possibilities

This event replaces the "ExtTablesPostProcessingHook" which was registered using
`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing']`.
Since TYPO3 does not provide an equivalent replacement, we XCLASS the `BackendRouteInitialization` to introduce this
event ourselves. Have a look at the event class for more information.
