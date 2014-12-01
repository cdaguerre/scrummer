<?php

namespace Scrummer\EventListener;

use Scrummer\Scrummer;

abstract class AbstractEventListener
{
    protected $scrummer;

    public function __construct(Scrummer $scrummer)
    {
        $this->scrummer = $scrummer;
    }
}
