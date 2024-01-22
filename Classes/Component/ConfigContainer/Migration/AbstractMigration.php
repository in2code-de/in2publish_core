<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\Migration;

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

use function array_key_exists;
use function sha1;
use function sprintf;
use function trigger_error;

/**
 * @deprecated Use the MigrationMessages trait instead. This class will be remove in in2publish_core v13.
 */
abstract class AbstractMigration implements MigrationInterface
{
    protected const DEPRECATION_MESSAGE = '%s uses %s which is deprecated and will be removed in in2publish_core v13. Use the MigrationMessages trait instead.';
    /** @var string[] */
    protected array $messages = [];

    protected function addMessage(string $message): void
    {
        $key = sha1($message);
        if (!array_key_exists($key, $this->messages)) {
            $this->messages[$key] = $message;
        }
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function __destruct()
    {
        trigger_error(sprintf(self::DEPRECATION_MESSAGE, static::class, self::class));
    }
}
