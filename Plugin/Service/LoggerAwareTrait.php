<?php

namespace MxcCommons\Plugin\Service;

trait LoggerAwareTrait
{
    /** @var Logger */
    protected $log;

    public function setLog(Logger $log)
    {
        $this->log = $log;
    }
}