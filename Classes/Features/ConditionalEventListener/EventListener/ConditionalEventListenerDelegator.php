<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\ConditionalEventListener\EventListener;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use Psr\Container\ContainerInterface;

class ConditionalEventListenerDelegator
{
    protected ContainerInterface $container;
    protected ConfigContainer $configContainer;
    protected array $eventListeners;

    public function __construct(ContainerInterface $container, ConfigContainer $configContainer, array $eventListeners)
    {
        $this->container = $container;
        $this->configContainer = $configContainer;
        $this->eventListeners = $eventListeners;
    }

    public function __invoke(object $event): void
    {
        $eventName = get_class($event);
        foreach ($this->eventListeners[$eventName] ?? [] as $identifier => $listener) {
            if ($this->configContainer->get($listener['condition'])) {
                $service = $this->container->get($listener['service']);
                $service->{$listener['method']}($event);
            } else {
                unset($this->eventListeners[$eventName][$identifier]);
                if (empty($this->eventListeners[$eventName])) {
                    unset($this->eventListeners[$eventName]);
                }
            }
        }
    }
}
