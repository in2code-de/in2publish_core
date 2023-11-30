<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DependencyInjection;

use In2code\In2publishCore\Component\Core\Service\ResolverService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function array_keys;

class StaticResolverPass implements CompilerPassInterface
{
    protected string $tagName;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container)
    {
        $resolverServiceDefinition = $container->findDefinition(ResolverService::class);
        $staticResolvers = $container->findTaggedServiceIds($this->tagName);
        foreach (array_keys($staticResolvers) as $staticResolver) {
            $resolverServiceDefinition->addMethodCall('addStaticResolver', [new Reference($staticResolver)]);
        }
    }
}
