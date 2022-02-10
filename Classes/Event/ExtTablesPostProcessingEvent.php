<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

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

/**
 * This class replaces the "ExtTablesPostProcessingHook" which was registered using
 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'] The actual hook was removed
 * and the suggested replacement is listening to the TYPO3\CMS\Core\Core\Event\BootCompletedEvent. The problem with
 * that event is though, that IT IS DISPATCHED BEFORE ext_tables.php files are included, which ultimately defeats the
 * purpose. So we here we go again and do it ourselves.
 *
 * Required until the patch got merged and released.
 *
 * Issue: https://forge.typo3.org/issues/95962
 * Patch: https://review.typo3.org/c/Packages/TYPO3.CMS/+/72160
 */
final class ExtTablesPostProcessingEvent
{
}
