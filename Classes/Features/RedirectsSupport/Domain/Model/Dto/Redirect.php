<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Domain\Model\Dto;

use function sprintf;

class Redirect
{
    public int $uid;
    public int $pid;
    public int $updatedon;
    public int $createdon;
    public int $createdby;
    public int $deleted;
    public int $disabled;
    public int $starttime;
    public int $endtime;
    public string $source_host;
    public string $source_path;
    public int $is_regexp;
    public int $protected;
    public int $force_https;
    public int $respect_query_parameters;
    public int $keep_query_parameters;
    public string $target;
    public int $target_statuscode;
    public int $hitcount;
    public int $lasthiton;
    public int $disable_hitcount;
    public ?int $tx_in2publishcore_page_uid;
    public ?string $tx_in2publishcore_foreign_site_id;

    public function __toString()
    {
        return sprintf(
            'Redirect [%d] (%s) %s -> %s',
            $this->uid,
            $this->source_host,
            $this->source_path,
            $this->target
        );
    }
}
