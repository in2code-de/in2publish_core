<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Application;

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

use In2code\In2publishCore\CommonInjection\CacheInjection;
use In2code\In2publishCore\CommonInjection\SiteFinderInjection;
use In2code\In2publishCore\Service\ForeignSiteFinderInjection;
use In2code\In2publishCore\Testing\Tests\Adapter\RemoteAdapterTest;
use In2code\In2publishCore\Testing\Tests\Database\ForeignDatabaseTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;

use function array_diff;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_unique;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

class SiteConfigurationTest implements TestCaseInterface
{
    use SiteFinderInjection;
    use ForeignSiteFinderInjection;
    use CacheInjection;

    public function run(): TestResult
    {
        // Clear the caches of the foreign site finder
        $this->cache->remove('sites');

        $localSites = $this->siteFinder->getAllSites(false);
        $foreignSites = $this->foreignSiteFinder->getAllSites();

        $siteErrors = [];

        $siteIdentifiers = array_unique(array_merge(array_keys($localSites), array_keys($foreignSites)));
        foreach ($siteIdentifiers as $siteIdentifier) {
            if (!array_key_exists($siteIdentifier, $localSites)) {
                $siteErrors[] = sprintf('The site %s does not exist on local', $siteIdentifier);
            } elseif (!array_key_exists($siteIdentifier, $foreignSites)) {
                $siteErrors[] = sprintf('The site %s does not exist on foreign', $siteIdentifier);
            } else {
                $localLanguages = $localSites[$siteIdentifier]->getAllLanguages();
                $foreignLanguages = $foreignSites[$siteIdentifier]->getAllLanguages();

                $languages = array_unique(array_merge(array_keys($localLanguages), array_keys($foreignLanguages)));

                foreach ($languages as $language) {
                    if (!array_key_exists($language, $localLanguages)) {
                        $siteErrors[] = sprintf(
                            'Site %s: The site language %d does not exist on local',
                            $siteIdentifier,
                            $language,
                        );
                    } elseif (!array_key_exists($language, $foreignLanguages)) {
                        $siteErrors[] = sprintf(
                            'Site %s: The site language %d does not exist on foreign',
                            $siteIdentifier,
                            $language,
                        );
                    } else {
                        $localLanguage = $localLanguages[$language];
                        $foreignLanguage = $foreignLanguages[$language];

                        $localLanguageArray = [
                            'languageId' => $localLanguage->getLanguageId(),
                            'locale' => $localLanguage->getLocale(),
                            'flagIdentifier' => $localLanguage->getFlagIdentifier(),
                            'hreflang' => $localLanguage->getHreflang(),
                            'typo3Language' => $localLanguage->getTypo3Language(),
                            'fallbackType' => $localLanguage->getFallbackType(),
                            'fallbackLanguageIds' => json_encode(
                                $localLanguage->getFallbackLanguageIds(),
                                JSON_THROW_ON_ERROR,
                            ),
                            'enabled' => $localLanguage->isEnabled(),
                        ];

                        $foreignLanguageArray = [
                            'languageId' => $foreignLanguage->getLanguageId(),
                            'locale' => $foreignLanguage->getLocale(),
                            'flagIdentifier' => $foreignLanguage->getFlagIdentifier(),
                            'hreflang' => $foreignLanguage->getHreflang(),
                            'typo3Language' => $foreignLanguage->getTypo3Language(),
                            'fallbackType' => $foreignLanguage->getFallbackType(),
                            'fallbackLanguageIds' => json_encode(
                                $foreignLanguage->getFallbackLanguageIds(),
                                JSON_THROW_ON_ERROR,
                            ),
                            'enabled' => $foreignLanguage->isEnabled(),
                        ];

                        $differences = array_keys(array_diff($localLanguageArray, $foreignLanguageArray));
                        foreach ($differences as $difference) {
                            $siteErrors[] = sprintf(
                                'Site %s differences in %s: %s <> %s',
                                $siteIdentifier,
                                $difference,
                                $localLanguageArray[$difference],
                                $foreignLanguageArray[$difference],
                            );
                        }
                    }
                }
            }
        }

        if (!empty($siteErrors)) {
            return new TestResult('application.site_configuration.invalid', TestResult::ERROR, $siteErrors);
        }
        return new TestResult('application.site_configuration.valid');
    }

    public function getDependencies(): array
    {
        return [
            ForeignDatabaseTest::class,
            RemoteAdapterTest::class,
        ];
    }
}
