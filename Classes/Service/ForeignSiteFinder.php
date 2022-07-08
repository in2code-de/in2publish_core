<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service;

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

use Closure;
use In2code\In2publishCore\Command\Foreign\Status\AllSitesCommand;
use In2code\In2publishCore\Command\Foreign\Status\SiteConfigurationCommand;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandResponse;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Exception\AllSitesCommandException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

use function array_key_exists;
use function base64_decode;
use function explode;
use function unserialize;

class ForeignSiteFinder implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const UNSERIALIZE_ALLOWED_CLASS = [Site::class, Uri::class, SiteLanguage::class];
    protected RemoteCommandDispatcher $rceDispatcher;
    protected FrontendInterface $cache;

    public function __construct(FrontendInterface $cache)
    {
        $this->cache = $cache;
    }

    public function injectRemoteCommandDispatcher(RemoteCommandDispatcher $rceDispatcher): void
    {
        $this->rceDispatcher = $rceDispatcher;
    }

    public function getSiteByPageId(int $pageId): Site
    {
        $closure = function () use ($pageId): Site {
            $request = new RemoteCommandRequest();
            $request->setCommand(SiteConfigurationCommand::IDENTIFIER);
            $request->setOption((string)$pageId);

            $response = $this->rceDispatcher->dispatch($request);

            if ($response->getExitStatus() === SiteConfigurationCommand::EXIT_PAGE_HIDDEN_OR_DISCONNECTED) {
                throw new PageNotFoundException('PageNotFound on foreign during site identification', 1619783372);
            }
            if ($response->getExitStatus() === SiteConfigurationCommand::EXIT_NO_SITE) {
                throw new SiteNotFoundException();
            }
            if ($response->isSuccessful()) {
                return $this->processCommandResult($response);
            }
            $this->logger->alert(
                'An error occurred while fetching a remote site config',
                [
                    'code' => $response->getExitStatus(),
                    'errors' => $response->getErrors(),
                    'output' => $response->getOutput(),
                ]
            );
            throw new In2publishCoreException('An error occurred while fetching a remote site config', 1620723511);
        };
        return $this->executeCached('site_page_' . $pageId, $closure);
    }

    /** @return Site[] */
    public function getAllSites(): array
    {
        $closure = function (): array {
            $request = new RemoteCommandRequest();
            $request->setCommand(AllSitesCommand::IDENTIFIER);
            $response = $this->rceDispatcher->dispatch($request);

            if ($response->isSuccessful()) {
                return $this->processCommandResult($response);
            }
            $exitStatus = $response->getExitStatus();
            $errors = $response->getErrors();
            $output = $response->getOutput();
            $this->logger->alert(
                'An error occurred while fetching all foreign sites',
                ['code' => $exitStatus, 'errors' => $errors, 'output' => $output]
            );
            throw new AllSitesCommandException($exitStatus, $errors, $output);
        };
        return $this->executeCached('sites', $closure);
    }

    public function getSiteByIdentifier(string $identifier): ?Site
    {
        $sites = $this->getAllSites();
        if (array_key_exists($identifier, $sites) && $sites[$identifier] instanceof Site) {
            return $sites[$identifier];
        }
        return null;
    }

    protected function processCommandResult(RemoteCommandResponse $response)
    {
        $responseText = $response->getOutputString();
        $responseParts = explode(':', $responseText);
        $serializedSite = base64_decode($responseParts[1]);
        $result = unserialize($serializedSite, ['allowed_classes' => self::UNSERIALIZE_ALLOWED_CLASS]);
        if (false === $result) {
            throw new SiteNotFoundException();
        }
        return $result;
    }

    protected function executeCached(string $cacheKey, Closure $closure)
    {
        if (!$this->cache->has($cacheKey)) {
            $result = $closure();
            $this->cache->set($cacheKey, $result);
            return $result;
        }
        return $this->cache->get($cacheKey);
    }
}
