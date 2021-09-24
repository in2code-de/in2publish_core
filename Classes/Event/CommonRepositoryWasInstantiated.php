<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Repository\CommonRepository;

final class CommonRepositoryWasInstantiated
{
    /** @var CommonRepository */
    private $commonRepository;

    public function __construct(CommonRepository $commonRepository)
    {
        $this->commonRepository = $commonRepository;
    }

    public function getCommonRepository(): CommonRepository
    {
        return $this->commonRepository;
    }
}
