<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FilesystemInformationCollection;
use In2code\In2publishCore\Component\RemoteProcedureCall\Envelope;
use In2code\In2publishCore\Component\RemoteProcedureCall\EnvelopeDispatcher;
use In2code\In2publishCore\Component\RemoteProcedureCall\ExecuteCommandDispatcherInjection;

class ForeignFolderInfoService
{
    use ExecuteCommandDispatcherInjection;
    use SharedFilesystemInfoCacheInjection;

    public function getFolderInformation(array $request): FilesystemInformationCollection
    {
        $collection = $this->sharedFilesystemInfoCache->getForeign($request);
        if (null !== $collection) {
            return $collection;
        }

        /** @see EnvelopeDispatcher::getFolderInfo */
        $envelope = new Envelope(EnvelopeDispatcher::CMD_GET_FOLDER_INFO, $request);

        $response = $this->executeCommandDispatcher->executeEnvelope($envelope);

        $this->sharedFilesystemInfoCache->setForeign($response, $request);

        return $response;
    }
}
