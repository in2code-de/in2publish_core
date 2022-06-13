<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Resolver;

use In2code\In2publishCore\Component\TcaHandling\Demand\Demands;
use In2code\In2publishCore\Domain\Model\Record;

use function array_keys;
use function preg_match;
use function preg_quote;
use function str_replace;

/**
 * Only for FlexForms which have arbitrary sections, like those generated by EXT:DCE.
 */
class MultiSectionTextResolver extends TextResolver
{
    public function resolve(Demands $demands, Record $record): void
    {
        $regEx = '/' . str_replace('\[ANY\]', '[\w\d]+', preg_quote($this->column, '/')) . '/';

        // Find all local and foreign properties which match the column field
        $localValues = [];
        $localProps = $record->getLocalProps();
        foreach ($localProps as $name => $value) {
            if (1 === preg_match($regEx, $name)) {
                $localValues[$name] = $value;
            }
        }
        $foreignValues = [];
        $foreignProps = $record->getForeignProps();
        foreach ($foreignProps as $name => $value) {
            if (isset($localValues[$name]) && $localValues[$name] !== $value) {
                $foreignValues[$name] = $value;
            }
        }

        // Only use keys of $localValues. Foreign must not have more fields than local.
        foreach (array_keys($localValues) as $key) {
            $localValue = $localValues[$key] ?? '';
            $foreignValue = $foreignValues[$key] ?? '';
            $values = $localValue === $foreignValue ? [$localValue] : [$localValue, $foreignValue];
            foreach ($values as $text) {
                $this->findRelationsInText($demands, $text, $record);
            }
        }
    }
}
