<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use Closure;
use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Domain\Model\Record;

use function htmlspecialchars_decode;
use function parse_str;
use function parse_url;
use function preg_match_all;
use function strpos;

class TextProcessor extends AbstractProcessor
{
    public const REGEX_T3URN = '~[\"\'\s](?P<URN>t3\://(?:file|page)\?uid=\d+)[\"\'\s]~';

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

    protected function buildResolver(string $table, string $column, array $processedTca): Closure
    {
        return function (Demands $demands, Record $record) use ($column): void {
            $localValue = $record->getLocalProps()[$column] ?? '';
            $foreignValue = $record->getForeignProps()[$column] ?? '';

            $values = $localValue === $foreignValue ? [$localValue] : [$localValue, $foreignValue];
            foreach ($values as $text) {
                $this->findRelationsInText($demands, $text, $record);
            }
        };
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
    }
}
