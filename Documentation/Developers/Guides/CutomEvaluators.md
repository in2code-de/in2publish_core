# Custom Evaluators

If you would like to use the `ConditionEvaluationService` and evaluate custom string conditions in your code,
you can create your own evaluators. Your evaluator will be registered automatically if it implements the interface
`Evaluator`.
For an example have a look at the `ExtConfEvaluator` or the `ConfEvaluator`.
These evaluators are used to evaluate conditions like `CONF:features.remoteCacheControl.enableTool` or
`EXTCONF:in2publish_core:logLevel` in order to check if a tool should be shown in the PublishTools module or not.
(see: [Condition Evaluation Service](ConditionEvaluationService.md) and [Custom Admin Tools](CustomTools.md))

It is recommended to prefix your custom condition with a unique identifier, e.g. `MYEXT:mycondition` and to check
for this prefix in your evaluator. This way you can be sure that your evaluator is only called for your custom conditions.
