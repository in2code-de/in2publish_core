<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Data;

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

use In2code\In2publishCore\Event\RequiredTablesWereIdentified;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\SingletonInterface;

class RequiredTablesDataProvider implements SingletonInterface
{
    /** @var EventDispatcher */
    protected $eventDispatcher;

    /** * @var array */
    protected $cache = [];

    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getRequiredTables(): array
    {
        if (empty($this->cache)) {
            $requiredTables = [
                'tx_in2publishcore_log',
                'tx_in2code_in2publish_task',
                'tx_in2code_in2publish_envelope',
            ];
            $requiredTables = $this->overruleTables($requiredTables);
            $this->cache = $requiredTables;
        }
        return $this->cache;
    }

    /**
     * @param array<int, string> $tables
     * @return array<int, string>
     */
    protected function overruleTables(array $tables): array
    {
        $event = new RequiredTablesWereIdentified($tables);
        $this->eventDispatcher->dispatch($event);
        return $event->getTables();
    }
}
