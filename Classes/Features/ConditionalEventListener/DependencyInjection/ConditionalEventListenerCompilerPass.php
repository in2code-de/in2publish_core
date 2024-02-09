<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\ConditionalEventListener\DependencyInjection;

use In2code\In2publishCore\Features\ConditionalEventListener\EventListener\ConditionalEventListenerDelegator;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionUnionType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function count;
use function is_string;
use function sprintf;
use function str_replace;

class ConditionalEventListenerCompilerPass implements CompilerPassInterface
{
    protected string $tagName;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container): void
    {
        $delegatedEvents = [];
        $collectListeners = $this->collectListeners($container);
        $dependencyOrderingService = new DependencyOrderingService();
        foreach ($collectListeners as $eventName => $listeners) {
            $delegatedEvents[$eventName] = $eventName;
            $collectListeners[$eventName] = $dependencyOrderingService->orderByDependencies($listeners);
        }

        $delegator = $container->findDefinition(ConditionalEventListenerDelegator::class);
        $delegator->setArgument('$eventListeners', $collectListeners);
        foreach ($delegatedEvents as $eventName) {
            $delegator->addTag('event.listener', [
                'identifier' => $this->tagName . '.' . str_replace('\\', '.', $eventName),
                'event' => $eventName,
            ]);
        }
    }

    /**
     * Collects all listeners from the container.
     */
    protected function collectListeners(ContainerBuilder $container): array
    {
        $unorderedEventListeners = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $service = $container->findDefinition($serviceName);
            $service->setPublic(true);
            foreach ($tags as $attributes) {
                if (!array_key_exists('condition', $attributes)) {
                    throw new InvalidArgumentException(
                        'Service tag "in2publish_core.conditional.event.listener" requires a condition attribute to be defined.  Missing in: ' . $serviceName,
                        1701773147,
                    );
                }
                $eventIdentifiers = $attributes['event'] ?? $this->getParameterType(
                    $serviceName,
                    $service,
                    $attributes['method'] ?? '__invoke',
                    $container,
                );
                if (empty($eventIdentifiers)) {
                    throw new InvalidArgumentException(
                        'Service tag "in2publish_core.conditional.event.listener" requires an event attribute to be defined or the listener method must declare a parameter type.  Missing in: ' . $serviceName,
                        1701770451,
                    );
                }
                if (is_string($eventIdentifiers)) {
                    $eventIdentifiers = [$eventIdentifiers];
                }
                foreach ($eventIdentifiers as $eventIdentifier) {
                    $listenerIdentifier = $attributes['identifier'] ?? $serviceName;
                    $unorderedEventListeners[$eventIdentifier][$listenerIdentifier] = [
                        'service' => $serviceName,
                        'method' => $attributes['method'] ?? '__invoke',
                        'condition' => $attributes['condition'],
                        'before' => GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true),
                        'after' => GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true),
                    ];
                }
            }
        }
        return $unorderedEventListeners;
    }

    /**
     * Derives the class type(s) of the first argument of a given method.
     * Supporting union types, this method returns the class type(s) as list.
     *
     * @return string[]|null A list of class types or NULL on failure
     */
    protected function getParameterType(
        string $serviceName,
        Definition $definition,
        string $method,
        ContainerBuilder $container
    ): ?array {
        // A Reflection exception should never actually get thrown here, but linters want a try-catch just in case.
        try {
            if (!$definition->isAutowired()) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Service "%s" has event listeners defined but does not declare an event to listen to and is not configured to autowire it from the listener method. Set autowire: true to enable auto-detection of the listener event.',
                        $serviceName,
                    ),
                    1701770456,
                );
            }
            $params = $this->getReflectionMethod($serviceName, $definition, $method, $container)->getParameters();
            $rType = count($params) ? $params[0]->getType() : null;
            if ($rType instanceof ReflectionNamedType) {
                return [$rType->getName()];
            }
            if ($rType instanceof ReflectionUnionType) {
                $types = [];
                foreach ($rType->getTypes() as $type) {
                    if ($type instanceof ReflectionNamedType) {
                        $types[] = $type->getName();
                    }
                }
                if ($types === []) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Service "%s" registers method "%s" as an event listener, but does not specify an event type and the method\'s first parameter does not contain a valid class type. Declare valid class types for the method parameter or specify the event classes explicitly',
                            $serviceName,
                            $method,
                        ),
                        1701770459,
                    );
                }
                return $types;
            }
            throw new InvalidArgumentException(
                sprintf(
                    'Service "%s" registers method "%s" as an event listener, but does not specify an event type and the method does not type a parameter. Declare a class type for the method parameter or specify an event class explicitly',
                    $serviceName,
                    $method,
                ),
                1701770462,
            );
        } catch (ReflectionException $e) {
            // The collectListeners() method will convert this to an exception.
            return null;
        }
    }

    /**
     * @throws RuntimeException
     *
     * This method borrowed very closely from TYPO3's ListenerProviderPass.
     */
    protected function getReflectionMethod(
        string $serviceName,
        Definition $definition,
        string $method,
        ContainerBuilder $container
    ): ReflectionFunctionAbstract {
        if (!$class = $definition->getClass()) {
            throw new RuntimeException(
                sprintf('Invalid service "%s": the class is not set.', $serviceName),
                1701770466,
            );
        }

        if (!$r = $container->getReflectionClass($class)) {
            throw new RuntimeException(
                sprintf('Invalid service "%s": class "%s" does not exist.', $serviceName, $class),
                1701770469,
            );
        }

        $methodeName = $class !== $serviceName ? $class . '::' . $method : $method;
        if (!$r->hasMethod($method)) {
            throw new RuntimeException(
                sprintf('Invalid service "%s": method "%s()" does not exist.', $serviceName, $methodeName),
                1701770482,
            );
        }

        $r = $r->getMethod($method);
        if (!$r->isPublic()) {
            throw new RuntimeException(
                sprintf('Invalid service "%s": method "%s()" must be public.', $serviceName, $methodeName),
                1701770484,
            );
        }

        return $r;
    }
}
