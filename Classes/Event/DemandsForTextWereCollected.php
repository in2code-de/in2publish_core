<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

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

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Record\Model\Record;

/**
 * @codeCoverageIgnore
 */
final class DemandsForTextWereCollected
{
    protected Demands $demands;
    private Record $record;
    protected string $text;

    public function __construct(Demands $demands, Record $record, string $text)
    {
        $this->demands = $demands;
        $this->record = $record;
        $this->text = $text;
    }

    public function getDemands(): Demands
    {
        return $this->demands;
    }

    public function getRecord(): Record
    {
        return $this->record;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
