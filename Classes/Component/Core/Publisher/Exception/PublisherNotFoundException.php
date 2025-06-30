<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Exception;

use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function sprintf;

/**
 * @codeCoverageIgnore
 */
class PublisherNotFoundException extends In2publishCoreException
{
    private const MESSAGE = 'Can not find a suitable publisher for record with classification "%s" and identifier "%s"';
    public const CODE = 1657192684;
    private Record $record;

    public function __construct(Record $record, ?Throwable $previous = null)
    {
        $this->record = $record;
        parent::__construct(
            sprintf(self::MESSAGE, $record->getClassification(), $record->getId()),
            self::CODE,
            $previous,
        );
    }

    public function getRecord(): Record
    {
        return $this->record;
    }
}
