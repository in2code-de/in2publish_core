<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RecordHandling\DependencyInjection\Exception;

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

use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function implode;
use function sprintf;

class InvalidCompilerPassTagException extends In2publishCoreException
{
    public const CODE = 1638799486;
    private const MESSAGE = 'The compiler pass %s does not handle tags other than %s';

    private string $compilerClass;

    /** @var array<string> */
    private $handledTags;

    public function __construct(string $compilerClass, array $handledTags, Throwable $previous = null)
    {
        $this->compilerClass = $compilerClass;
        $this->handledTags = $handledTags;
        parent::__construct(
            sprintf(self::MESSAGE, $compilerClass, '"' . implode('", "', $handledTags) . '"'),
            self::CODE,
            $previous
        );
    }

    public function getCompilerClass(): string
    {
        return $this->compilerClass;
    }

    /** @return array<string> */
    public function getHandledTags(): array
    {
        return $this->handledTags;
    }
}
