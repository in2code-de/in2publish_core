<?php

namespace In2code\In2publishCore\Event;

final class BeforePublishingTranslationsEvent
{
    private $record;
    private $shouldProcessTranslations = false;

    public function __construct($record, bool $includeChildPages)
    {
        $this->record = $record;
    }

    public function getRecord()
    {
        return $this->record;
    }

    public function shouldProcessTranslations(): bool
    {
        return $this->shouldProcessTranslations;
    }

    public function setShouldProcessTranslations(bool $process): void
    {
        $this->shouldProcessTranslations = $process;
    }
}