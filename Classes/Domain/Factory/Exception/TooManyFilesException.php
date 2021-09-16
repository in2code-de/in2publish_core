<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Factory\Exception;

/*
 * Copyright notice
 *
 * (c) 2019 in2code.de and the following authors:
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

abstract class TooManyFilesException extends In2publishCoreException
{
    public const CODE = 1555492787;
    public const MESSAGE = 'The folder "%s" has too many files (%d). The threshold is %d.';

    /** @var string */
    protected $folder;

    /** @var int */
    protected $count;

    /** @var int */
    protected $threshold;

    public function __construct(string $folder, int $count, int $threshold, Throwable $previous = null)
    {
        parent::__construct(sprintf(static::MESSAGE, $folder, $count, $threshold), static::CODE, $previous);
        $this->folder = $folder;
        $this->count = $count;
        $this->threshold = $threshold;
    }

    public function getFolder(): string
    {
        return $this->folder;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }
}
