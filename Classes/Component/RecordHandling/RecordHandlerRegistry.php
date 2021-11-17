<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RecordHandling;

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

use function in_array;

class RecordHandlerRegistry
{
    private array $finders = [];

    private array $publishers = [];

    public function isHandlerRegistered(string $service): bool
    {
        return $this->isFinderRegistered($service) || $this->isPublisherRegistered($service);
    }

    public function registerRecordFinder(string $service): void
    {
        $this->finders[] = $service;
    }

    public function isFinderRegistered(string $service): bool
    {
        return in_array($service, $this->finders, true);
    }

    public function getFinders(): array
    {
        return $this->finders;
    }

    public function registerRecordPublisher(string $service): void
    {
        $this->publishers[] = $service;
    }

    public function isPublisherRegistered(string $service): bool
    {
        return in_array($service, $this->publishers, true);
    }

    public function getPublishers(): array
    {
        return $this->publishers;
    }
}
