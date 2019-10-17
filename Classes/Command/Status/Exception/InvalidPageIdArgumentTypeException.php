<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Command\Status\Exception;

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

use In2code\In2publishCore\In2publishCoreException;
use function sprintf;

class InvalidPageIdArgumentTypeException extends In2publishCoreException
{
    private const MESSAGE = 'The argument pageId must be an integer, got string "%s"';
    public const CODE = 1561653002;

    public static function fromGivenPageId(string $pageId): InvalidPageIdArgumentTypeException
    {
        return new self(sprintf(self::MESSAGE, $pageId), self::CODE);
    }
}
