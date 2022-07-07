<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\DependencyInjection;

use In2code\In2publishCore\Component\TcaHandling\DemandResolver\DemandResolverCollection;
use In2code\In2publishCore\Component\TcaHandling\DemandResolver\DemandResolverFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function array_keys;

class DemandResolverPass implements CompilerPassInterface
{
    private string $tagName;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container): void
    {
        $factory = $container->findDefinition(DemandResolverFactory::class);
        if (!$factory) {
            return;
        }

        $taggedServiceIds = $container->findTaggedServiceIds($this->tagName);
        foreach (array_keys($taggedServiceIds) as $serviceName) {
            $definition = $container->findDefinition($serviceName);
            if (
                !$definition->isAutoconfigured()
                || $definition->isAbstract()
                || $definition->getClass() === DemandResolverCollection::class
                || interface_exists($definition->getClass())
            ) {
                continue;
            }
            $definition->setPublic(true);

            $factory->addMethodCall('addDemandResolver', [
                new Reference($serviceName),
            ]);
        }
    }
}
