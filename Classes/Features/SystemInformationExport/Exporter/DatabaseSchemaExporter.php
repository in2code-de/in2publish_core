<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SystemInformationExport\Exporter;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\Utility\DatabaseUtility;

class DatabaseSchemaExporter implements SystemInformationExporter
{
    public function getUniqueKey(): string
    {
        return 'schema';
    }

    public function getInformation(): array
    {
        $schema = [];
        foreach (['local', 'foreign'] as $side) {
            $schemaManager = DatabaseUtility::buildDatabaseConnectionForSide($side)->getSchemaManager();
            foreach ($schemaManager->listTables() as $table) {
                $schema[$side][$table->getName()]['options'] = $table->getOptions();
                foreach ($table->getColumns() as $column) {
                    $schema[$side][$table->getName()]['columns'][$column->getName()] = $column->toArray();
                }
                foreach ($table->getIndexes() as $index) {
                    $schema[$side][$table->getName()]['indexes'][$index->getName()] = [
                        'columns' => $index->getColumns(),
                        'isPrimary' => $index->isPrimary(),
                        'isSimple' => $index->isSimpleIndex(),
                        'isUnique' => $index->isUnique(),
                        'isQuoted' => $index->isQuoted(),
                        'options' => $index->getOptions(),
                        'flags' => $index->getFlags(),
                    ];
                }
                foreach ($table->getForeignKeys() as $foreignKey) {
                    $schema[$side][$table->getName()]['fk'][$foreignKey->getName()] = [
                        'isQuoted' => $foreignKey->isQuoted(),
                        'options' => $foreignKey->getOptions(),
                    ];
                }
            }
        }

        return $schema;
    }
}
