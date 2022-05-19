<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;
use In2code\In2publishCore\Component\TcaHandling\Resolver\TextResolver;

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
        $resolver = $this->container->get(TextResolver::class);
        $resolver->configure($column);
        return $resolver;
    }
}
