<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Context;

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

use LogicException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;

use function getenv;
use function in_array;

class ContextService implements SingletonInterface
{
    public const LOCAL = 'Local';
    public const FOREIGN = 'Foreign';
    public const ENV_VAR_NAME = 'IN2PUBLISH_CONTEXT';
    public const REDIRECT_ENV_VAR_NAME = 'REDIRECT_IN2PUBLISH_CONTEXT';

    protected string $context;

    public function __construct()
    {
        $this->context = $this->determineContext();
    }

    public function getContext(): string
    {
        return $this->context;
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    protected function determineContext(): string
    {
        $environmentVariable = getenv(static::ENV_VAR_NAME) ?: getenv(static::REDIRECT_ENV_VAR_NAME) ?: false;
        if (false === $environmentVariable) {
            return static::FOREIGN;
        }
        if (in_array($environmentVariable, [static::LOCAL, static::FOREIGN], true)) {
            return $environmentVariable;
        }
        if (Environment::getContext()->isProduction()) {
            return static::FOREIGN;
        }
        throw new LogicException('The defined in2publish context is not supported', 1469717011);
    }

    public function isForeign(): bool
    {
        return static::FOREIGN === $this->context;
    }

    public function isLocal(): bool
    {
        return static::LOCAL === $this->context;
    }

    public function isContextDefined(): bool
    {
        return (false !== getenv(static::ENV_VAR_NAME)) || (false !== getenv(static::REDIRECT_ENV_VAR_NAME));
    }
}
