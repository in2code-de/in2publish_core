<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Repository;

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

/**
 * @deprecated Please use the interfaces RecordFinder and RecordPublisher accordingly t your requirements.
 */
abstract class CommonRepository
{
    /**
     * @deprecated Listen to the new event RelatedRecordsByRteWereFetched instead.
     *  The signal and this constant will be removed in in2publish_core version 11.
     */
    public const SIGNAL_RELATION_RESOLVER_RTE = 'relationResolverRTE';
}
