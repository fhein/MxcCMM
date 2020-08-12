<?php

namespace MxcCommons\Plugin\Service;

interface LoggerAwareInterface
{
    public function setLog(LoggerInterface $log);
}