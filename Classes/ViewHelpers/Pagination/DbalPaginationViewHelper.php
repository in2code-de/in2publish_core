<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Pagination;

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

use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @property RenderingContext $renderingContext
 */
class DbalPaginationViewHelper extends AbstractViewHelper
{
    private const ARG_QUERY = 'query';
    private const ARG_AS = 'as';
    private const ARG_LIMIT = 'limit';

    protected $escapeChildren = false;
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(self::ARG_QUERY, QueryBuilder::class, 'The query to paginate');
        $this->registerArgument(self::ARG_AS, 'string', 'Name of the variable where the results are available in');
        $this->registerArgument(self::ARG_LIMIT, 'integer', 'Amount of results to show per page', false, 20);
    }

    public function render(): string
    {
        if (!$this->templateVariableContainer->exists('pagination')) {
            return '<div style="color: red">ERROR: Pagination variable not found. Did you forget to assign it in your controller?</div>';
        }
        $controllerContext = $this->renderingContext->getControllerContext();
        if (null === $controllerContext) {
            return '<div style="color: red">ERROR: The controller context null.</div>';
        }

        /** @var QueryBuilder $query */
        $query = $this->arguments[self::ARG_QUERY];
        /** @var string $asVariable */
        $asVariable = $this->arguments[self::ARG_AS];
        /** @var int $limit */
        $limit = $this->arguments[self::ARG_LIMIT];

        /** @var array $pagination */
        $pagination = $this->templateVariableContainer->get('pagination');
        $pagination['currentPage'] = $pagination['currentPage'] ?? 1;

        $countQuery = clone $query;
        $countResult = $countQuery->count('*')->execute();
        $numberOfResults = $countResult->fetchOne();
        $numberOfPages = (int)ceil($numberOfResults / $limit);

        $pagination['numberOfPages'] = $numberOfPages;
        $pagination['maxResults'] = $numberOfResults;
        $pagination['firstResult'] = (($pagination['currentPage'] - 1) * $limit) + 1;
        $pagination['lastResult'] = min($pagination['currentPage'] * $limit, $pagination['maxResults']);

        $pagination['previousPage'] = null;
        if ($pagination['currentPage'] > 1) {
            $pagination['previousPage'] = $pagination['currentPage'] - 1;
        }

        $pagination['nextPage'] = null;
        if ($pagination['currentPage'] < $pagination['numberOfPages']) {
            $pagination['nextPage'] = $pagination['currentPage'] + 1;
        }

        $pagination['isLastPage'] = $pagination['currentPage'] === $pagination['numberOfPages'];
        $pagination['isFirstPage'] = $pagination['currentPage'] === 1;

        $query->setMaxResults($limit);
        $query->setFirstResult(($pagination['currentPage'] - 1) * $limit);

        $statement = $query->execute();

        $this->templateVariableContainer->add($asVariable, $statement);
        $content = $this->renderChildren();
        $this->templateVariableContainer->remove($asVariable);
        $content .= $this->renderPaginationNavigation($pagination);
        return $content;
    }

    public function renderPaginationNavigation(array $pagination): string
    {
        $paginationView = new StandaloneView();
        $paginationView->setRenderingContext($this->renderingContext);
        return $paginationView->renderPartial('Pagination/Navigation', null, ['pagination' => $pagination]);
    }
}
