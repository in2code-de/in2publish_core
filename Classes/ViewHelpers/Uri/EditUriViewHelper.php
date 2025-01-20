<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Uri;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class EditUriViewHelper extends AbstractViewHelper
{
    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('tableName', 'string', 'table name of record to be edited', true);
        $this->registerArgument('uid', 'integer', 'identifier of the record to be edited', true);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws RouteNotFoundException
     */
    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        $returnUrl = '';
        if ($request instanceof ServerRequestInterface
            && ApplicationType::fromRequest($request)->isBackend()
        ) {
            $returnUrl = (string)$request->getUri();
        }

        return (string)$uriBuilder->buildUriFromRoute('record_edit', [
            'edit[' . $arguments['tableName'] . '][' . $arguments['uid'] . ']' => 'edit',
            'returnUrl' => $returnUrl,
        ]);
    }
}
