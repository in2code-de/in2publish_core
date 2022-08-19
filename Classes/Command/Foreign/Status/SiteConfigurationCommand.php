<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Command\Foreign\Status;

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

use In2code\In2publishCore\Command\Foreign\Status\Exception\InvalidPageIdArgumentTypeException;
use In2code\In2publishCore\CommonInjection\SiteFinderInjection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

use function base64_encode;
use function serialize;

class SiteConfigurationCommand extends Command
{
    use SiteFinderInjection;

    public const ARG_PAGE_ID = 'pageId';
    public const ARG_PAGE_ID_DESCRIPTION = 'The page id to retrieve the site config for';
    public const EXIT_NO_SITE = 250;
    public const EXIT_PAGE_HIDDEN_OR_DISCONNECTED = 251;
    public const IDENTIFIER = 'in2publish_core:status:siteconfiguration';

    protected function configure(): void
    {
        $this->addArgument(self::ARG_PAGE_ID, InputArgument::REQUIRED, self::ARG_PAGE_ID_DESCRIPTION);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $pageId = $input->getArgument(self::ARG_PAGE_ID);
        if (null !== $pageId && $pageId !== (string)(int)$pageId) {
            throw new InvalidPageIdArgumentTypeException($pageId);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pageId = (int)$input->getArgument(self::ARG_PAGE_ID);
        try {
            $site = $this->siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException $e) {
            try {
                GeneralUtility::makeInstance(RootlineUtility::class, $pageId, null)->get();
            } catch (PageNotFoundException $e) {
                return static::EXIT_PAGE_HIDDEN_OR_DISCONNECTED;
            }
            return static::EXIT_NO_SITE;
        }
        $output->writeln('Site: ' . base64_encode(serialize($site)));
        return Command::SUCCESS;
    }
}
