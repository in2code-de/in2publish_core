<?php

declare(strict_types=1);

/*
 * Copyright notice
 *
 * (c) 2024 in2code.de and the following authors:
 * Daniel Hoffmann <daniel.hoffmann@in2code.de>
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

namespace In2code\In2publishCore\Features\RedirectsSupport\Event;

final class RedirectsWereFilteredForListing
{
    private array $redirects;

    public function __construct(array $redirects)
    {
        $this->redirects = $redirects;
    }

    public function getRedirects(): array
    {
        return $this->redirects;
    }

    public function setRedirects(array $redirects): void
    {
        $this->redirects = $redirects;
    }
}
