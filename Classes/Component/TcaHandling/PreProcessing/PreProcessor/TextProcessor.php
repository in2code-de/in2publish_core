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
        return new TextResolver($column);
    }
}
