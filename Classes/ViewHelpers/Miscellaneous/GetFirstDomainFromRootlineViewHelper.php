<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Miscellaneous;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
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

use In2code\In2publishCore\Domain\Service\DomainService;
use In2code\In2publishCore\Service\Routing\SiteService;
use In2code\In2publishCore\Utility\UriUtility;
use In2code\In2publishCore\ViewHelpers\Link\PreviewRecordViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use function ltrim;
use function rtrim;
use function sprintf;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * Class GetFirstDomainFromRootlineViewHelper
 */
class GetFirstDomainFromRootlineViewHelper extends AbstractViewHelper
{
    protected const DEPRECATED_VIEWHELPER = 'The ViewHelper "%s" is deprecated and will be removed in in2publish_core version 11. Use %s instead.';

    /**
     * @var DomainService
     */
    protected $domainService;

    /**
     * GetFirstDomainFromRootlineViewHelper constructor.
     */
    public function __construct()
    {
        $this->domainService = GeneralUtility::makeInstance(DomainService::class);
    }

    /**
     *
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('identifier', 'int', 'The page uid to search in its rootLine', true);
        $this->registerArgument('stagingLevel', 'string', '"local" or "foreign"', false, 'local');
        $this->registerArgument('addProtocol', 'bool', 'Prepend http(s)://? Defaults to true', false, true);
    }

    /**
     * Get domain from rootline without trailing slash
     *
     * @return string
     */
    public function render(): string
    {
        trigger_error(
            sprintf(self::DEPRECATED_VIEWHELPER, static::class, PreviewRecordViewHelper::class),
            E_USER_DEPRECATED
        );
        $identifier = $this->arguments['identifier'];
        $stagingLevel = $this->arguments['stagingLevel'];
        $addProtocol = $this->arguments['addProtocol'];

        $siteService = GeneralUtility::makeInstance(SiteService::class);
        $site = $siteService->getSiteForPidAndStagingLevel($identifier, $stagingLevel);
        if (null === $site) {
            return '';
        }
        // Though the path is part of the Base we will strip it.
        // This VH is used to get a domain. "index.php?id=XX" will be appended to it.
        // Not stripping the path will result in URLs like example.com/subpath/index.php?id=9 which don't work
        $uri = $site->getBase()->withPath('');
        $uri = UriUtility::normalizeUri($uri);
        if (!$addProtocol) {
            $uri = $uri->withScheme('');
        }
        $url = (string)$uri;
        if (!$addProtocol) {
            $url = ltrim($url, '/');
        }
        return rtrim($url, '/');
    }
}
