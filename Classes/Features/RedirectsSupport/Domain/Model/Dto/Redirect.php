<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Domain\Model\Dto;

use function sprintf;

class Redirect
{
    public $uid;
    public $pid;
    public $updatedon;
    public $createdon;
    public $createdby;
    public $deleted;
    public $disabled;
    public $starttime;
    public $endtime;
    public $source_host;
    public $source_path;
    public $is_regexp;
    public $protected;
    public $force_https;
    public $respect_query_parameters;
    public $keep_query_parameters;
    public $target;
    public $target_statuscode;
    public $hitcount;
    public $lasthiton;
    public $disable_hitcount;
    public $tx_in2publishcore_page_uid;
    public $tx_in2publishcore_foreign_site_id;

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
