<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Config\Migration;

/*
 * Copyright notice
 *
 * (c) 2022 in2code.de and the following authors:
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

class IngoredFieldsMigration extends AbstractMigration
{
    protected const MIGRATION_MESSAGE = 'You are using the old "ignoreFieldsForDifferenceView" format to ignore fields for tables. Please use the new "ignoreFields" setting. Your settings have been migrated on the fly.';

    public function migrate(array $config): array
    {
        if (isset($config['ignoreFieldsForDifferenceView'])) {
            $this->addMessage(self::MIGRATION_MESSAGE);
            foreach ($config['ignoreFieldsForDifferenceView'] as $table => $fields) {
                foreach ($fields as $field) {
                    $config['ignoredFields'][$table]['fields'][] = $field;
                }
            }
            unset($config['ignoreFieldsForDifferenceView']);
        }
        return $config;
    }
}
