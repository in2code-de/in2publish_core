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

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Driver\RemoteFileAbstractionLayerDriver;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Utility\UriUtility;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use function ltrim;
use function rtrim;

class BuildResourcePathViewHelper extends AbstractViewHelper
{
    /**
     * @var Uri[]
     */
    protected $domains;

    /** @var ConfigContainer */
    protected $configContainer;

    /** @var ResourceFactory */
    protected $resourceFactory;

    /** @var RemoteFileAbstractionLayerDriver */
    protected $remoteFileAbstractionLayerDriver;

    public function __construct(
        ConfigContainer $configContainer,
        ResourceFactory $resourceFactory,
        RemoteFileAbstractionLayerDriver $remoteFileAbstractionLayerDriver
    ) {
        $this->configContainer = $configContainer;
        $this->resourceFactory = $resourceFactory;
        $this->remoteFileAbstractionLayerDriver = $remoteFileAbstractionLayerDriver;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function initialize(): void
    {
        $config = $this->configContainer->get('filePreviewDomainName');
        foreach (['local', 'foreign'] as $stagingLevel) {
            $this->domains[$stagingLevel] = UriUtility::normalizeUri(new Uri($config[$stagingLevel]));
        }
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('record', Record::class, 'record of the file, which needs the ressource path', true);
        $this->registerArgument('stagingLevel', 'string', 'Sets the staging level [LOCAL/foreign]', true, 'local');
    }

    /**
     * @return string
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function render(): string
    {
        /** @var Record $record */
        $record = $this->arguments['record'];

        /** @var string $propertyName */
        $stagingLevel = $this->arguments['stagingLevel'];

        $resourceUrl = null;
        if ('sys_file' !== $record->getTableName()) {
            // TODO: maybe throw exception
            return '';
        }

        if ('local' === $stagingLevel && $record->localRecordExists()) {
            $storage = $record->getPropertyBySideIdentifier($stagingLevel, 'storage');
            $identifier = $record->getPropertyBySideIdentifier($stagingLevel, 'identifier');

            /** @var File $file Keep this annotation for the correct method return type generation */
            $file = $this->resourceFactory->getFileObjectByStorageAndIdentifier($storage, $identifier);
            $resourceUrl = $file->getPublicUrl();
        }

        if ('foreign' === $stagingLevel && $record->foreignRecordExists()) {
            $storage = $record->getPropertyBySideIdentifier($stagingLevel, 'storage');
            $identifier = $record->getPropertyBySideIdentifier($stagingLevel, 'identifier');

            $this->remoteFileAbstractionLayerDriver->setStorageUid($storage);
            $this->remoteFileAbstractionLayerDriver->initialize();
            $resourceUrl = $this->remoteFileAbstractionLayerDriver->getPublicUrl($identifier);
        }

        if (null === $resourceUrl) {
            // TODO: maybe throw exception
            return '';
        }

        // If the URI is absolute we don't need to prefix it.
        $resourceUri = new Uri($resourceUrl);
        if (!empty($resourceUri->getHost())) {
            return $resourceUrl;
        }

        $uri = $this->domains[$stagingLevel];
        $uri = $uri->withPath(rtrim($uri->getPath(), '/') . '/' . ltrim($resourceUrl, '/'));
        return (string)$uri;
    }
}
