# Conditional Event Listeners

EXT:in2publish_core provides a `ConditionalEventListenerDelegator` to ensure that event listeners are only called if a
condition is met. This is necessary, because most events should only trigger a response, if certain conditions
 are met, e.g. a certain set of features is active.

The `ConditionalEventListenerDelegator` ensures that event listeners are only registered if the condition is met.
Such conditional event listeners must be registered with the tag `in2publish_core.conditional.event.listener`
and have a condition that can be evaluated by the `ConditionEvaluationService`
(see [Condition Evaluation Service](ConditionEvaluationService.md)).

Example:
```yaml
     tags:
       - name: in2publish_core.conditional.event.listener
         condition: 'CONF:features.hideRecordsDeletedDifferently.enable'
         identifier: 'in2publish-HideRecordsDeletedDifferently-DecideIfRecordShouldBeIgnored'
         method: 'decideIfRecordShouldBeIgnored'
         event: In2code\In2publishCore\Event\DecideIfRecordShouldBeIgnored
```
