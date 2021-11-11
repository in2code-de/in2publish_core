<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Factory;

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

use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Factory\TaskFactory as NewTaskFactory;

use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * @deprecated Please use \In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Factory\TaskFactory
 *     directly.
 */
class TaskFactory extends NewTaskFactory
{
    private const DEPRECATION_MESSAGE = 'The class ' . self::class . ' has been moved. Please use the new class '
                                        . parent::class . ' instead.';

    public function __construct()
    {
        trigger_error(self::DEPRECATION_MESSAGE, E_USER_DEPRECATED);
    }
}
