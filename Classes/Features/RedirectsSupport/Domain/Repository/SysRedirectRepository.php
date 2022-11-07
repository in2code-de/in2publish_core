<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Domain\Repository;

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

use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Model\Dto\Redirect;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

class SysRedirectRepository
{
    use LocalDatabaseInjection;

    public function findHostsOfRedirects(): array
    {
        return $this->getQueryBuilder()
                    ->select('source_host as name')
                    ->from('sys_redirect')
                    ->orderBy('source_host')
                    ->groupBy('source_host')
                    ->execute()
                    ->fetchAllAssociative();
    }

    public function findStatusCodesOfRedirects(): array
    {
        return $this->getQueryBuilder()
                    ->select('target_statuscode as code')
                    ->from('sys_redirect')
                    ->orderBy('target_statuscode')
                    ->groupBy('target_statuscode')
                    ->execute()
                    ->fetchAllAssociative();
    }

    public function findLocalRawByUid(int $redirect): ?Redirect
    {
        $row = $this->getQueryBuilder()
                    ->select('*')
                    ->from('sys_redirect')
                    ->where('uid = :redirect')
                    ->setParameter('redirect', $redirect)
                    ->execute()
                    ->fetchAssociative();
        if (false === $row) {
            return null;
        }
        $redirectDto = new Redirect();
        foreach ($row as $prop => $value) {
            $redirectDto->{$prop} = $value;
        }
        return $redirectDto;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->localDatabase->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder;
    }

    public function update(Redirect $redirect): void
    {
        $this->localDatabase->update(
            'sys_redirect',
            (array)$redirect,
            ['uid' => $redirect->uid]
        );
    }
}
