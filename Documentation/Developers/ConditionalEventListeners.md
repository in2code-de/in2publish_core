# Conditional Event Listeners

EXT:in2publish_core provides a method to register event listeners with conditions, to ensure that event listeners are
only called if these conditions are met. This reduces the overhead of disabled features, because the actual event
listeners which might heavily depend on other services and are costly to instantiate are not created.

The `ConditionalEventListenerDelegator` ensures that event listeners are only instantiated if the condition is met.
Such conditional event listeners must be registered with the tag `in2publish_core.conditional.event.listener`
and have a condition that can be evaluated by the `ConditionEvaluationService`
(see [Condition Evaluation Service](ConditionEvaluationService.md)).

You can use the `in2publish_core.conditional.event.listener` tag like you would use `event.listener`. The registration
of a conditional event listener is equal to the registration of a normal event listener, except for the tag and
the `condition` attribute. The condition can be a string or array. If it is an array, all condition strings in the array
must evaluate to true (they are combined by a logical AND).

```yaml
services:
    In2code\In2publishCore\Features\HideRecordsDeletedDifferently\EventListener\HideRecordsDeletedDifferentlyEventListener:
        tags:
            -   name: in2publish_core.conditional.event.listener
                condition: 'CONF:features.hideRecordsDeletedDifferently.enable'
                identifier: 'in2publish-HideRecordsDeletedDifferently-DecideIfRecordShouldBeIgnored'
                method: 'decideIfRecordShouldBeIgnored'
                event: In2code\In2publishCore\Event\DecideIfRecordShouldBeIgnored
```
