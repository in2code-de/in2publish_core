<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\AdminTools\Service\Exception;

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

use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function sprintf;

/**
 * @codeCoverageIgnore
 */
class ClassNotFoundException extends In2publishCoreException
{
    private const MESSAGE = 'The class "%s" registered as Content Publisher Admin Tool does not exist';
    public const CODE = 1642103517;
    protected string $class;

    public function __construct(string $class, Throwable $previous = null)
    {
        parent::__construct(sprintf(self::MESSAGE, $class), self::CODE, $previous);
        $this->class = $class;
    }

    public function getClass(): string
    {
        return $this->class;
    }
}
