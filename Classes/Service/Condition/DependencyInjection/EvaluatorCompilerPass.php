<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Condition\DependencyInjection;

use In2code\In2publishCore\Service\Condition\ConditionEvaluationService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function array_keys;

class EvaluatorCompilerPass implements CompilerPassInterface
{
    private string $tagName;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container)
    {
        $service = $container->findDefinition(ConditionEvaluationService::class);
        $evaluators = $container->findTaggedServiceIds($this->tagName);
        foreach (array_keys($evaluators) as $evaluator) {
            $service->addMethodCall('addEvaluator', [new Reference($evaluator)]);
        }
    }
}
