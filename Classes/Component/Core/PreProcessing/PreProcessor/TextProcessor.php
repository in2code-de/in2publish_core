<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\Resolver\MultiSectionTextResolver;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use In2code\In2publishCore\Component\Core\Resolver\TextResolver;

use function str_contains;

class TextProcessor extends AbstractProcessor
{
    protected string $type = 'text';
    protected array $required = [
        'enableRichtext' => 'Text which is not rich text does not contain relations as t3 URNs',
    ];

    protected function additionalPreProcess(string $table, string $column, array $tca): array
    {
        if (!isset($tca['enableRichtext'])) {
            return [];
        }
        if (!$tca['enableRichtext']) {
            return ['Field enableRichtext must not be false'];
        }
        return [];
    }

    protected function buildResolver(string $table, string $column, array $processedTca): Resolver
    {
        if (str_contains($column, '[ANY]')) {
            $resolver = $this->container->get(MultiSectionTextResolver::class);
            $resolver->configure($column);
            return $resolver;
        }
        $resolver = $this->container->get(TextResolver::class);
        $resolver->configure($column);
        return $resolver;
    }
}
