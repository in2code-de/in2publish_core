<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\ConditionalEventListener\EventListener;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use Psr\Container\ContainerInterface;

use function get_class;

class ConditionalEventListenerDelegator
{
    protected ContainerInterface $container;
    protected ConfigContainer $configContainer;
    protected array $eventListeners;
    protected array $evaluatedListeners = [];

    public function __construct(ContainerInterface $container, ConfigContainer $configContainer, array $eventListeners)
    {
        $this->container = $container;
        $this->configContainer = $configContainer;
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
                if ($this->configContainer->get($eventListener['condition'])) {
                    $service = $this->container->get($eventListener['service']);

                    $this->evaluatedListeners[$eventName][$identifier] = $eventListener;
                    $this->evaluatedListeners[$eventName][$identifier]['service'] = $service;
                }
            }
        }
        return $this->evaluatedListeners[$eventName];
    }
}
