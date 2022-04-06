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

use TYPO3\CMS\Core\Utility\GeneralUtility;

use function in_array;

class InputProcessor extends TextProcessor
{
    protected string $type = 'input';

    protected function additionalPreProcess(string $table, string $column, array $tca): array
    {
        if ($tca['renderType'] === 'inputLink') {
            return [];
        }

        if (isset($tca['softref'])) {
            $softRef = GeneralUtility::trimExplode(',', $tca['softref'] ?? '', true);
            if (in_array('typolink', $softRef, true) || in_array('typolink_tag', $softRef, true)) {
                return [];
            }
        }
        return [
            'An input field must either have renderType="inputLink" or softref="typolink" or softref="typolink_tag"',
        ];
    }
}
