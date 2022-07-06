<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SystemInformationExport\Exporter;

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

use In2code\In2publishCore\Domain\Service\ForeignSiteFinder;
use Throwable;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

class SitesConfigurationExporter implements SystemInformationExporter
{
    protected SiteFinder $siteFinder;
    protected ForeignSiteFinder $foreignSiteFinder;

    public function __construct(SiteFinder $siteFinder, ForeignSiteFinder $foreignSiteFinder)
    {
        $this->siteFinder = $siteFinder;
        $this->foreignSiteFinder = $foreignSiteFinder;
    }

    public function getUniqueKey(): string
    {
        return 'sites';
    }

    public function getInformation(): array
    {
        $siteConfigs = [];

        $localSites = $this->siteFinder->getAllSites(false);
        $foreignSites = $this->foreignSiteFinder->getAllSites();

        /**
         * @var string $side
         * @var Site $site
         */
        foreach (['local' => $localSites, 'foreign' => $foreignSites] as $side => $sites) {
            foreach ($sites as $site) {
                $langs = [];
                $rootPageId = $site->getRootPageId();
                foreach ($site->getAllLanguages() as $language) {
                    $languageId = $language->getLanguageId();
                    try {
                        $uri = $site->getRouter()
                                    ->generateUri((string)$rootPageId, ['_language' => $languageId])
                                    ->__toString();
                    } catch (Throwable $throwable) {
                        $uri = (string)$throwable;
                    }
                    $langs[] = [
                        'base' => $language->getBase()->__toString(),
                        'actualURI' => $uri,
                        'langId' => $languageId,
                        'typo3Lang' => $language->getTypo3Language(),
                        'isocode' => $language->getTwoLetterIsoCode(),
                    ];
                }
                try {
                    $uri = $site->getRouter()->generateUri((string)$rootPageId)->__toString();
                } catch (Throwable $throwable) {
                    $uri = (string)$throwable;
                }
                $siteConfigs[$side][$site->getIdentifier()] = [
                    'rootPageId' => $rootPageId,
                    'base' => $site->getBase()->__toString(),
                    'actualURI' => $uri,
                    'langs' => $langs,
                ];
            }
        }

        return $siteConfigs;
    }
}
