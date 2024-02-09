<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\ConditionalEventListener\EventListener;

use In2code\In2publishCore\CommonInjection\ContainerInjection;
use In2code\In2publishCore\Service\Condition\ConditionEvaluationServiceInjection;

use function get_class;

class ConditionalEventListenerDelegator
{
    use ContainerInjection;
    use ConditionEvaluationServiceInjection;

    protected array $eventListeners;
    protected array $evaluatedListeners = [];

    public function __construct(array $eventListeners)
    {
        $this->eventListeners = $eventListeners;
    }

    public function __invoke(object $event): void
    {
        $listeners = $this->getListenersForEvent($event);
        foreach ($listeners as $listener) {
            $listener['service']->{$listener['method']}($event);
        }
    }

    protected function getListenersForEvent(object $event): array
    {
        $eventName = get_class($event);
        if (!isset($this->evaluatedListeners[$eventName])) {
            $this->evaluatedListeners[$eventName] = [];
            $eventListeners = $this->eventListeners[$eventName] ?? [];
            foreach ($eventListeners as $identifier => $eventListener) {
                if ($this->conditionEvaluationService->evaluate($eventListener['condition'])) {
                    $service = $this->container->get($eventListener['service']);

                    $this->evaluatedListeners[$eventName][$identifier] = $eventListener;
                    $this->evaluatedListeners[$eventName][$identifier]['service'] = $service;
                }
            }
        }
        return $this->evaluatedListeners[$eventName];
    }
}
