<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Language;

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

use In2code\In2publish\Domain\Model\ContentLanguage;
use In2code\In2publishCore\CommonInjection\BackendUserAuthenticationInjection;
use In2code\In2publishCore\CommonInjection\SiteFinderInjection;
use In2code\In2publishCore\CommonInjection\TranslationConfigurationProviderInjection;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageService;

use function array_key_exists;
use function ksort;

class SiteLanguageService
{
    use BackendUserAuthenticationInjection;
    use SiteFinderInjection;
    use TranslationConfigurationProviderInjection;

    protected LanguageService $languageService;

    public function __construct()
    {
        $this->languageService = $GLOBALS['LANG'];
    }

    /** @return array<ContentLanguage> */
    public function getAllowedLanguages(int $pageId): array
    {
        try {
            $this->siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException $e) {
            // If there's no site for the current PID use 0 instead to
            // trigger specific implementation details of getSystemLanguages
            // which will return all languages
            $pageId = 0;
        }
        $languages = $this->translationConfigurationProvider->getSystemLanguages($pageId);

        if (
            0 === $pageId
            && !array_key_exists(-1, $languages)
            && $this->backendUserAuthentication->checkLanguageAccess(-1)
        ) {
            // Language -1 will not be returned when there's no site, so add it manually
            $languages[-1] = [
                'uid' => -1,
                'title' => $this->languageService->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:multipleLanguages',
                ),
                'ISOcode' => '',
                'flagIcon' => 'flags-multiple',
            ];
        }

        ksort($languages);
        foreach ($languages as $index => $language) {
            $languages[$index] = new ContentLanguage(
                $language['uid'],
                $language['uid'] === 0 ? $language['title'] . ' (default)' : $language['title'],
                $language['ISOcode'] ?? '',
                $language['flagIcon'],
            );
        }
        return $languages;
    }
}
