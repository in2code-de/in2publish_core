<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Uri;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class EditUriViewHelper extends AbstractViewHelper
{
    public function __construct(private readonly UriBuilder $uriBuilder)
    {
    }
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('tableName', 'string', 'table name of record to be edited', true);
        $this->registerArgument('uid', 'integer', 'identifier of the record to be edited', true);
    }

    /**
     * @throws RouteNotFoundException
     */
    public function render(): string
    {
        $uriBuilder = $this->uriBuilder;
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        $returnUrl = '';
        if (
            $request instanceof ServerRequestInterface
            && ApplicationType::fromRequest($request)->isBackend()
        ) {
            $returnUrl = (string)$request->getUri();
        }

        return (string)$uriBuilder->buildUriFromRoute('record_edit', [
            'edit[' . $this->arguments['tableName'] . '][' . $this->arguments['uid'] . ']' => 'edit',
            'returnUrl' => $returnUrl,
        ]);
    }
}
