<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\File;

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

use In2code\In2publishCore\CommonInjection\SiteFinderInjection;
use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Service\ForeignSiteFinderInjection;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use function ltrim;
use function rtrim;

class BuildResourcePathViewHelper extends AbstractViewHelper
{
    use SiteFinderInjection;
    use ForeignSiteFinderInjection;

    protected ConfigContainer $configContainer;

    protected ResourceFactory $resourceFactory;

    /** @var Uri[] */
    protected array $domains;

    public function __construct(
        ResourceFactory $resourceFactory
    ) {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @throws SiteNotFoundException
     */
    public function initialize(): void
    {
        $this->domains['local'] = $this->siteFinder->getSiteByIdentifier('main')->getBase();
        $this->domains['foreign'] = $this->foreignSiteFinder->getSiteByIdentifier('main')->getBase();
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('publicUrl', 'string', 'Url of the record', true);
        $this->registerArgument('stagingLevel', 'string', 'Sets the staging level [LOCAL/foreign]', true, 'local');
    }

    public function render(): string
    {
        /** @var string $stagingLevel */
        $stagingLevel = $this->arguments['stagingLevel'];

        /** @var string $publicUrl */
        $publicUrl = $this->arguments['publicUrl'];

        // If the URI is absolute we don't need to prefix it.
        $resourceUri = new Uri($publicUrl);
        if (!empty($resourceUri->getHost())) {
            return $publicUrl;
        }

        $uri = $this->domains[$stagingLevel];
        $uri = $uri->withPath(rtrim($uri->getPath(), '/') . '/' . ltrim($publicUrl, '/'));
        return (string)$uri;
    }
}
