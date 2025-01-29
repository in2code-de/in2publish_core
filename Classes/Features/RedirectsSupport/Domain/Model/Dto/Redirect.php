<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Domain\Model\Dto;

use function sprintf;

class Redirect
{
    // Required properties
    public int $uid;
    public int $pid;
    public string $source_host;
    public string $source_path;
    public string $target;
    public int $target_statuscode;
    public int $createdon;
    public int $updatedon;

    // Optional properties (with default values or nullable)
    public ?int $creation_type;
    public int $deleted = 0;
    public int $disable_hitcount = 0;
    public int $disabled = 0;
    public ?string $description;
    public int $endtime = 0;
    public int $force_https = 0;
    public int $hitcount = 0;
    public string $integrity_status = '';
    public int $is_regexp = 0;
    public int $keep_query_parameters = 0;
    public int $lasthiton = 0;
    public int $protected = 0;
    public int $respect_query_parameters = 0;
    public int $starttime = 0;
    public ?string $tx_in2publishcore_foreign_site_id;
    public ?int $tx_in2publishcore_page_uid;

    public function __toString()
    {
        return sprintf(
            'Redirect [%d] (%s) %s -> %s',
            $this->uid,
            $this->source_host,
            $this->source_path,
            $this->target,
        );
    }
}
