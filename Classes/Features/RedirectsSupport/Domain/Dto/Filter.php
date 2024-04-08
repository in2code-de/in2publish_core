<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Domain\Dto;

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

use In2code\In2publishCore\Utility\DatabaseUtility;

class Filter
{
    protected ?string $domain;
    protected ?string $source;
    protected ?string $target;
    protected ?int $code;
    protected ?string $association;
    protected ?string $publishable;

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(?string $domain): void
    {
        $this->domain = $domain ?: null;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source ?: null;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(?string $target): void
    {
        $this->target = $target ?: null;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): void
    {
        $this->code = $code;
    }

    public function getAssociation(): ?string
    {
        return $this->association;
    }

    public function setAssociation(?string $association): void
    {
        $this->association = $association ?: null;
    }

    public function getPublishable(): ?string
    {
        return $this->publishable;
    }

    public function setPublishable(?string $publishable): void
    {
        $this->publishable = $publishable;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) The method is pretty easy to understand
     * @SuppressWarnings(PHPMD.NPathComplexity) The method is pretty easy to understand
     */
    public function toAdditionWhere(): array
    {
        $database = DatabaseUtility::buildLocalDatabaseConnection();
        $where = [];

        if (null !== $this->domain) {
            $where[] = 'source_host = ' . $database->quote($this->domain);
        }
        if (null !== $this->source) {
            $where[] = 'source_path LIKE ' . $database->quote('%' . $this->source . '%');
        }
        if (null !== $this->target) {
            $where[] = 'target LIKE ' . $database->quote('%' . $this->target . '%');
        }
        if (null !== $this->code) {
            $where[] = 'target_statuscode = ' . $database->quote($this->code);
        }
        if (null !== $this->association) {
            if ('present' === $this->association) {
                $where[] = '(
                    tx_in2publishcore_foreign_site_id IS NOT NULL
                    OR tx_in2publishcore_page_uid IS NOT NULL
                    OR source_host = \'*\'
                )';
            }
            if ('missing' === $this->association) {
                $where[] = '(
                    tx_in2publishcore_foreign_site_id IS NULL
                    AND tx_in2publishcore_page_uid IS NULL
                    AND source_host != \'*\'
                )';
            }
        }

        return $where;
    }
}
