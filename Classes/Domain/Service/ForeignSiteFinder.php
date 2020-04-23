<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Domain\Service;

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

use In2code\In2publishCore\Command\Status\SiteConfigurationCommand;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\In2publishCoreException;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function base64_decode;
use function explode;
use function unserialize;

/**
 * Class ForeignSiteFinder
 */
class ForeignSiteFinder
{
    /**
     * @var RemoteCommandDispatcher
     */
    protected $rceDispatcher = null;

    /**
     * @var VariableFrontend
     */
    protected $cache = null;

    /**
     * ForeignSiteFinder constructor.
     */
    public function __construct()
    {
        $this->rceDispatcher = GeneralUtility::makeInstance(RemoteCommandDispatcher::class);
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('in2publish_core');
    }

    /**
     * @param int $pageId
     *
     * @return Site
     * @throws In2publishCoreException
     * @throws SiteNotFoundException
     */
    public function getSiteByPageId(int $pageId): Site
    {
        $cacheKey = 'site_config_' . $pageId;
        if (!$this->cache->has($cacheKey)) {
            $request = GeneralUtility::makeInstance(RemoteCommandRequest::class);
            $request->setCommand(SiteConfigurationCommand::IDENTIFIER);
            $request->setOption((string)$pageId);

            $response = $this->rceDispatcher->dispatch($request);

            if ($response->getExitStatus() === SiteConfigurationCommand::EXIT_NO_SITE) {
                $site = false;
            } elseif ($response->isSuccessful()) {
                $responseText = $response->getOutputString();
                $responseParts = explode(':', $responseText);
                $serializedSite = base64_decode($responseParts[1]);
                $site = unserialize($serializedSite);
            } else {
                throw new In2publishCoreException('An error occurred while fetching a remote site config');
            }
            $this->cache->set($cacheKey, $site);
        }
        $site = isset($site) ? $site : $this->cache->get($cacheKey);

        if (false === $site) {
            throw new SiteNotFoundException();
        }
        return $site;
    }
}
