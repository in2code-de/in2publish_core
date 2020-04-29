<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Attribute;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class DirtyPropertiesIconDataAttributesViewHelper
 */
class DirtyPropertiesIconDataAttributesViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @param RenderingContextInterface $renderingContext
     */
    public function setRenderingContext(RenderingContextInterface $renderingContext)
    {
        parent::setRenderingContext($renderingContext);
        if ($renderingContext instanceof RenderingContext) {
            $this->controllerContext = $renderingContext->getControllerContext();
        }
    }

    /**
     *
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('record', RecordInterface::class, 'record of which to get the dirty properties', true);
    }

    /**
     * Get data attributes for i-icon
     *
     * @param Record $record
     *
     * @return string
     */
    public function render(): string
    {
        $attributesString = 'data-action="opendirtypropertieslistcontainer"';
        if (GeneralUtility::makeInstance(ConfigContainer::class)->get('factory.simpleOverviewAndAjax')) {
            $attributesString .= $this->getDataAttributesForSimpleOverviewAndAjax($this->arguments['record']);
        }
        return $attributesString;
    }

    /**
     * @param Record $record
     *
     * @return string
     */
    protected function getDataAttributesForSimpleOverviewAndAjax(Record $record): string
    {
        $attributesString = ' data-action-ajax-uri="' . $this->getAjaxUri($record) . '"';
        $attributesString .= ' data-action-ajax-result=".' . $this->getAjaxContainerClassName($record) . '"';
        $attributesString .= ' data-action-ajax-once="true" data-action="opendirtypropertieslistcontainer"';
        return $attributesString;
    }

    /**
     * @param Record $record
     *
     * @return string
     */
    protected function getAjaxUri(Record $record): string
    {
        return $this
            ->controllerContext
            ->getUriBuilder()
            ->uriFor(
                'detail',
                ['identifier' => $record->getIdentifier(), 'tableName' => $record->getTableName()],
                'Record'
            );
    }

    /**
     * @param Record $record
     *
     * @return string
     */
    protected function getAjaxContainerClassName(Record $record): string
    {
        return 'simpleOverviewAndAjaxContainerForRecord' . $record->getTableName() . $record->getIdentifier();
    }
}
