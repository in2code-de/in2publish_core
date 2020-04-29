<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Service\Exception;

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

/**
 * Class PageDoesNotExistException
 */
class PageDoesNotExistException extends In2publishCoreException
{
    protected const MESSAGE = 'A page with ID %d does not exist on %s';
    public const CODE = 1573811622;

    /**
     * @param int $pid
     * @param string $side
     *
     * @return PageDoesNotExistException
     */
    public static function forMissingPage(int $pid, string $side): self
    {
        return new self(sprintf(self::MESSAGE, $pid, $side), self::CODE);
    }
}
