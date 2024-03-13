<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\CommonInjection\EventDispatcherInjection;
use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Event\DemandsForTextWereCollected;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function htmlspecialchars_decode;
use function parse_str;
use function parse_url;
use function preg_match_all;
use function strpos;

class TextResolver extends AbstractResolver
{
    use EventDispatcherInjection;

    private const REGEX_T3URN = '~(?P<URN>t3\://(?:file)\?uid=\d+)(?:#[\w\d\-\_\!]+)?~';
    protected string $column;

    public function configure(string $column): void
    {
        $this->column = $column;
    }

    public function getTargetTables(): array
    {
        return ['sys_file'];
    }

    public function resolve(Demands $demands, Record $record): void
    {
        $localValue = $record->getLocalProps()[$this->column] ?? '';
        $foreignValue = $record->getForeignProps()[$this->column] ?? '';

        $values = $localValue === $foreignValue ? [$localValue] : [$localValue, $foreignValue];
        foreach ($values as $text) {
            $this->findRelationsInText($demands, $text, $record);
        }
    }

    protected function findRelationsInText(Demands $demands, string $text, Record $record): void
    {
        if (strpos($text, 't3://') === false) {
            return;
        }
        preg_match_all(self::REGEX_T3URN, $text, $matches);
        if (empty($matches['URN'])) {
            return;
        }

        foreach ($matches['URN'] as $urn) {
            // Do NOT use LinkService because the URN might either be not local or not available or trigger FAL.
            $urnParsed = parse_url($urn);
            parse_str(htmlspecialchars_decode($urnParsed['query']), $data);

            if ('file' === $urnParsed['host'] && isset($data['uid'])) {
                $demands->addDemand(new SelectDemand('sys_file', '', 'uid', $data['uid'], $record));
            }
        }

        $this->eventDispatcher->dispatch(new DemandsForTextWereCollected($demands, $record, $text));
    }

    public function __serialize(): array
    {
        return [
            'metaInfo' => $this->metaInfo,
            'column' => $this->column,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->metaInfo = $data['metaInfo'];
        $this->configure($data['column']);
        $this->injectEventDispatcher(GeneralUtility::makeInstance(EventDispatcher::class));
    }
}
