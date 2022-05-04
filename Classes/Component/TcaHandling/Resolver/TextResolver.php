<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Resolver;

use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Event\DemandsForTextWereCollected;
use Symfony\Component\EventDispatcher\EventDispatcher;

use function htmlspecialchars_decode;
use function parse_str;
use function parse_url;
use function preg_match_all;
use function strpos;

class TextResolver implements Resolver
{
    private const REGEX_T3URN = '~[\"\'\s](?P<URN>t3\://(?:file|page)\?uid=\d+)[\"\'\s]~';

    protected EventDispatcher $eventDispatcher;
    protected string $column;

    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function configure(string $column): void
    {
        $this->column = $column;
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
                $demands->addSelect('sys_file', '', 'uid', $data['uid'], $record);
            }
            if ('page' === $urnParsed['host'] && isset($data['uid'])) {
                $demands->addSelect('pages', '', 'uid', $data['uid'], $record);
            }
        }

        $this->eventDispatcher->dispatch(new DemandsForTextWereCollected($demands, $record, $text));
    }
}
