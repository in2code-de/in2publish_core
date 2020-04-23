<?php

declare(strict_types=1);
namespace In2code\In2publishCore\ViewHelpers\Uri;

/*
 * Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
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

use In2code\In2publishCore\Service\Routing\SiteService;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class CompareUriViewHelper extends AbstractTagBasedViewHelper
{
    protected const ARG_IDENTIFIER = 'identifier';

    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     *
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument(self::ARG_IDENTIFIER, 'string', 'The uid of the page to compare', true);
    }

    public function render(): string
    {
        $identifier = $this->arguments[self::ARG_IDENTIFIER];

        $siteService = GeneralUtility::makeInstance(SiteService::class);
        $site = $siteService->getSiteForPidAndStagingLevel($identifier, 'local');
        if (null === $site) {
            return '';
        }
        $url = '';
        try {
            $url = $site->getRouter()->generateUri(
                $identifier,
                [
                    'id' => $identifier,
                    'tx_in2publishcore_pi1[identifier]' => $identifier,
                    'type' => 9815,
                ]
            );
        } catch (Throwable $exception) {
        }
        if (empty($url)) {
            return '';
        }

        $this->tag->setContent($this->renderChildren());
        $this->tag->addAttribute('href', $url);
        $this->tag->addAttribute('target', '_blank');
        return $this->tag->render();
    }
}
