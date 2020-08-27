<?php

namespace MxcCommons\Plugin\Service;

use MxcCommons\Interop\Container\ContainerInterface;

class ServicesAwareTrait
{
    /** @var ContainerInterface */
    protected  $services;

    public function setDatabase(ContainerInterface $services)
    {
        $this->services = $services;
    }
}