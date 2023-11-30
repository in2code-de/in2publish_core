<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Cache;

/*
 * Copyright notice
 *
 * (c) 2023 in2code.de and the following authors:
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

use Closure;
use In2code\In2publishCore\Cache\Exception\CacheableValueCanNotBeGeneratedException;
use In2code\In2publishCore\CommonInjection\CacheInjection;
use TYPO3\CMS\Core\SingletonInterface;

use function array_key_exists;

class CachedRuntimeCache implements SingletonInterface
{
    use CacheInjection;

    protected array $rtc = [];

    /**
     * @return mixed
     */
    public function get(string $key, Closure $valueFactory, int $ttl = 86400)
    {
        if (!array_key_exists($key, $this->rtc)) {
            if (!$this->cache->has($key)) {
                try {
                    $value = $valueFactory();
                    $this->cache->set($key, $value, [], $ttl);
                } catch (CacheableValueCanNotBeGeneratedException $exception) {
                    $value = $exception->getValue();
                }
            } else {
                $value = $this->cache->get($key);
            }
            $this->rtc[$key] = $value;
        }

        return $this->rtc[$key];
    }
}
