<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DependencyInjection;

use In2code\In2publishCore\Component\Core\Publisher\PublisherService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function array_keys;
use function interface_exists;

/**
 * @codeCoverageIgnore
 */
class PublisherPass implements CompilerPassInterface
{
    private string $tagName;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container): void
    {
        $registryDefinition = $container->findDefinition(PublisherService::class);
        if (!$registryDefinition) {
            return;
        }

        $taggedServiceIds = $container->findTaggedServiceIds($this->tagName);
        foreach (array_keys($taggedServiceIds) as $serviceName) {
            $definition = $container->findDefinition($serviceName);
            if ($definition->isAbstract() || interface_exists($definition->getClass())) {
                continue;
            }
            $definition->setPublic(true);

            $registryDefinition->addMethodCall('addPublisher', [
                new Reference($serviceName),
            ]);
        }
    }
}
