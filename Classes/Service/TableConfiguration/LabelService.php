<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\TableConfiguration;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
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

use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Service\Configuration\TcaServiceInjection;

use function sprintf;
use function trim;

class LabelService
{
    use TcaServiceInjection;

    protected string $emptyFieldValue = '---';

    /**
     * Get label field from record
     *
     * @param Record $record
     * @param string $stagingLevel "local" or "foreign"
     *
     * @return string
     */
    public function getLabelField(Record $record, string $stagingLevel = 'local'): string
    {
        $table = $record->getClassification();

        if ($table === 'sys_file_reference') {
            switch ($stagingLevel) {
                case 'local':
                    $props = $record->getLocalProps();
                    break;
                case 'foreign':
                    $props = $record->getForeignProps();
                    break;
            }
            if (empty($props['uid_local'])) {
                return '---';
            }
            return sprintf(
                '%d [%d,%d]',
                $record->getId(),
                $props['uid_local'],
                $props['uid_foreign']
            );
        }
        $props = $record->getPropsBySide($stagingLevel);
        if (empty($props)) {
            return $this->emptyFieldValue;
        }
        $label = $this->tcaService->getRecordLabel($props, $table);
        if (trim($label) === '') {
            $label = $this->emptyFieldValue;
        }
        return $label;
    }
}
