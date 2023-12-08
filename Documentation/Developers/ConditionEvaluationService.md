# Condition Evaluation Service

There is a generic `ConditionEvaluationService` in EXT:in2publish_core which can be used to evaluate conditions in strings
or arrays of strings. The service delegates the actual evaluation to the custom evaluators for each condition.

The `ConditionEvaluationService` is e.g. used in the Publish Tools module to evaluate if a tool should be shown or not.
It delegates the evaluation of the condition to the `ExtConfEvaluator` or the `ConfEvaluator`, depending on the condition.

The condition parts are delimited by `:` (colons)
    * `CONF`: A dot-path to the configuration value e.g. `CONF:features.remoteCacheControl.enableTool`
    * `EXTCONF`: This condition has three parts. `EXTCONF`, an extension key and a path to the extension's config
      e.g. `EXTCONF:in2publish_core:logLevel`
      The evaluation of the conditions is done by the generic `ConditionEvaluationService`,

You can use the `ConditionEvaluationService` in your own code, in order to evaluate conditions (for an example have a
look at the ToolsRegistry). You can also create your own evaluators for custom conditions
(see [CutomEvaluators.md](Guides/CutomEvaluators.md)).

```php
