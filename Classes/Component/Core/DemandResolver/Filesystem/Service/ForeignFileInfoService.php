<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FilesystemInformationCollection;
use In2code\In2publishCore\Component\RemoteProcedureCall\Envelope;
use In2code\In2publishCore\Component\RemoteProcedureCall\EnvelopeDispatcher;
use In2code\In2publishCore\Component\RemoteProcedureCall\ExecuteCommandDispatcherInjection;

class ForeignFileInfoService
{
    use ExecuteCommandDispatcherInjection;

    public function getFileInfo(array $request): FilesystemInformationCollection
    {
        /** @see EnvelopeDispatcher::getFileInfo */
        $envelope = new Envelope(EnvelopeDispatcher::CMD_GET_FILE_INFO, $request);
        return $this->executeCommandDispatcher->executeEnvelope($envelope);
    }
}
