<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RecordInspector\Controller;

use In2code\In2publishCore\Component\Core\FileHandling\DefaultFalFinderInjection;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuilderInjection;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuildRequest;
use In2code\In2publishCore\Features\AdminTools\Controller\AbstractAdminToolsController;
use Psr\Http\Message\ResponseInterface;

use function array_combine;
use function array_keys;
use function array_merge;

class RecordInspectorController extends AbstractAdminToolsController
{
    use RecordTreeBuilderInjection;
    use DefaultFalFinderInjection;

    public function indexAction(): ResponseInterface
    {
        $tables = array_keys($GLOBALS['TCA'] ?? []);
        $classifications = array_merge(
            [
                FileRecord::CLASSIFICATION => FileRecord::CLASSIFICATION,
                FolderRecord::CLASSIFICATION => FolderRecord::CLASSIFICATION,
            ],
            array_combine($tables, $tables),
        );

        $this->moduleTemplate->assignMultiple([
            'classifications' => $classifications,
        ]);

        return $this->htmlResponse();
    }

    public function inspectAction(string $classification, string $identifier): ResponseInterface
    {
        if ($classification === FileRecord::CLASSIFICATION) {
            $recordTree = $this->defaultFalFinder->findFileRecord($identifier);
        } elseif ($classification === FolderRecord::CLASSIFICATION) {
            $recordTree = $this->defaultFalFinder->findFolderRecord($identifier);
        } else {
            $request = new RecordTreeBuildRequest($classification, (int)$identifier, 1);
            $recordTree = $this->recordTreeBuilder->buildRecordTree($request);
        }
        $this->moduleTemplate->assignMultiple([
            'recordTree' => $recordTree
        ]);
        return $this->htmlResponse();
    }
}
