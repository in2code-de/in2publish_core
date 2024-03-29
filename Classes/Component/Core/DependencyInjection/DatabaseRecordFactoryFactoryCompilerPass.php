<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DependencyInjection;

use In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactoryFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function array_keys;
use function interface_exists;

/**
 * @codeCoverageIgnore
 */
class DatabaseRecordFactoryFactoryCompilerPass implements CompilerPassInterface
{
    private string $tagName;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container)
    {
        $recordDatabaseFactoryFactory = $container->getDefinition(DatabaseRecordFactoryFactory::class);
        if (!$recordDatabaseFactoryFactory) {
            return;
        }

        foreach (array_keys($container->findTaggedServiceIds($this->tagName)) as $serviceName) {
            $definition = $container->findDefinition($serviceName);
            if ($definition->isAbstract() || interface_exists($definition->getClass())) {
                continue;
            }
            $definition->setPublic(true);
            $recordDatabaseFactoryFactory->addMethodCall(
                'addFactory',
                [new Reference($serviceName)],
            );
        }
    }
}
