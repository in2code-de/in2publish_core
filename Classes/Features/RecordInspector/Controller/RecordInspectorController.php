<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RecordInspector\Controller;

use In2code\In2publishCore\Component\Core\FileHandling\DefaultFalFinderInjection;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuilderInjection;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuildRequest;
use In2code\In2publishCore\Features\AdminTools\Controller\Traits\AdminToolsModuleTemplate;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

use function array_combine;
use function array_keys;
use function array_merge;

class RecordInspectorController extends ActionController
{
    use AdminToolsModuleTemplate;
    use RecordTreeBuilderInjection;
    use DefaultFalFinderInjection;

    public function indexAction(): ResponseInterface
    {
        $tables = array_keys($GLOBALS['TCA'] ?? []);
        $this->view->assign(
            'classifications',
            array_merge(
                [
                    FileRecord::CLASSIFICATION => FileRecord::CLASSIFICATION,
                    FolderRecord::CLASSIFICATION => FolderRecord::CLASSIFICATION,
                ],
                array_combine($tables, $tables)
            )
        );
        return $this->htmlResponse();
    }

    public function inspectAction(string $classification, string $id): ResponseInterface
    {
        if ($classification === FileRecord::CLASSIFICATION) {
            $recordTree = $this->defaultFalFinder->findFileRecord($id);
        } elseif ($classification === FolderRecord::CLASSIFICATION) {
            $recordTree = $this->defaultFalFinder->findFolderRecord($id);
        } else {
            $request = new RecordTreeBuildRequest($classification, (int)$id, 1);
            $recordTree = $this->recordTreeBuilder->buildRecordTree($request);
        }
        $this->view->assign('recordTree', $recordTree);
        return $this->htmlResponse();
    }
}
