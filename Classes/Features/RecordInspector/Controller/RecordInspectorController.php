<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RecordInspector\Controller;

use In2code\In2publishCore\Component\TcaHandling\RecordTreeBuilder;
use In2code\In2publishCore\Features\AdminTools\Controller\Traits\AdminToolsModuleTemplate;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

use function array_combine;
use function array_keys;

class RecordInspectorController extends ActionController
{
    use AdminToolsModuleTemplate;

    protected RecordTreeBuilder $recordTreeBuilder;

    public function injectRecordTreeBuilder(RecordTreeBuilder $recordTreeBuilder): void
    {
        $this->recordTreeBuilder = $recordTreeBuilder;
    }

    public function indexAction(): ResponseInterface
    {
        $tables = array_keys($GLOBALS['TCA'] ?? []);
        $this->view->assign('classifications', array_combine($tables, $tables));
        return $this->htmlResponse();
    }

    public function inspectAction(string $classification, int $id): ResponseInterface
    {
        $recordTree = $this->recordTreeBuilder->buildRecordTree($classification, $id);
        $this->view->assign('recordTree', $recordTree);
        return $this->htmlResponse();
    }
}
