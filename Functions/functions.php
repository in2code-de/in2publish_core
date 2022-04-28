<?php

namespace In2code\In2publishCore {

    use In2code\In2publishCore\Domain\Model\Record;

    use function function_exists;

    if (!function_exists('\In2code\In2publishCore\record_key')) {
        function record_key(Record $record): string
        {
            return $record->getClassification() . "\0" . $record->getId();
        }
    }
}
