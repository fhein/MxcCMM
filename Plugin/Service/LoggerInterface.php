<?php

namespace MxcCommons\Plugin\Service;

use Throwable;
use MxcCommons\Log\LoggerInterface as BaseInterface;

interface LoggerInterface extends BaseInterface
{
    public function except(Throwable $e, bool $logTrace = true, bool $rethrow = true);

    public function enter();

    public function leave();
}